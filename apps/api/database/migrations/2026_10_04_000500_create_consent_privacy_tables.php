<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_consents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('facility_id')->nullable()->constrained('facilities')->restrictOnDelete();
            $table->string('data_category', 30);
            $table->string('purpose', 40);
            $table->string('recipient_class', 40);
            $table->string('status', 20);
            $table->foreignId('granted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('effective_from');
            $table->timestampTz('effective_until')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestampsTz();

            $table->index(
                ['organization_id', 'patient_id', 'status'],
                'patient_consents_patient_status',
            );
            $table->index(
                ['patient_id', 'data_category', 'purpose', 'recipient_class'],
                'patient_consents_policy_lookup',
            );
            $table->index(['facility_id', 'effective_from', 'effective_until'], 'patient_consents_effective');
        });

        Schema::create('break_glass_accesses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('purpose', 40);
            $table->text('reason');
            $table->timestampTz('activated_at');
            $table->timestampTz('expires_at');
            $table->timestampsTz();

            $table->index(
                ['actor_user_id', 'patient_id', 'purpose', 'expires_at'],
                'break_glass_active_lookup',
            );
            $table->index(['organization_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('break_glass_accesses');
        Schema::dropIfExists('patient_consents');
    }
};
