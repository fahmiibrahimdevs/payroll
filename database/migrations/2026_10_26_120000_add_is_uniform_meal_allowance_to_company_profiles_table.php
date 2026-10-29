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
            if (! Schema::hasColumn('company_profiles', 'is_uniform_meal_allowance')) {
                $table->boolean('is_uniform_meal_allowance')->default(true)->after('default_meal_allowance_per_day');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('company_profiles', 'is_uniform_meal_allowance')) {
                $table->dropColumn('is_uniform_meal_allowance');
            }
        });
    }
};
