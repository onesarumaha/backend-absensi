<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('late_tolerance');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->integer('radius_meters')->default(100)->after('longitude');
            $table->string('location_name')->nullable()->after('radius_meters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'radius_meters',
                'location_name',
            ]);
        });
    }
};
