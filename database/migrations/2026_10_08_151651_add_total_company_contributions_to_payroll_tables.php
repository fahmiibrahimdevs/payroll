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
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->decimal('total_company_contributions', 15, 2)->default(0)->after('total_net_salary');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('total_company_contributions', 15, 2)->default(0)->after('pph21_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropColumn('total_company_contributions');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn('total_company_contributions');
        });
    }
};
