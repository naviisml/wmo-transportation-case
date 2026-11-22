<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE wmo_authorizations ADD CONSTRAINT wmo_authorization_resident_snapshot UNIQUE (id, resident_id)');
        DB::statement('ALTER TABLE budget_periods ADD CONSTRAINT budget_period_authorization_snapshot UNIQUE (id, wmo_authorization_id)');
        DB::statement('ALTER TABLE addresses ADD CONSTRAINT address_resident_parcel_snapshot UNIQUE (resident_id, parcel_id)');

        DB::statement('ALTER TABLE rides ADD COLUMN wmo_authorization_id bigint');
        DB::statement('UPDATE rides r SET wmo_authorization_id = bp.wmo_authorization_id FROM budget_periods bp WHERE bp.id = r.budget_period_id');
        DB::statement('ALTER TABLE rides ALTER COLUMN wmo_authorization_id SET NOT NULL');

        DB::statement('ALTER TABLE rides ADD CONSTRAINT ride_budget_authorization FOREIGN KEY (budget_period_id, wmo_authorization_id) REFERENCES budget_periods (id, wmo_authorization_id) ON UPDATE RESTRICT ON DELETE RESTRICT');
        DB::statement('ALTER TABLE rides ADD CONSTRAINT ride_authorization_resident FOREIGN KEY (wmo_authorization_id, resident_id) REFERENCES wmo_authorizations (id, resident_id) ON UPDATE RESTRICT ON DELETE RESTRICT');

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION validate_ride_snapshot() RETURNS trigger AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM budget_periods bp
                    JOIN wmo_authorizations wa ON wa.id = bp.wmo_authorization_id
                    WHERE bp.id = NEW.budget_period_id
                      AND bp.wmo_authorization_id = NEW.wmo_authorization_id
                      AND wa.resident_id = NEW.resident_id
                ) THEN
                    RAISE EXCEPTION 'Ride budget and authorization do not belong to the resident' USING ERRCODE = '23514';
                END IF;

                IF NOT EXISTS (
                    SELECT 1
                    FROM addresses a
                    WHERE a.resident_id = NEW.resident_id
                      AND a.parcel_id = NEW.parcel_id
                ) THEN
                    RAISE EXCEPTION 'Ride parcel does not match the resident address' USING ERRCODE = '23514';
                END IF;

                IF NOT EXISTS (
                    SELECT 1
                    FROM parcel_assignments pa
                    WHERE pa.id = NEW.parcel_assignment_id
                      AND pa.parcel_id = NEW.parcel_id
                      AND pa.taxi_company_id = NEW.taxi_company_id
                ) THEN
                    RAISE EXCEPTION 'Ride assignment does not match the parcel and taxi snapshot' USING ERRCODE = '23514';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
            SQL
        );
        DB::statement('DROP TRIGGER IF EXISTS rides_validate_snapshot ON rides');
        DB::statement('CREATE TRIGGER rides_validate_snapshot BEFORE INSERT OR UPDATE ON rides FOR EACH ROW EXECUTE FUNCTION validate_ride_snapshot()');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS rides_validate_snapshot ON rides');
        DB::statement('DROP FUNCTION IF EXISTS validate_ride_snapshot()');
        DB::statement('ALTER TABLE rides DROP CONSTRAINT IF EXISTS ride_authorization_resident');
        DB::statement('ALTER TABLE rides DROP CONSTRAINT IF EXISTS ride_budget_authorization');
        DB::statement('ALTER TABLE rides DROP COLUMN IF EXISTS wmo_authorization_id');

        DB::statement('ALTER TABLE addresses DROP CONSTRAINT IF EXISTS address_resident_parcel_snapshot');
        DB::statement('ALTER TABLE budget_periods DROP CONSTRAINT IF EXISTS budget_period_authorization_snapshot');
        DB::statement('ALTER TABLE wmo_authorizations DROP CONSTRAINT IF EXISTS wmo_authorization_resident_snapshot');
    }
};
