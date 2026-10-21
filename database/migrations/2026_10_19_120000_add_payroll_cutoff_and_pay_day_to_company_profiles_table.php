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
            $table->unsignedTinyInteger('payroll_cutoff_day')
                ->default(20)
                ->after('work_days_per_week')
                ->comment('Monthly closing date for payroll data extraction (1-31, default 20)');

            $table->unsignedTinyInteger('payroll_pay_day')
                ->default(25)
                ->after('payroll_cutoff_day')
                ->comment('Default monthly salary disbursement date (1-31, default 25)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn(['payroll_cutoff_day', 'payroll_pay_day']);
        });
    }
};
