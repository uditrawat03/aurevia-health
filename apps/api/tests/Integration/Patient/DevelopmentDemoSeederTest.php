<?php

declare(strict_types=1);

namespace Tests\Integration\Patient;

use App\Models\User;
use Database\Seeders\DevelopmentDemoSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\IntegrationTestCase;

final class DevelopmentDemoSeederTest extends IntegrationTestCase
{
    public function test_development_seeder_creates_login_workspace_and_synthetic_patients(): void
    {
        app(DevelopmentDemoSeeder::class)->run();

        $this->assertDatabaseHas('users', [
            'email' => DevelopmentDemoSeeder::OWNER_EMAIL,
        ]);
        $user = User::query()
            ->where('email', DevelopmentDemoSeeder::OWNER_EMAIL)
            ->firstOrFail();
        self::assertTrue(Hash::check(
            DevelopmentDemoSeeder::OWNER_PASSWORD,
            (string) $user->getAttribute('password'),
        ));

        $this->assertDatabaseHas('organizations', [
            'slug' => 'aurevia-demo-health',
        ]);
        $this->assertDatabaseHas('organization_memberships', [
            'role' => 'OWNER',
            'status' => 'ACTIVE',
            'all_facilities' => true,
        ]);
        $this->assertDatabaseHas('patient_identifiers', [
            'system' => 'aurevia-demo-mrn',
            'normalized_value' => 'DEMO0001',
        ]);
        $this->assertDatabaseCount('patients', 2);
    }
}
