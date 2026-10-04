<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 160);
            $table->string('slug', 120)->unique();
            $table->char('country_code', 2);
            $table->string('country_profile_code', 32);
            $table->string('country_profile_version', 32);
            $table->string('locale_override', 35)->nullable();
            $table->string('timezone_override', 64)->nullable();
            $table->string('week_starts_on_override', 16)->nullable();
            $table->timestampsTz();
        });

        Schema::create('health_systems', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('code', 64);
            $table->timestampsTz();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('facilities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('health_system_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('code', 64);
            $table->string('locale_override', 35)->nullable();
            $table->string('timezone_override', 64)->nullable();
            $table->string('week_starts_on_override', 16)->nullable();
            $table->timestampsTz();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('code', 64);
            $table->string('locale_override', 35)->nullable();
            $table->string('timezone_override', 64)->nullable();
            $table->string('week_starts_on_override', 16)->nullable();
            $table->timestampsTz();
            $table->unique(['facility_id', 'code']);
        });

        Schema::create('configuration_changes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type', 32);
            $table->ulid('scope_id');
            $table->string('setting_key', 64);
            $table->string('previous_value')->nullable();
            $table->string('new_value')->nullable();
            $table->string('correlation_id', 64);
            $table->timestampTz('changed_at');
            $table->index(['organization_id', 'scope_type', 'scope_id'], 'configuration_change_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_changes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('health_systems');
        Schema::dropIfExists('organizations');
    }
};
