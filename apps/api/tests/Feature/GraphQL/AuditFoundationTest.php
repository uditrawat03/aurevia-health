<?php

declare(strict_types=1);

namespace Tests\Feature\GraphQL;

use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditOutcome;
use App\Domains\Identity\Enums\MembershipStatus;
use App\Domains\Identity\Enums\OrganizationRole;
use App\Models\OrganizationMembership;
use App\Models\User;
use Tests\IntegrationTestCase;
use Tests\Support\InteractsWithGraphQL;

final class AuditFoundationTest extends IntegrationTestCase
{
    use InteractsWithGraphQL;

    private const string CREATE_ORGANIZATION_MUTATION = <<<'GRAPHQL'
        mutation CreateOrganization($input: CreateOrganizationInput!) {
            createOrganization(input: $input) {
                id
                name
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

    private const string CREATE_HEALTH_SYSTEM_MUTATION = <<<'GRAPHQL'
        mutation CreateHealthSystem($input: CreateHealthSystemInput!) {
            createHealthSystem(input: $input) {
                id
            }
        }
        GRAPHQL;

    private const string AUDIT_EVENTS_QUERY = <<<'GRAPHQL'
        query AuditEvents($input: AuditEventsInput!) {
            auditEvents(input: $input) {
                actorUserId
                organizationId
                resourceType
                resourceId
                action
                outcome
                correlationId
                occurredAt
            }
        }
        GRAPHQL;

    public function test_allowed_protected_read_creates_structured_audit_evidence(): void
    {
        $owner = User::factory()->create();
        $organizationId = $this->createOrganization($owner, 'audit-read-owner');
        $correlationId = 'audit-allowed-organization-read';

        $this->actingAs($owner, 'web');
        $response = $this->postGraphQL(
            self::ORGANIZATION_QUERY,
            $correlationId,
            ['id' => $organizationId],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.organization.id', $organizationId);

        $this->assertDatabaseHas('audit_events', [
            'actor_user_id' => $owner->getKey(),
            'organization_id' => $organizationId,
            'resource_type' => 'ORGANIZATION',
            'resource_id' => $organizationId,
            'action' => AuditAction::VIEW_ORGANIZATION->value,
            'outcome' => AuditOutcome::ALLOWED->value,
            'correlation_id' => $correlationId,
        ]);
    }

    public function test_denied_mutation_is_audited_after_transaction_rollback(): void
    {
        $owner = User::factory()->create();
        $organizationId = $this->createOrganization($owner, 'audit-denial-owner');
        $intruder = User::factory()->create();
        $correlationId = 'audit-denied-health-system-create';

        $this->actingAs($intruder, 'web');
        $response = $this->postGraphQL(
            self::CREATE_HEALTH_SYSTEM_MUTATION,
            $correlationId,
            [
                'input' => [
                    'organizationId' => $organizationId,
                    'name' => 'Denied Synthetic Health System',
                    'code' => 'DENIED-SYNTH',
                ],
            ],
        );

        $response
            ->assertOk()
            ->assertJsonStructure(['errors']);

        $this->assertDatabaseMissing('health_systems', [
            'organization_id' => $organizationId,
            'code' => 'DENIED-SYNTH',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'actor_user_id' => $intruder->getKey(),
            'organization_id' => $organizationId,
            'resource_type' => 'ORGANIZATION',
            'resource_id' => $organizationId,
            'action' => AuditAction::MANAGE_ORGANIZATION->value,
            'outcome' => AuditOutcome::DENIED->value,
            'correlation_id' => $correlationId,
        ]);
    }

    public function test_audit_viewer_is_restricted_and_is_itself_audited(): void
    {
        $owner = User::factory()->create();
        $organizationId = $this->createOrganization($owner, 'audit-viewer-owner');
        $viewer = User::factory()->create();

        OrganizationMembership::query()->create([
            'user_id' => $viewer->getKey(),
            'organization_id' => $organizationId,
            'role' => OrganizationRole::VIEWER->value,
            'status' => MembershipStatus::ACTIVE->value,
            'all_facilities' => true,
        ]);

        $this->actingAs($owner, 'web');
        $allowed = $this->postGraphQL(
            self::AUDIT_EVENTS_QUERY,
            'audit-owner-view',
            ['input' => ['organizationId' => $organizationId, 'limit' => 25]],
        );

        $allowed->assertOk();
        $events = $allowed->json('data.auditEvents');
        self::assertIsArray($events);
        self::assertTrue($this->containsAuditAction($events, AuditAction::VIEW_AUDIT, AuditOutcome::ALLOWED));

        $this->actingAs($viewer, 'web');
        $denied = $this->postGraphQL(
            self::AUDIT_EVENTS_QUERY,
            'audit-viewer-denied',
            ['input' => ['organizationId' => $organizationId]],
        );

        $denied
            ->assertOk()
            ->assertJsonStructure(['errors']);

        $this->assertDatabaseHas('audit_events', [
            'actor_user_id' => $viewer->getKey(),
            'organization_id' => $organizationId,
            'action' => AuditAction::VIEW_AUDIT->value,
            'outcome' => AuditOutcome::DENIED->value,
            'correlation_id' => 'audit-viewer-denied',
        ]);
    }

    private function createOrganization(User $owner, string $slug): string
    {
        $this->actingAs($owner, 'web');
        $response = $this->postGraphQL(
            self::CREATE_ORGANIZATION_MUTATION,
            'audit-create-'.$slug,
            [
                'input' => [
                    'name' => 'Synthetic '.str_replace('-', ' ', $slug),
                    'slug' => $slug,
                    'countryCode' => 'IN',
                ],
            ],
        );

        $response->assertOk();
        $organizationId = $response->json('data.createOrganization.id');
        self::assertIsString($organizationId);

        $this->assertDatabaseHas('audit_events', [
            'actor_user_id' => $owner->getKey(),
            'organization_id' => $organizationId,
            'action' => AuditAction::CREATE_ORGANIZATION->value,
            'outcome' => AuditOutcome::ALLOWED->value,
        ]);

        return $organizationId;
    }

    /**
     * @param list<array<string, mixed>> $events
     */
    private function containsAuditAction(
        array $events,
        AuditAction $action,
        AuditOutcome $outcome,
    ): bool {
        foreach ($events as $event) {
            $matches = ($event['action'] ?? null) === $action->value
                && ($event['outcome'] ?? null) === $outcome->value;
            if ($matches) {
                return true;
            }
        }

        return false;
    }
}
