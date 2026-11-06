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
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('work_location_id')
                ->nullable()
                ->after('department_id')
                ->constrained('work_locations')
                ->nullOnDelete();
            $table->boolean('is_free_location')
                ->default(false)
                ->after('work_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_location_id');
            $table->dropColumn('is_free_location');
        });
    }
};
