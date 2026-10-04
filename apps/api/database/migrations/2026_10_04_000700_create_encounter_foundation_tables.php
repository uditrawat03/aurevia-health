<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUlid('appointment_id')->nullable()->constrained('appointments')->restrictOnDelete();
            $table->string('type', 32);
            $table->string('status', 32);
            $table->timestampTz('arrived_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'appointment_id'], 'encounters_appointment_unique');
            $table->index(['organization_id', 'facility_id', 'status'], 'encounters_facility_status');
            $table->index(['organization_id', 'patient_id', 'created_at'], 'encounters_patient_timeline');
        });

        Schema::create('encounter_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('encounter_id')->constrained('encounters')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('reason')->nullable();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('occurred_at');

            $table->index(['encounter_id', 'occurred_at'], 'encounter_events_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_events');
        Schema::dropIfExists('encounters');
    }
};
