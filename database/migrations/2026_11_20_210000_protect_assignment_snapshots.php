<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE parcel_assignments ADD CONSTRAINT parcel_assignment_snapshot UNIQUE (id, parcel_id, taxi_company_id)');
        DB::statement('ALTER TABLE rides ADD CONSTRAINT ride_assignment_snapshot FOREIGN KEY (parcel_assignment_id, parcel_id, taxi_company_id) REFERENCES parcel_assignments (id, parcel_id, taxi_company_id) ON UPDATE RESTRICT ON DELETE RESTRICT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE rides DROP CONSTRAINT ride_assignment_snapshot');
        DB::statement('ALTER TABLE parcel_assignments DROP CONSTRAINT parcel_assignment_snapshot');
    }
};
