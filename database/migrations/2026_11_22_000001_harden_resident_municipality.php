<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE residents SET municipality = 'utrecht' WHERE municipality IS NULL AND EXISTS (SELECT 1 FROM addresses WHERE addresses.resident_id = residents.id AND lower(trim(addresses.city)) = 'utrecht')");

        DB::statement("UPDATE residents SET municipality = 'utrecht' WHERE municipality IS NULL");

        DB::statement("ALTER TABLE residents ALTER COLUMN municipality SET DEFAULT 'utrecht'");
        DB::statement('ALTER TABLE residents ALTER COLUMN municipality SET NOT NULL');
        DB::statement("ALTER TABLE residents ADD CONSTRAINT residents_municipality_utrecht_check CHECK (municipality = 'utrecht')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE residents DROP CONSTRAINT IF EXISTS residents_municipality_utrecht_check');
        DB::statement('ALTER TABLE residents ALTER COLUMN municipality DROP NOT NULL');
        DB::statement('ALTER TABLE residents ALTER COLUMN municipality DROP DEFAULT');
    }
};
