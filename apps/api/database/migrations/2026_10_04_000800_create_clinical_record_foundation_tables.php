<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_problems', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('encounter_id')->constrained('encounters')->restrictOnDelete();
            $table->string('code_system', 255)->nullable();
            $table->string('code', 128)->nullable();
            $table->string('display', 500);
            $table->string('status', 32);
            $table->date('onset_date')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('recorded_at');
            $table->timestampsTz();

            $table->index(['organization_id', 'patient_id', 'status'], 'clinical_problems_patient_status');
            $table->index(['encounter_id', 'recorded_at'], 'clinical_problems_encounter_timeline');
        });

        Schema::create('patient_allergies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('encounter_id')->constrained('encounters')->restrictOnDelete();
            $table->string('code_system', 255)->nullable();
            $table->string('code', 128)->nullable();
            $table->string('substance', 500);
            $table->string('reaction', 500)->nullable();
            $table->string('severity', 32);
            $table->string('status', 32);
            $table->string('verification_status', 32);
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('recorded_at');
            $table->timestampsTz();

            $table->index(['organization_id', 'patient_id', 'status'], 'patient_allergies_patient_status');
            $table->index(['encounter_id', 'recorded_at'], 'patient_allergies_encounter_timeline');
        });

        Schema::create('clinical_observations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('encounter_id')->constrained('encounters')->restrictOnDelete();
            $table->string('code_system', 255)->nullable();
            $table->string('code', 128);
            $table->string('display', 255);
            $table->decimal('value_numeric', 14, 4)->nullable();
            $table->text('value_text')->nullable();
            $table->string('unit', 64)->nullable();
            $table->timestampTz('effective_at');
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('recorded_at');
            $table->timestampsTz();

            $table->index(['organization_id', 'patient_id', 'effective_at'], 'clinical_observations_patient_timeline');
            $table->index(['encounter_id', 'effective_at'], 'clinical_observations_encounter_timeline');
        });

        Schema::create('clinical_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('encounter_id')->constrained('encounters')->restrictOnDelete();
            $table->string('note_type', 64);
            $table->string('title', 255)->nullable();
            $table->text('body');
            $table->string('status', 32);
            $table->foreignId('author_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('signed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('signed_at')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'patient_id', 'created_at'], 'clinical_notes_patient_timeline');
            $table->index(['encounter_id', 'status'], 'clinical_notes_encounter_status');
        });

        Schema::create('clinical_note_amendments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('clinical_note_id')->constrained('clinical_notes')->restrictOnDelete();
            $table->string('type', 32);
            $table->text('body');
            $table->text('reason')->nullable();
            $table->foreignId('author_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['clinical_note_id', 'created_at'], 'clinical_note_amendments_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_note_amendments');
        Schema::dropIfExists('clinical_notes');
        Schema::dropIfExists('clinical_observations');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('clinical_problems');
    }
};
