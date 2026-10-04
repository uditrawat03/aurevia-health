<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->ulid('organization_id')->nullable();
            $table->ulid('facility_id')->nullable();
            $table->ulid('patient_id')->nullable();
            $table->string('resource_type', 64);
            $table->string('resource_id', 128)->nullable();
            $table->string('action', 96);
            $table->string('outcome', 32);
            $table->string('correlation_id', 64);
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['organization_id', 'occurred_at'], 'audit_event_org_time_idx');
            $table->index(['actor_user_id', 'occurred_at'], 'audit_event_actor_time_idx');
            $table->index(['resource_type', 'resource_id'], 'audit_event_resource_idx');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
