<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
        });

        Schema::create('appointment_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 30);
            $table->timestampTz('from_starts_at')->nullable();
            $table->timestampTz('from_ends_at')->nullable();
            $table->timestampTz('to_starts_at')->nullable();
            $table->timestampTz('to_ends_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestampTz('occurred_at');

            $table->index(['appointment_id', 'occurred_at']);
            $table->index(['organization_id', 'facility_id', 'occurred_at']);
        });

        Schema::create('waitlist_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained('facilities')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('appointment_type_id')->constrained('appointment_types')->restrictOnDelete();
            $table->string('status', 20);
            $table->timestampTz('preferred_from');
            $table->timestampTz('preferred_until');
            $table->string('timezone', 80);
            $table->text('reason')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('idempotency_key', 120);
            $table->char('request_fingerprint', 64);
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'idempotency_key'], 'waitlist_org_idempotency_unique');
            $table->index(['organization_id', 'facility_id', 'status', 'preferred_from'], 'waitlist_facility_status_window');
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('appointment_events');

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['cancelled_by_user_id']);
            $table->dropColumn(['cancelled_at', 'cancelled_by_user_id', 'cancellation_reason']);
        });
    }
};
