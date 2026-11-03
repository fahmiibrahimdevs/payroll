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
            $table->decimal('overtime_meal_threshold_hours', 4, 2)->default(3.00)->nullable()->after('overtime_rounding_threshold_minutes');
            $table->decimal('overtime_meal_allowance', 15, 2)->default(15000.00)->nullable()->after('overtime_meal_threshold_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'overtime_meal_threshold_hours',
                'overtime_meal_allowance',
            ]);
        });
    }
};
