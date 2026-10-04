<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Aurevia development demo data is seeded only in the local environment.');

            return;
        }

        $this->call(DevelopmentDemoSeeder::class);
    }
}
