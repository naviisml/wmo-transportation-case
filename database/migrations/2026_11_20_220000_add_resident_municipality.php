<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('municipality', 80)->nullable();
            $table->index(['municipality', 'id']);
        });

        DB::table('residents')
            ->whereIn('id', DB::table('addresses')->select('resident_id')->where('city', 'ilike', 'Utrecht'))
            ->update(['municipality' => 'utrecht']);
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropIndex(['municipality', 'id']);
            $table->dropColumn('municipality');
        });
    }
};
