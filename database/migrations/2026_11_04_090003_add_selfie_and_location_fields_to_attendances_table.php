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
            $table->string('clock_in_photo_path', 255)->nullable()->after('clock_in');
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_photo_path');
            $table->decimal('clock_in_longitude', 11, 7)->nullable()->after('clock_in_latitude');
            $table->unsignedMediumInteger('clock_in_distance_meters')->nullable()->after('clock_in_longitude');
            $table->foreignId('clock_in_location_id')
                ->nullable()
                ->after('clock_in_distance_meters')
                ->constrained('work_locations')
                ->nullOnDelete();

            $table->string('clock_out_photo_path', 255)->nullable()->after('clock_out');
            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_photo_path');
            $table->decimal('clock_out_longitude', 11, 7)->nullable()->after('clock_out_latitude');
            $table->unsignedMediumInteger('clock_out_distance_meters')->nullable()->after('clock_out_longitude');
            $table->foreignId('clock_out_location_id')
                ->nullable()
                ->after('clock_out_distance_meters')
                ->constrained('work_locations')
                ->nullOnDelete();

            $table->string('review_status', 20)->default('approved')->after('notes');
            $table->text('review_notes')->nullable()->after('review_status');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('review_notes')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->string('device_info', 255)->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clock_in_location_id');
            $table->dropConstrainedForeignId('clock_out_location_id');
            $table->dropConstrainedForeignId('reviewed_by');

            $table->dropColumn([
                'clock_in_photo_path',
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_in_distance_meters',
                'clock_out_photo_path',
                'clock_out_latitude',
                'clock_out_longitude',
                'clock_out_distance_meters',
                'review_status',
                'review_notes',
                'reviewed_at',
                'device_info',
            ]);
        });
    }
};
