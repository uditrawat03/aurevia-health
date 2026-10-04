<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditOutcome;
use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Models\Facility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class ConsentPrivacyFoundationTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    private const string PATIENT_QUERY = <<<'GRAPHQL'
        query Patient($organizationId: ID!, $id: ID!) {
            patient(organizationId: $organizationId, id: $id) {
                id
                givenName
                familyName
                contacts {
                    value
                }
            }
        }
        GRAPHQL;

    private const string GRANT_CONSENT_MUTATION = <<<'GRAPHQL'
        mutation GrantConsent($input: GrantPatientConsentInput!) {
            grantPatientConsent(input: $input) {
                id
                status
                isEffective
            }
        }
        GRAPHQL;

    private const string REVOKE_CONSENT_MUTATION = <<<'GRAPHQL'
        mutation RevokeConsent($input: RevokePatientConsentInput!) {
            revokePatientConsent(input: $input) {
                id
                status
                revocationReason
                isEffective
            }
        }
        GRAPHQL;

    private const string PRIVACY_DECISION_QUERY = <<<'GRAPHQL'
        query PrivacyDecision($input: PatientPrivacyDecisionInput!) {
            patientPrivacyDecision(input: $input) {
                allowed
                reason
                breakGlassAccessId
            }
        }
        GRAPHQL;

    private const string BREAK_GLASS_MUTATION = <<<'GRAPHQL'
        mutation BreakGlass($input: ActivateBreakGlassInput!) {
            activateBreakGlass(input: $input) {
                id
                reason
                purpose
                expiresAt
            }
        }
        GRAPHQL;

    public function test_consent_grant_allows_patient_read_and_revocation_denies_it(): void
    {
        [$owner, $organization, $facility, $patient] = $this->workspace();
        $this->actingAs($owner, 'web');

        $deniedBeforeConsent = $this->patientRead(
            organizationId: (string) $organization->getKey(),
            patientId: (string) $patient->getKey(),
            correlationId: 'privacy-before-consent',
        );
        $deniedBeforeConsent
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'privacy-before-consent');

        $consentId = $this->grantConsent(
            organizationId: (string) $organization->getKey(),
            patientId: (string) $patient->getKey(),
            facilityId: (string) $facility->getKey(),
            correlationId: 'privacy-grant-consent',
        )->json('data.grantPatientConsent.id');
        self::assertIsString($consentId);

        $allowed = $this->patientRead(
            organizationId: (string) $organization->getKey(),
            patientId: (string) $patient->getKey(),
            correlationId: 'privacy-after-consent',
        );
        $allowed
            ->assertOk()
            ->assertJsonPath('data.patient.id', $patient->getKey());

        $revoked = $this->postGraphQL(
            self::REVOKE_CONSENT_MUTATION,
            'privacy-revoke-consent',
            [
                'input' => [
                    'organizationId' => (string) $organization->getKey(),
                    'patientId' => (string) $patient->getKey(),
                    'consentId' => $consentId,
                    'reason' => 'Patient withdrew treatment consent',
                ],
            ],
        );
        $revoked
            ->assertOk()
            ->assertJsonPath('data.revokePatientConsent.status', 'REVOKED')
            ->assertJsonPath('data.revokePatientConsent.isEffective', false);

        $decision = $this->privacyDecision(
            organizationId: (string) $organization->getKey(),
            patientId: (string) $patient->getKey(),
            correlationId: 'privacy-decision-revoked',
        );
        $decision
            ->assertOk()
            ->assertJsonPath('data.patientPrivacyDecision.allowed', false)
            ->assertJsonPath('data.patientPrivacyDecision.reason', 'NO_EFFECTIVE_CONSENT');

        $deniedAfterRevocation = $this->patientRead(
            organizationId: (string) $organization->getKey(),
            patientId: (string) $patient->getKey(),
            correlationId: 'privacy-after-revocation',
        );
        $deniedAfterRevocation
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseHas('patient_consents', [
            'id' => $consentId,
            'status' => 'REVOKED',
            'revocation_reason' => 'Patient withdrew treatment consent',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'actor_user_id' => $owner->getKey(),
            'patient_id' => $patient->getKey(),
            'action' => AuditAction::REVOKE_PATIENT_CONSENT->value,
            'outcome' => AuditOutcome::ALLOWED->value,
            'correlation_id' => 'privacy-revoke-consent',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'patient_id' => $patient->getKey(),
            'action' => AuditAction::PRIVACY_DECISION->value,
            'outcome' => AuditOutcome::DENIED->value,
            'correlation_id' => 'privacy-decision-revoked',
        ]);
    }

    public function test_break_glass_requires_a_reason_and_temporarily_allows_treatment_access(): void
    {
        [$owner, $organization, $facility, $patient] = $this->workspace(slug: 'break-glass-org');
        $this->actingAs($owner, 'web');

        $shortReason = $this->postGraphQL(
            self::BREAK_GLASS_MUTATION,
            'privacy-break-glass-short',
            [
                'input' => [
                    'organizationId' => (string) $organization->getKey(),
                    'patientId' => (string) $patient->getKey(),
                    'purpose' => 'TREATMENT',
                    'reason' => 'urgent',
                ],
            ],
        );
        $shortReason
            ->assertOk()
            ->assertJsonPath('data', null);
        $this->assertDatabaseCount('break_glass_accesses', 0);

        $reason = 'Immediate emergency treatment requires access';
        $activated = $this->postGraphQL(
            self::BREAK_GLASS_MUTATION,
            'privacy-break-glass-activate',
            [
                'input' => [
                    'organizationId' => (string) $organization->getKey(),
                    'patientId' => (string) $patient->getKey(),
                    'purpose' => 'TREATMENT',
                    'reason' => $reason,
                ],
            ],
        );
        $breakGlassId = $activated->json('data.activateBreakGlass.id');
        self::assertIsString($breakGlassId);
        $activated
            ->assertOk()
            ->assertJsonPath('data.activateBreakGlass.reason', $reason)
            ->assertJsonPath('data.activateBreakGlass.purpose', 'TREATMENT');

        $decision = $this->privacyDecision(
            organizationId: (string) $organization->getKey(),
            patientId: (string) $patient->getKey(),
            correlationId: 'privacy-break-glass-decision',
        );
        $decision
            ->assertOk()
            ->assertJsonPath('data.patientPrivacyDecision.allowed', true)
            ->assertJsonPath('data.patientPrivacyDecision.reason', 'BREAK_GLASS')
            ->assertJsonPath('data.patientPrivacyDecision.breakGlassAccessId', $breakGlassId);

        $allowedRead = $this->patientRead(
            organizationId: (string) $organization->getKey(),
            patientId: (string) $patient->getKey(),
            correlationId: 'privacy-break-glass-read',
        );
        $allowedRead
            ->assertOk()
            ->assertJsonPath('data.patient.id', $patient->getKey());

        $this->assertDatabaseHas('break_glass_accesses', [
            'id' => $breakGlassId,
            'organization_id' => $organization->getKey(),
            'facility_id' => $facility->getKey(),
            'patient_id' => $patient->getKey(),
            'actor_user_id' => $owner->getKey(),
            'reason' => $reason,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'patient_id' => $patient->getKey(),
            'resource_id' => $breakGlassId,
            'action' => AuditAction::ACTIVATE_BREAK_GLASS->value,
            'correlation_id' => 'privacy-break-glass-activate',
        ]);
    }

    public function test_patient_search_projection_cannot_bypass_privacy_with_sensitive_fields(): void
    {
        [$owner, $organization, , $patient] = $this->workspace(slug: 'privacy-search-org');
        $this->actingAs($owner, 'web');

        $query = <<<'GRAPHQL'
            query Patients($input: PatientSearchInput!) {
                patients(input: $input) {
                    items {
                        id
                        contacts {
                            value
                        }
                    }
                }
            }
            GRAPHQL;

        $response = $this->postGraphQL(
            $query,
            'privacy-search-sensitive-field',
            [
                'input' => [
                    'organizationId' => (string) $organization->getKey(),
                    'query' => 'Privacy',
                ],
            ],
        );

        $response->assertOk();
        $message = $response->json('errors.0.message');
        self::assertIsString($message);
        self::assertStringContainsString('contacts', $message);
        self::assertNull($response->json('data'));
        self::assertNotNull($patient->getKey());
    }

    private function patientRead(
        string $organizationId,
        string $patientId,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::PATIENT_QUERY,
            $correlationId,
            [
                'organizationId' => $organizationId,
                'id' => $patientId,
            ],
        );
    }

    private function grantConsent(
        string $organizationId,
        string $patientId,
        string $facilityId,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::GRANT_CONSENT_MUTATION,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'patientId' => $patientId,
                    'facilityId' => $facilityId,
                    'dataCategory' => 'DEMOGRAPHICS',
                    'purpose' => 'TREATMENT',
                    'recipientClass' => 'CARE_TEAM',
                ],
            ],
        );
    }

    private function privacyDecision(
        string $organizationId,
        string $patientId,
        string $correlationId,
    ): TestResponse {
        return $this->postGraphQL(
            self::PRIVACY_DECISION_QUERY,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'patientId' => $patientId,
                    'dataCategory' => 'DEMOGRAPHICS',
                    'purpose' => 'TREATMENT',
                    'recipientClass' => 'CARE_TEAM',
                ],
            ],
        );
    }

    /** @return array{User, Organization, Facility, Patient} */
    private function workspace(string $slug = 'consent-privacy-org'): array
    {
        $owner = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Consent Privacy Organization',
            'slug' => $slug,
            'country_code' => 'IN',
            'country_profile_code' => 'IN',
            'country_profile_version' => '1.0.0',
        ]);
        $facility = Facility::query()->create([
            'organization_id' => $organization->getKey(),
            'health_system_id' => null,
            'name' => 'Privacy Facility',
            'code' => 'PRIVACY-FAC',
        ]);
        OrganizationMembership::query()->create([
            'user_id' => $owner->getKey(),
            'organization_id' => $organization->getKey(),
            'role' => OrganizationRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => true,
        ]);
        $patient = Patient::query()->create([
            'organization_id' => $organization->getKey(),
            'registration_facility_id' => $facility->getKey(),
            'given_name' => 'Privacy',
            'normalized_given_name' => 'privacy',
            'middle_name' => null,
            'family_name' => 'Patient',
            'normalized_family_name' => 'patient',
            'preferred_name' => null,
            'date_of_birth' => '1990-01-01',
            'sex_at_birth' => 'UNKNOWN',
        ]);

        return [$owner, $organization, $facility, $patient];
    }
}
