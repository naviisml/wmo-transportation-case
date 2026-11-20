<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        Schema::create('taxi_companies', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('municipality');
            $t->foreignId('taxi_company_id')->nullable()->constrained()->restrictOnDelete();
        });
        DB::statement("ALTER TABLE users ADD CHECK (role IN ('municipality','callcenter','taxi_company')), ADD CHECK ((role = 'taxi_company') = (taxi_company_id IS NOT NULL))");
        Schema::create('parcels', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('parcel_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parcel_id')->constrained()->restrictOnDelete();
            $t->foreignId('taxi_company_id')->constrained()->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on')->nullable();
            $t->timestamps();
            $t->index(['parcel_id', 'starts_on']);
        });
        DB::statement("ALTER TABLE parcel_assignments ADD CHECK (ends_on IS NULL OR ends_on > starts_on), ADD EXCLUDE USING gist (parcel_id WITH =, daterange(starts_on, ends_on, '[)') WITH &&)");
        Schema::create('residents', function (Blueprint $t) {
            $t->id();
            $t->string('name')->index();
            $t->string('reference')->unique();
            $t->timestamps();
        });
        Schema::create('addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resident_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('parcel_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('street');
            $t->string('house_number', 20);
            $t->string('postal_code', 10);
            $t->string('city')->default('Utrecht');
            $t->timestamps();
        });
        Schema::create('wmo_authorizations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resident_id')->unique()->constrained()->restrictOnDelete();
            $t->date('starts_on');
            $t->date('terminated_on')->nullable();
            $t->integer('annual_meters');
            $t->timestamps();
        });
        DB::statement('ALTER TABLE wmo_authorizations ADD CHECK (annual_meters >= 0), ADD CHECK (terminated_on IS NULL OR terminated_on >= starts_on)');
        Schema::create('budget_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wmo_authorization_id')->constrained()->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->integer('allocated_meters');
            $t->integer('consumed_meters')->default(0);
            $t->timestamps();
            $t->unique(['wmo_authorization_id', 'starts_on']);
        });
        DB::statement("ALTER TABLE budget_periods ADD CHECK (ends_on > starts_on), ADD CHECK (allocated_meters >= 0 AND consumed_meters >= 0 AND consumed_meters <= allocated_meters), ADD EXCLUDE USING gist (wmo_authorization_id WITH =, daterange(starts_on, ends_on, '[)') WITH &&)");
        Schema::create('rides', function (Blueprint $t) {
            $t->id();
            foreach (['resident', 'budget_period', 'parcel', 'parcel_assignment', 'taxi_company'] as $r) {
                $t->foreignId($r.'_id')->constrained()->restrictOnDelete();
            }$t->foreignId('booked_by')->constrained('users')->restrictOnDelete();
            $t->string('origin');
            $t->string('destination');
            $t->timestampTz('scheduled_at');
            $t->integer('distance_meters');
            $t->string('status')->default('booked');
            $t->string('idempotency_key', 128);
            $t->string('request_hash', 64);
            $t->timestamps();
            $t->unique(['booked_by', 'idempotency_key']);
            $t->index(['taxi_company_id', 'scheduled_at', 'status']);
        });
        DB::statement("ALTER TABLE rides ADD CHECK (distance_meters > 0), ADD CHECK (status IN ('booked','completed','cancelled'))");
        Schema::create('audit_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('resource_type')->nullable();
            $t->unsignedBigInteger('resource_id')->nullable();
            $t->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_entries', 'rides', 'budget_periods', 'wmo_authorizations', 'addresses', 'residents', 'parcel_assignments', 'parcels'] as $table) {
            Schema::dropIfExists($table);
        } Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('taxi_company_id');
            $t->dropColumn('role');
        });
        Schema::dropIfExists('taxi_companies');
    }
};
