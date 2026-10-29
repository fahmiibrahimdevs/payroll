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
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->decimal('default_meal_allowance_per_day', 12, 2)->default(25000)->after('work_days_per_week');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('meal_allowance_type', 20)->default('standar')->after('overtime_rate_per_hour'); // standar, khusus
            $table->decimal('meal_allowance_per_day', 12, 2)->default(25000)->after('meal_allowance_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('default_meal_allowance_per_day');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['meal_allowance_type', 'meal_allowance_per_day']);
        });
    }
};
