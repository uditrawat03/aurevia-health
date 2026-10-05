<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Scheduling\Enums\SchedulingResourceType;
use App\Models\AppointmentType;
use App\Models\SchedulingResource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Support\InteractsWithGraphQL;
use Tests\TestCase;

final class V1PerformanceBaseline extends TestCase
{
    use InteractsWithGraphQL;

    private const int ITERATIONS = 25;
    private const int WARMUPS = 3;

    private const string CREATE_ORGANIZATION = <<<'GRAPHQL'
        mutation CreateOrganization($input: CreateOrganizationInput!) {
            createOrganization(input: $input) { id }
        }
        GRAPHQL;

    private const string CREATE_FACILITY = <<<'GRAPHQL'
        mutation CreateFacility($input: CreateFacilityInput!) {
            createFacility(input: $input) { id }
        }
        GRAPHQL;

    private const string ASSIGN_MEMBERSHIP = <<<'GRAPHQL'
        mutation AssignOrganizationMembership($input: AssignOrganizationMembershipInput!) {
            assignOrganizationMembership(input: $input) { role status facilityIds }
        }
        GRAPHQL;

    private const string REGISTER_PATIENT = <<<'GRAPHQL'
        mutation RegisterPatient($input: RegisterPatientInput!) {
            registerPatient(input: $input) { patient { id givenName familyName } }
        }
        GRAPHQL;

    private const string GRANT_CONSENT = <<<'GRAPHQL'
        mutation GrantPatientConsent($input: GrantPatientConsentInput!) {
            grantPatientConsent(input: $input) { id status }
        }
        GRAPHQL;

    private const string BOOK_APPOINTMENT = <<<'GRAPHQL'
        mutation BookAppointment($input: BookAppointmentInput!) {
            bookAppointment(input: $input) { appointment { id status } replayed }
        }
        GRAPHQL;

    private const string CREATE_ENCOUNTER = <<<'GRAPHQL'
        mutation CreateEncounter($input: CreateEncounterInput!) {
            createEncounter(input: $input) { id status }
        }
        GRAPHQL;

    private const string TRANSITION_ENCOUNTER = <<<'GRAPHQL'
        mutation TransitionEncounter($input: TransitionEncounterInput!) {
            transitionEncounter(input: $input) { id status }
        }
        GRAPHQL;

    private const string RECORD_PROBLEM = <<<'GRAPHQL'
        mutation RecordProblem($input: RecordProblemInput!) {
            recordProblem(input: $input) { id display status }
        }
        GRAPHQL;

    private const string ORGANIZATION = <<<'GRAPHQL'
        query Organization($id: ID!) {
            organization(id: $id) { id name countryCode facilities { id name code } }
        }
        GRAPHQL;

    private const string PATIENT_SEARCH = <<<'GRAPHQL'
        query Patients($input: PatientSearchInput!) {
            patients(input: $input) {
                total
                items { id givenName familyName registrationFacilityId identifiers { type system value } }
            }
        }
        GRAPHQL;

    private const string CLINICAL_RECORD = <<<'GRAPHQL'
        query ClinicalRecord($input: PatientClinicalRecordInput!) {
            clinicalRecord(input: $input) {
                patientId
                problems { id display status }
                allergies { id }
                observations { id }
                notes { id status }
                timeline { id type resourceType }
            }
        }
        GRAPHQL;

    private const string APPOINTMENTS = <<<'GRAPHQL'
        query Appointments($input: AppointmentWindowInput!) {
            appointments(input: $input) {
                id patientId status startsAt endsAt timezone
                appointmentType { id name }
                resources { id name type }
            }
        }
        GRAPHQL;

    private const string AUDIT_EVENTS = <<<'GRAPHQL'
        query AuditEvents($input: AuditEventsInput!) {
            auditEvents(input: $input) {
                id action outcome resourceType patientId occurredAt
            }
        }
        GRAPHQL;

    protected function setUp(): void
    {
        parent::setUp();

        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
        if ($exitCode !== 0) {
            throw new RuntimeException('Performance baseline migrations failed: '.Artisan::output());
        }
    }

    public function test_v1_graphql_performance_baseline(): void
    {
        [$owner, $scheduler, $clinician, $organizationId, $facilityId, $patientId] = $this->seedSyntheticWorkflow();

        $from = CarbonImmutable::now('UTC')->subDay()->toIso8601String();
        $to = CarbonImmutable::now('UTC')->addDays(7)->toIso8601String();

        $this->actingAs($owner, 'web');
        $this->printMetric('authenticated_graphql', fn (int $iteration): TestResponse => $this->postGraphQL(
            self::ORGANIZATION,
            "perf-org-$iteration",
            ['id' => $organizationId],
        ));

        $this->actingAs($scheduler, 'web');
        $this->printMetric('patient_search', fn (int $iteration): TestResponse => $this->postGraphQL(
            self::PATIENT_SEARCH,
            "perf-patient-search-$iteration",
            ['input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'query' => 'Performance',
                'limit' => 20,
            ]],
        ));

        $this->actingAs($clinician, 'web');
        $this->printMetric('clinical_record', fn (int $iteration): TestResponse => $this->postGraphQL(
            self::CLINICAL_RECORD,
            "perf-clinical-record-$iteration",
            ['input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
            ]],
        ));

        $this->actingAs($scheduler, 'web');
        $this->printMetric('scheduling_window', fn (int $iteration): TestResponse => $this->postGraphQL(
            self::APPOINTMENTS,
            "perf-schedule-$iteration",
            ['input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'from' => $from,
                'to' => $to,
            ]],
        ));

        $this->actingAs($owner, 'web');
        $this->printMetric('audit_view', fn (int $iteration): TestResponse => $this->postGraphQL(
            self::AUDIT_EVENTS,
            "perf-audit-$iteration",
            ['input' => ['organizationId' => $organizationId, 'limit' => 50]],
        ));
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: string, 4: string, 5: string}
     */
    private function seedSyntheticWorkflow(): array
    {
        $owner = User::factory()->create();
        $scheduler = User::factory()->create();
        $clinician = User::factory()->create();
        $this->actingAs($owner, 'web');

        $organizationId = $this->postGraphQL(self::CREATE_ORGANIZATION, 'perf-create-organization', [
            'input' => [
                'name' => 'Aurevia Performance Synthetic Health',
                'slug' => 'aurevia-performance-synthetic-health',
                'countryCode' => 'IN',
            ],
        ])->assertOk()->json('data.createOrganization.id');
        self::assertIsString($organizationId);

        $facilityId = $this->postGraphQL(self::CREATE_FACILITY, 'perf-create-facility', [
            'input' => [
                'organizationId' => $organizationId,
                'name' => 'Aurevia Performance Synthetic Hospital',
                'code' => 'PERF-SYNTH',
                'settings' => ['timezone' => 'Asia/Kolkata'],
            ],
        ])->assertOk()->json('data.createFacility.id');
        self::assertIsString($facilityId);

        $this->assignMembership($organizationId, $facilityId, $scheduler, OrganizationRole::STAFF, 'perf-assign-scheduler');
        $this->assignMembership($organizationId, $facilityId, $clinician, OrganizationRole::CLINICIAN, 'perf-assign-clinician');

        $appointmentType = AppointmentType::query()->create([
            'organization_id' => $organizationId,
            'facility_id' => $facilityId,
            'code' => 'PERF-GENERAL',
            'name' => 'Performance general consultation',
            'duration_minutes' => 30,
            'active' => true,
        ]);
        $resource = SchedulingResource::query()->create([
            'organization_id' => $organizationId,
            'facility_id' => $facilityId,
            'type' => SchedulingResourceType::PROVIDER->value,
            'name' => 'Dr. Performance Baseline',
            'code' => 'PERF-PROVIDER',
            'active' => true,
        ]);

        $this->actingAs($scheduler, 'web');
        $patientId = $this->postGraphQL(self::REGISTER_PATIENT, 'perf-register-patient', [
            'input' => [
                'organizationId' => $organizationId,
                'registrationFacilityId' => $facilityId,
                'givenName' => 'Performance',
                'familyName' => 'Baseline',
                'dateOfBirth' => '1990-01-15',
                'sexAtBirth' => 'UNKNOWN',
                'identifiers' => [[
                    'type' => 'MRN',
                    'system' => 'urn:aurevia:synthetic:mrn',
                    'value' => 'PERF-0001',
                ]],
            ],
        ])->assertOk()->json('data.registerPatient.patient.id');
        self::assertIsString($patientId);

        foreach (['DEMOGRAPHICS', 'CLINICAL'] as $category) {
            $this->postGraphQL(self::GRANT_CONSENT, 'perf-consent-'.strtolower($category), [
                'input' => [
                    'organizationId' => $organizationId,
                    'patientId' => $patientId,
                    'facilityId' => $facilityId,
                    'dataCategory' => $category,
                    'purpose' => 'TREATMENT',
                    'recipientClass' => 'CARE_TEAM',
                ],
            ])->assertOk()->assertJsonPath('data.grantPatientConsent.status', 'ACTIVE');
        }

        $startsAt = CarbonImmutable::now('Asia/Kolkata')->addDay()->setTime(11, 0)->format('Y-m-d\\TH:i');
        $appointmentId = $this->postGraphQL(self::BOOK_APPOINTMENT, 'perf-book-appointment', [
            'input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
                'appointmentTypeId' => (string) $appointmentType->getKey(),
                'resourceIds' => [(string) $resource->getKey()],
                'startsAtLocal' => $startsAt,
                'timezone' => 'Asia/Kolkata',
                'reason' => 'Synthetic performance baseline',
                'idempotencyKey' => 'perf-booking-0001',
            ],
        ])->assertOk()->json('data.bookAppointment.appointment.id');
        self::assertIsString($appointmentId);

        $this->actingAs($clinician, 'web');
        $encounterId = $this->postGraphQL(self::CREATE_ENCOUNTER, 'perf-create-encounter', [
            'input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
                'appointmentId' => $appointmentId,
                'type' => 'OUTPATIENT',
            ],
        ])->assertOk()->json('data.createEncounter.id');
        self::assertIsString($encounterId);

        foreach (['ARRIVED', 'IN_PROGRESS'] as $status) {
            $this->postGraphQL(self::TRANSITION_ENCOUNTER, 'perf-transition-'.strtolower($status), [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityId,
                    'patientId' => $patientId,
                    'encounterId' => $encounterId,
                    'toStatus' => $status,
                ],
            ])->assertOk();
        }

        $this->postGraphQL(self::RECORD_PROBLEM, 'perf-record-problem', [
            'input' => [
                'organizationId' => $organizationId,
                'facilityId' => $facilityId,
                'patientId' => $patientId,
                'encounterId' => $encounterId,
                'codeSystem' => 'urn:aurevia:synthetic:conditions',
                'code' => 'PERF-PROBLEM',
                'display' => 'Synthetic performance condition',
                'status' => 'ACTIVE',
            ],
        ])->assertOk();

        return [$owner, $scheduler, $clinician, $organizationId, $facilityId, $patientId];
    }

    private function assignMembership(
        string $organizationId,
        string $facilityId,
        User $user,
        OrganizationRole $role,
        string $correlationId,
    ): void {
        $this->postGraphQL(self::ASSIGN_MEMBERSHIP, $correlationId, [
            'input' => [
                'organizationId' => $organizationId,
                'userId' => (string) $user->getKey(),
                'role' => $role->value,
                'allFacilities' => false,
                'facilityIds' => [$facilityId],
            ],
        ])->assertOk();
    }

    /** @param callable(int): TestResponse $operation */
    private function printMetric(string $name, callable $operation): void
    {
        for ($iteration = 0; $iteration < self::WARMUPS; $iteration++) {
            $this->assertGraphQlResponse($operation(-($iteration + 1)));
        }

        $samples = [];
        for ($iteration = 1; $iteration <= self::ITERATIONS; $iteration++) {
            $start = hrtime(true);
            $response = $operation($iteration);
            $elapsedMs = (hrtime(true) - $start) / 1_000_000;
            $this->assertGraphQlResponse($response);
            $samples[] = $elapsedMs;
        }

        sort($samples, SORT_NUMERIC);
        $p50 = $this->percentile($samples, 0.50);
        $p95 = $this->percentile($samples, 0.95);

        printf(
            "AUREVIA_PERF operation=%s iterations=%d p50_ms=%.2f p95_ms=%.2f min_ms=%.2f max_ms=%.2f\n",
            $name,
            self::ITERATIONS,
            $p50,
            $p95,
            $samples[0],
            $samples[array_key_last($samples)],
        );
    }

    private function assertGraphQlResponse(TestResponse $response): void
    {
        $response->assertOk();
        self::assertNull($response->json('errors'), json_encode($response->json('errors')) ?: 'GraphQL errors present.');
    }

    /** @param list<float> $samples */
    private function percentile(array $samples, float $percentile): float
    {
        $index = max(0, min(count($samples) - 1, (int) ceil($percentile * count($samples)) - 1));

        return $samples[$index];
    }
}
