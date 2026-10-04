<?php

declare(strict_types=1);

namespace Tests\Integration\Scheduling;

use Database\Seeders\DevelopmentDemoSeeder;
use Tests\IntegrationTestCase;

final class SchedulingDevelopmentDemoSeederTest extends IntegrationTestCase
{
    public function test_demo_seeder_creates_bookable_scheduling_configuration(): void
    {
        app(DevelopmentDemoSeeder::class)->run();

        $this->assertDatabaseHas('appointment_types', [
            'code' => 'GEN-CONSULT',
            'duration_minutes' => 30,
            'active' => true,
        ]);
        $this->assertDatabaseHas('scheduling_resources', [
            'code' => 'PROV-MIRA-SEN',
            'type' => 'PROVIDER',
            'active' => true,
        ]);
        $this->assertDatabaseHas('scheduling_resources', [
            'code' => 'ROOM-CONSULT-1',
            'type' => 'ROOM',
            'active' => true,
        ]);
    }
}
