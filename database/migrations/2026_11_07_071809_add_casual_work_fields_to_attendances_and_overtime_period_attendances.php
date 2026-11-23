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
            $table->string('casual_work_type', 20)->nullable()->after('review_status');
            $table->text('early_departure_reason')->nullable()->after('casual_work_type');
            $table->decimal('casual_pay_override', 14, 2)->nullable()->after('early_departure_reason');
            $table->text('casual_pay_reason')->nullable()->after('casual_pay_override');
        });

        Schema::table('overtime_period_attendances', function (Blueprint $table) {
            $table->decimal('total_hourly_work_pay', 14, 2)->default(0)->after('total_daily_wage');
        });

        Schema::table('overtime_periods', function (Blueprint $table) {
            $table->decimal('total_hourly_work_pay', 15, 2)->default(0)->after('total_daily_wage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_periods', function (Blueprint $table) {
            $table->dropColumn('total_hourly_work_pay');
        });

        Schema::table('overtime_period_attendances', function (Blueprint $table) {
            $table->dropColumn('total_hourly_work_pay');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'casual_work_type',
                'early_departure_reason',
                'casual_pay_override',
                'casual_pay_reason',
            ]);
        });
    }
};
