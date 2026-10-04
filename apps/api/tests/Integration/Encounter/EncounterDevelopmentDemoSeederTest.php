<?php

declare(strict_types=1);

namespace Tests\Integration\Encounter;

use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentStatus;
use Database\Seeders\DevelopmentDemoSeeder;
use Tests\IntegrationTestCase;

final class EncounterDevelopmentDemoSeederTest extends IntegrationTestCase
{
    public function test_demo_seeder_creates_clinical_privacy_scenarios_for_encounter_testing(): void
    {
        app(DevelopmentDemoSeeder::class)->run();

        $this->assertDatabaseHas('patient_consents', [
            'data_category' => ConsentDataCategory::CLINICAL->value,
            'status' => ConsentStatus::ACTIVE->value,
            'revocation_reason' => null,
        ]);
        $this->assertDatabaseHas('patient_consents', [
            'data_category' => ConsentDataCategory::CLINICAL->value,
            'status' => ConsentStatus::REVOKED->value,
            'revocation_reason' => 'Synthetic revoked-consent scenario',
        ]);
    }
}
