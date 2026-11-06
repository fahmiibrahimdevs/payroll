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
            $table->boolean('is_overtime')->default(false)->after('overtime_out');
            $table->string('overtime_status', 30)->nullable()->after('is_overtime'); // in_progress, completed, auto_cutoff
            $table->string('overtime_in_photo_path')->nullable()->after('overtime_status');
            $table->decimal('overtime_in_latitude', 10, 7)->nullable()->after('overtime_in_photo_path');
            $table->decimal('overtime_in_longitude', 11, 7)->nullable()->after('overtime_in_latitude');
            $table->unsignedMediumInteger('overtime_in_distance_meters')->nullable()->after('overtime_in_longitude');
            $table->foreignId('overtime_in_location_id')->nullable()->after('overtime_in_distance_meters')->constrained('work_locations')->nullOnDelete();

            $table->string('overtime_out_photo_path')->nullable()->after('overtime_in_location_id');
            $table->decimal('overtime_out_latitude', 10, 7)->nullable()->after('overtime_out_photo_path');
            $table->decimal('overtime_out_longitude', 11, 7)->nullable()->after('overtime_out_latitude');
            $table->unsignedMediumInteger('overtime_out_distance_meters')->nullable()->after('overtime_out_longitude');
            $table->foreignId('overtime_out_location_id')->nullable()->after('overtime_out_distance_meters')->constrained('work_locations')->nullOnDelete();
        });

        Schema::table('overtime_submissions', function (Blueprint $table) {
            $table->foreignId('attendance_id')->nullable()->after('employee_id')->constrained('attendances')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_submissions', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
            $table->dropColumn('attendance_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['overtime_in_location_id']);
            $table->dropForeign(['overtime_out_location_id']);
            $table->dropColumn([
                'is_overtime',
                'overtime_status',
                'overtime_in_photo_path',
                'overtime_in_latitude',
                'overtime_in_longitude',
                'overtime_in_distance_meters',
                'overtime_in_location_id',
                'overtime_out_photo_path',
                'overtime_out_latitude',
                'overtime_out_longitude',
                'overtime_out_distance_meters',
                'overtime_out_location_id',
            ]);
        });
    }
};
