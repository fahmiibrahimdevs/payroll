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
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedInteger('clock_in_accuracy_meters')->nullable()->after('clock_in_distance_meters');
            $table->unsignedInteger('clock_out_accuracy_meters')->nullable()->after('clock_out_distance_meters');
            $table->unsignedInteger('overtime_in_accuracy_meters')->nullable()->after('overtime_in_distance_meters');
            $table->unsignedInteger('overtime_out_accuracy_meters')->nullable()->after('overtime_out_distance_meters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_accuracy_meters',
                'clock_out_accuracy_meters',
                'overtime_in_accuracy_meters',
                'overtime_out_accuracy_meters',
            ]);
        });
    }
};
