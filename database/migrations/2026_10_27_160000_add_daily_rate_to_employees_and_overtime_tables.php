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
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (! Schema::hasColumn('employees', 'daily_rate')) {
                    $table->decimal('daily_rate', 14, 2)->default(0)->after('basic_salary');
                }
                $table->decimal('basic_salary', 15, 2)->nullable()->default(0)->change();
            });
        }

        if (Schema::hasTable('overtime_period_attendances')) {
            Schema::table('overtime_period_attendances', function (Blueprint $table) {
                if (! Schema::hasColumn('overtime_period_attendances', 'daily_rate')) {
                    $table->decimal('daily_rate', 14, 2)->default(0)->after('meal_allowance_per_day');
                }
                if (! Schema::hasColumn('overtime_period_attendances', 'total_daily_wage')) {
                    $table->decimal('total_daily_wage', 14, 2)->default(0)->after('total_meal_allowance');
                }
            });
        }

        if (Schema::hasTable('overtime_periods') && ! Schema::hasColumn('overtime_periods', 'total_daily_wage')) {
            Schema::table('overtime_periods', function (Blueprint $table) {
                $table->decimal('total_daily_wage', 15, 2)->default(0)->after('total_meal_allowance');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'daily_rate')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('daily_rate');
            });
        }

        if (Schema::hasTable('overtime_period_attendances')) {
            Schema::table('overtime_period_attendances', function (Blueprint $table) {
                $table->dropColumn(['daily_rate', 'total_daily_wage']);
            });
        }

        if (Schema::hasTable('overtime_periods') && Schema::hasColumn('overtime_periods', 'total_daily_wage')) {
            Schema::table('overtime_periods', function (Blueprint $table) {
                $table->dropColumn('total_daily_wage');
            });
        }
    }
};
