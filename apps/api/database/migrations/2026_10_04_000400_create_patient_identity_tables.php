<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('registration_facility_id')->constrained('facilities')->restrictOnDelete();
            $table->string('given_name', 120);
            $table->string('normalized_given_name', 120);
            $table->string('middle_name', 120)->nullable();
            $table->string('family_name', 120);
            $table->string('normalized_family_name', 120);
            $table->string('preferred_name', 120)->nullable();
            $table->date('date_of_birth');
            $table->string('sex_at_birth', 20);
            $table->timestampsTz();

            $table->index(['organization_id', 'registration_facility_id']);
            $table->index(['organization_id', 'normalized_family_name', 'date_of_birth'], 'patients_identity_lookup');
        });

        Schema::create('patient_identifiers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('system', 120);
            $table->string('value', 255);
            $table->string('normalized_value', 255);
            $table->timestampsTz();

            $table->index(['organization_id', 'normalized_value'], 'patient_identifiers_lookup');
            $table->index(['patient_id', 'type']);
        });

        Schema::create('patient_contacts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('value', 255);
            $table->boolean('preferred')->default(false);
            $table->timestampsTz();
        });

        Schema::create('patient_addresses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('use', 20);
            $table->string('line1', 255);
            $table->string('line2', 255)->nullable();
            $table->string('city', 120);
            $table->string('region', 120)->nullable();
            $table->string('postal_code', 40)->nullable();
            $table->char('country_code', 2);
            $table->boolean('preferred')->default(false);
            $table->timestampsTz();
        });

        Schema::create('patient_relationships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('name', 200);
            $table->string('phone', 80)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('legal_guardian')->default(false);
            $table->boolean('emergency_contact')->default(false);
            $table->timestampsTz();
        });

        Schema::create('patient_merge_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('source_patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('target_patient_id')->constrained('patients')->restrictOnDelete();
            $table->string('status', 20);
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'status']);
            $table->index(['source_patient_id', 'target_patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_merge_reviews');
        Schema::dropIfExists('patient_relationships');
        Schema::dropIfExists('patient_addresses');
        Schema::dropIfExists('patient_contacts');
        Schema::dropIfExists('patient_identifiers');
        Schema::dropIfExists('patients');
    }
};
