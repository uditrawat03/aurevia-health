<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Models\User;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class IdentityAuthorizationTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    private const string LOGIN_MUTATION = <<<'GRAPHQL'
        mutation Login($input: LoginInput!) {
            login(input: $input) {
                id
                name
                email
                memberships {
                    organizationId
                    role
                    status
                    allFacilities
                    facilityIds
                }
            }
        }
        GRAPHQL;

    private const string CURRENT_USER_QUERY = <<<'GRAPHQL'
        query CurrentUser {
            me {
                id
                email
            }
        }
        GRAPHQL;

    private const string LOGOUT_MUTATION = <<<'GRAPHQL'
        mutation Logout {
            logout {
                loggedOut
            }
        }
        GRAPHQL;

    private const string CREATE_ORGANIZATION_MUTATION = <<<'GRAPHQL'
        mutation CreateOrganization($input: CreateOrganizationInput!) {
            createOrganization(input: $input) {
                id
                name
            }
        }
        GRAPHQL;

    private const string CREATE_FACILITY_MUTATION = <<<'GRAPHQL'
        mutation CreateFacility($input: CreateFacilityInput!) {
            createFacility(input: $input) {
                id
                organizationId
            }
        }
        GRAPHQL;

    private const string ORGANIZATION_QUERY = <<<'GRAPHQL'
        query Organization($id: ID!) {
            organization(id: $id) {
                id
                name
            }
        }
        GRAPHQL;

    private const string RESOLVED_SETTINGS_QUERY = <<<'GRAPHQL'
        query ResolvedSettings($input: ResolveOperationalSettingsInput!) {
            resolvedOperationalSettings(input: $input) {
                timezone
            }
        }
        GRAPHQL;

    private const string ASSIGN_MEMBERSHIP_MUTATION = <<<'GRAPHQL'
        mutation AssignOrganizationMembership($input: AssignOrganizationMembershipInput!) {
            assignOrganizationMembership(input: $input) {
                userId
                organizationId
                role
                status
                allFacilities
                facilityIds
            }
        }
        GRAPHQL;

    private const string REVOKE_MEMBERSHIP_MUTATION = <<<'GRAPHQL'
        mutation RevokeOrganizationMembership($input: RevokeOrganizationMembershipInput!) {
            revokeOrganizationMembership(input: $input) {
                userId
                organizationId
                status
            }
        }
        GRAPHQL;

    public function test_first_party_session_login_and_logout_use_the_web_guard(): void
    {
        $user = User::factory()->create([
            'email' => 'clinician@example.test',
        ]);

        $login = $this->postGraphQL(
            self::LOGIN_MUTATION,
            'test-session-login',
            [
                'input' => [
                    'email' => 'clinician@example.test',
                    'password' => 'password',
                ],
            ],
        );

        $login
            ->assertOk()
            ->assertJsonPath('data.login.email', 'clinician@example.test');
        $this->assertAuthenticatedAs($user, 'web');

        $currentUser = $this->postGraphQL(
            self::CURRENT_USER_QUERY,
            'test-current-user',
        );
        $currentUser
            ->assertOk()
            ->assertJsonPath('data.me.email', 'clinician@example.test');

        $logout = $this->postGraphQL(
            self::LOGOUT_MUTATION,
            'test-session-logout',
        );

        $logout
            ->assertOk()
            ->assertJsonPath('data.logout.loggedOut', true);
        $this->assertGuest('web');
    }

    public function test_organization_query_requires_authentication(): void
    {
        $response = $this->postGraphQL(
            self::ORGANIZATION_QUERY,
            'test-authentication-required',
            ['id' => '01J00000000000000000000000'],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'test-authentication-required');
    }

    public function test_organization_creator_receives_owner_membership(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $organizationId = $this->createOrganization('owner-membership', 'Owner Membership');

        $this->assertDatabaseHas('organization_memberships', [
            'user_id' => $user->getKey(),
            'organization_id' => $organizationId,
            'role' => OrganizationRole::OWNER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => true,
        ]);
    }

    public function test_cross_tenant_organization_access_is_denied(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA, 'web');
        $organizationA = $this->createOrganization('tenant-a', 'Tenant A');

        $this->actingAs($userB, 'web');
        $organizationB = $this->createOrganization('tenant-b', 'Tenant B');

        $this->actingAs($userA, 'web');
        $allowed = $this->postGraphQL(
            self::ORGANIZATION_QUERY,
            'test-own-tenant',
            ['id' => $organizationA],
        );
        $allowed
            ->assertOk()
            ->assertJsonPath('data.organization.id', $organizationA);

        $denied = $this->postGraphQL(
            self::ORGANIZATION_QUERY,
            'test-cross-tenant-denial',
            ['id' => $organizationB],
        );
        $denied
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'test-cross-tenant-denial');
    }

    public function test_selected_facility_scope_is_enforced_and_revocation_takes_effect(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $this->actingAs($owner, 'web');

        $organizationId = $this->createOrganization('facility-scope', 'Facility Scope');
        $facilityA = $this->createFacility($organizationId, 'FAC-A', 'Facility A');
        $facilityB = $this->createFacility($organizationId, 'FAC-B', 'Facility B');

        $assigned = $this->postGraphQL(
            self::ASSIGN_MEMBERSHIP_MUTATION,
            'test-assign-facility-membership',
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'userId' => (string) $staff->getKey(),
                    'role' => OrganizationRole::STAFF->value,
                    'allFacilities' => false,
                    'facilityIds' => [$facilityA],
                ],
            ],
        );
        $assigned
            ->assertOk()
            ->assertJsonPath('data.assignOrganizationMembership.status', MembershipStatus::ACTIVE->value)
            ->assertJsonPath('data.assignOrganizationMembership.facilityIds.0', $facilityA);

        $this->actingAs($staff, 'web');
        $allowed = $this->postGraphQL(
            self::RESOLVED_SETTINGS_QUERY,
            'test-selected-facility-allowed',
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityA,
                ],
            ],
        );
        $allowed->assertOk()->assertJsonMissingPath('errors');

        $denied = $this->postGraphQL(
            self::RESOLVED_SETTINGS_QUERY,
            'test-unselected-facility-denied',
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityB,
                ],
            ],
        );
        $denied
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'test-unselected-facility-denied');

        $this->actingAs($owner, 'web');
        $revoked = $this->postGraphQL(
            self::REVOKE_MEMBERSHIP_MUTATION,
            'test-revoke-membership',
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'userId' => (string) $staff->getKey(),
                ],
            ],
        );
        $revoked
            ->assertOk()
            ->assertJsonPath('data.revokeOrganizationMembership.status', MembershipStatus::REVOKED->value);

        $this->actingAs($staff, 'web');
        $afterRevocation = $this->postGraphQL(
            self::RESOLVED_SETTINGS_QUERY,
            'test-revoked-membership-denied',
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'facilityId' => $facilityA,
                ],
            ],
        );
        $afterRevocation
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.0.extensions.correlationId', 'test-revoked-membership-denied');
    }

    private function createOrganization(string $slug, string $name): string
    {
        $response = $this->postGraphQL(
            self::CREATE_ORGANIZATION_MUTATION,
            'test-create-'.$slug,
            [
                'input' => [
                    'name' => $name,
                    'slug' => $slug,
                    'countryCode' => 'IN',
                ],
            ],
        );
        $response->assertOk()->assertJsonMissingPath('errors');

        $organizationId = $response->json('data.createOrganization.id');
        self::assertIsString($organizationId);

        return $organizationId;
    }

    private function createFacility(string $organizationId, string $code, string $name): string
    {
        $response = $this->postGraphQL(
            self::CREATE_FACILITY_MUTATION,
            'test-create-'.strtolower($code),
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'name' => $name,
                    'code' => $code,
                ],
            ],
        );
        $response->assertOk()->assertJsonMissingPath('errors');

        $facilityId = $response->json('data.createFacility.id');
        self::assertIsString($facilityId);

        return $facilityId;
    }
}
