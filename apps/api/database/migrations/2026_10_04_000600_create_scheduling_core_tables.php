<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_types', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name', 160);
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('active')->default(true);
            $table->timestampsTz();

            $table->unique(['organization_id', 'facility_id', 'code'], 'appointment_types_scope_code_unique');
            $table->index(['organization_id', 'facility_id', 'active']);
        });

        Schema::create('scheduling_resources', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('name', 160);
            $table->string('code', 60);
            $table->boolean('active')->default(true);
            $table->timestampsTz();

            $table->unique(['organization_id', 'facility_id', 'code'], 'scheduling_resources_scope_code_unique');
            $table->index(['organization_id', 'facility_id', 'type', 'active'], 'scheduling_resources_lookup');
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('appointment_type_id')->constrained('appointment_types')->restrictOnDelete();
            $table->string('status', 24);
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('timezone', 64);
            $table->text('reason')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('idempotency_key', 120);
            $table->char('request_fingerprint', 64);
            $table->timestampsTz();

            $table->unique(['organization_id', 'idempotency_key'], 'appointments_idempotency_unique');
            $table->index(['organization_id', 'facility_id', 'starts_at'], 'appointments_facility_calendar');
            $table->index(['organization_id', 'patient_id', 'starts_at'], 'appointments_patient_calendar');
            $table->index(['organization_id', 'status']);
        });

        Schema::create('appointment_resource_assignments', function (Blueprint $table): void {
            $table->foreignUlid('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignUlid('scheduling_resource_id')->constrained('scheduling_resources')->restrictOnDelete();

            $table->primary(['appointment_id', 'scheduling_resource_id'], 'appointment_resource_assignments_pk');
            $table->index(['scheduling_resource_id', 'appointment_id'], 'appointment_resource_assignments_resource');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_resource_assignments');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('scheduling_resources');
        Schema::dropIfExists('appointment_types');
    }
};
