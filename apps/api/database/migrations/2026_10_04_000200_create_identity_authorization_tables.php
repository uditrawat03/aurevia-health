<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->string('status', 16);
            $table->boolean('all_facilities')->default(false);
            $table->timestampsTz();
            $table->unique(['user_id', 'organization_id']);
        });

        Schema::create('organization_membership_facilities', function (Blueprint $table): void {
            $table->foreignUlid('organization_membership_id')
                ->constrained('organization_memberships')
                ->cascadeOnDelete();
            $table->foreignUlid('facility_id')->constrained()->cascadeOnDelete();
            $table->primary(['organization_membership_id', 'facility_id'], 'membership_facility_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_membership_facilities');
        Schema::dropIfExists('organization_memberships');
    }
};
