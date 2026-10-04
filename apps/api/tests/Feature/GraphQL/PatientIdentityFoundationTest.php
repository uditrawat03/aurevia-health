<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditOutcome;
use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Domains\Patient\Enums\PatientMergeReviewStatus;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class PatientIdentityFoundationTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    private const string REGISTER_PATIENT_MUTATION = <<<'GRAPHQL'
        mutation RegisterPatient($input: RegisterPatientInput!) {
            registerPatient(input: $input) {
                patient {
                    id
                    organizationId
                    registrationFacilityId
                    givenName
                    familyName
                    dateOfBirth
                    sexAtBirth
                    identifiers {
                        type
                        system
                        value
                    }
                }
                duplicateCandidates {
                    patientId
                    confidence
                    reason
                }
            }
        }
        GRAPHQL;

    private const string PATIENT_SEARCH_QUERY = <<<'GRAPHQL'
        query Patients($input: PatientSearchInput!) {
            patients(input: $input) {
                total
                items {
                    id
                    givenName
                    familyName
                    identifiers {
                        value
                    }
                }
            }
        }
        GRAPHQL;

    private const string PATIENT_QUERY = <<<'GRAPHQL'
        query Patient($organizationId: ID!, $id: ID!) {
            patient(organizationId: $organizationId, id: $id) {
                id
                givenName
                familyName
            }
        }
        GRAPHQL;

    private const string REQUEST_MERGE_REVIEW_MUTATION = <<<'GRAPHQL'
        mutation RequestMerge($input: RequestPatientMergeReviewInput!) {
            requestPatientMergeReview(input: $input) {
                id
                sourcePatientId
                targetPatientId
                status
                reason
            }
        }
        GRAPHQL;

    public function test_registration_returns_duplicate_candidates_without_auto_merging_and_is_audited(): void
    {
        [$owner, $organization, $facility] = $this->workspace(
            role: OrganizationRole::OWNER,
            allFacilities: true,
        );
        $this->actingAs($owner, 'web');

        $first = $this->register(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            mrn: 'MPI-001',
            correlationId: 'patient-register-first',
        );
        $firstId = $first->json('data.registerPatient.patient.id');
        self::assertIsString($firstId);
        $first->assertJsonCount(0, 'data.registerPatient.duplicateCandidates');

        $second = $this->register(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            mrn: 'MPI-002',
            correlationId: 'patient-register-duplicate',
        );
        $secondId = $second->json('data.registerPatient.patient.id');
        self::assertIsString($secondId);

        $second
            ->assertOk()
            ->assertJsonPath('data.registerPatient.duplicateCandidates.0.patientId', $firstId)
            ->assertJsonPath('data.registerPatient.duplicateCandidates.0.reason', 'NAME_AND_DOB');

        self::assertNotSame($firstId, $secondId);
        $this->assertDatabaseCount('patients', 2);
        $this->assertDatabaseHas('audit_events', [
            'actor_user_id' => $owner->getKey(),
            'organization_id' => $organization->getKey(),
            'facility_id' => $facility->getKey(),
            'patient_id' => $secondId,
            'action' => AuditAction::REGISTER_PATIENT->value,
            'outcome' => AuditOutcome::ALLOWED->value,
            'correlation_id' => 'patient-register-duplicate',
        ]);
    }

    public function test_patient_search_and_read_enforce_tenant_and_facility_scope(): void
    {
        [$owner, $organization, $facilityA] = $this->workspace(
            role: OrganizationRole::OWNER,
            allFacilities: true,
        );
        $facilityB = Facility::query()->create([
            'organization_id' => $organization->getKey(),
            'health_system_id' => null,
            'name' => 'Facility B',
            'code' => 'FAC-B',
        ]);

        $this->actingAs($owner, 'web');
        $patientA = $this->register(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facilityA->getKey(),
            mrn: 'SCOPE-A',
            correlationId: 'patient-scope-a',
        )->json('data.registerPatient.patient.id');
        self::assertIsString($patientA);

        $patientB = $this->register(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facilityB->getKey(),
            mrn: 'SCOPE-B',
            correlationId: 'patient-scope-b',
        )->json('data.registerPatient.patient.id');
        self::assertIsString($patientB);

        $staff = User::factory()->create();
        $membership = OrganizationMembership::query()->create([
            'user_id' => $staff->getKey(),
            'organization_id' => $organization->getKey(),
            'role' => OrganizationRole::STAFF->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => false,
        ]);
        $membership->facilities()->attach($facilityA->getKey());

        $this->actingAs($staff, 'web');
        $allowedSearch = $this->postGraphQL(
            self::PATIENT_SEARCH_QUERY,
            'patient-search-allowed',
            [
                'input' => [
                    'organizationId' => (string) $organization->getKey(),
                    'facilityId' => (string) $facilityA->getKey(),
                    'query' => 'Asha',
                ],
            ],
        );
        $allowedSearch
            ->assertOk()
            ->assertJsonPath('data.patients.total', 1)
            ->assertJsonPath('data.patients.items.0.id', $patientA);

        $allowedRead = $this->postGraphQL(
            self::PATIENT_QUERY,
            'patient-read-allowed',
            [
                'organizationId' => (string) $organization->getKey(),
                'id' => $patientA,
            ],
        );
        $allowedRead
            ->assertOk()
            ->assertJsonPath('data.patient.id', $patientA);

        $deniedRead = $this->postGraphQL(
            self::PATIENT_QUERY,
            'patient-read-wrong-facility',
            [
                'organizationId' => (string) $organization->getKey(),
                'id' => $patientB,
            ],
        );
        $deniedRead
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'patient-read-wrong-facility');

        [, $otherOrganization] = $this->workspace(
            role: OrganizationRole::OWNER,
            allFacilities: true,
            slug: 'other-patient-org',
        );
        $this->actingAs($staff, 'web');
        $wrongTenant = $this->postGraphQL(
            self::PATIENT_SEARCH_QUERY,
            'patient-search-wrong-tenant',
            [
                'input' => [
                    'organizationId' => (string) $otherOrganization->getKey(),
                    'query' => 'Asha',
                ],
            ],
        );
        $wrongTenant
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'patient-search-wrong-tenant');

    }

    public function test_merge_review_is_pending_and_never_merges_automatically(): void
    {
        [$owner, $organization, $facility] = $this->workspace(
            role: OrganizationRole::OWNER,
            allFacilities: true,
            slug: 'merge-review-org',
        );
        $this->actingAs($owner, 'web');

        $sourceId = $this->register(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            mrn: 'MERGE-001',
            correlationId: 'merge-source',
        )->json('data.registerPatient.patient.id');
        $targetId = $this->register(
            organizationId: (string) $organization->getKey(),
            facilityId: (string) $facility->getKey(),
            mrn: 'MERGE-002',
            correlationId: 'merge-target',
        )->json('data.registerPatient.patient.id');
        self::assertIsString($sourceId);
        self::assertIsString($targetId);

        $response = $this->postGraphQL(
            self::REQUEST_MERGE_REVIEW_MUTATION,
            'patient-merge-review',
            [
                'input' => [
                    'organizationId' => (string) $organization->getKey(),
                    'sourcePatientId' => $sourceId,
                    'targetPatientId' => $targetId,
                    'reason' => 'Synthetic near-duplicate review',
                ],
            ],
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.requestPatientMergeReview.status',
                PatientMergeReviewStatus::PENDING->value,
            )
            ->assertJsonPath('data.requestPatientMergeReview.sourcePatientId', $sourceId)
            ->assertJsonPath('data.requestPatientMergeReview.targetPatientId', $targetId);

        $this->assertDatabaseCount('patients', 2);
        $this->assertDatabaseHas('patient_merge_reviews', [
            'organization_id' => $organization->getKey(),
            'source_patient_id' => $sourceId,
            'target_patient_id' => $targetId,
            'status' => PatientMergeReviewStatus::PENDING->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'actor_user_id' => $owner->getKey(),
            'patient_id' => $targetId,
            'action' => AuditAction::REQUEST_PATIENT_MERGE_REVIEW->value,
            'correlation_id' => 'patient-merge-review',
        ]);
    }

    private function register(
        string $organizationId,
        string $facilityId,
        string $mrn,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::REGISTER_PATIENT_MUTATION,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'registrationFacilityId' => $facilityId,
                    'givenName' => 'Asha',
                    'familyName' => 'Mehta',
                    'dateOfBirth' => '1988-04-18',
                    'sexAtBirth' => 'FEMALE',
                    'identifiers' => [
                        [
                            'type' => 'MRN',
                            'system' => 'test-mrn',
                            'value' => $mrn,
                        ],
                    ],
                    'contacts' => [],
                    'addresses' => [],
                    'relationships' => [],
                ],
            ],
        );
    }

    /** @return array{User, Organization, Facility} */
    private function workspace(
        OrganizationRole $role,
        bool $allFacilities,
        string $slug = 'patient-foundation-org',
    ): array {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Patient Foundation Organization',
            'slug' => $slug,
            'country_code' => 'IN',
            'country_profile_code' => 'IN',
            'country_profile_version' => '1.0.0',
        ]);
        $facility = Facility::query()->create([
            'organization_id' => $organization->getKey(),
            'health_system_id' => null,
            'name' => 'Facility A',
            'code' => 'FAC-A',
        ]);
        OrganizationMembership::query()->create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'role' => $role->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => $allFacilities,
        ]);

        return [$user, $organization, $facility];
    }
}
