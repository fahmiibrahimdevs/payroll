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
        Schema::table('employee_details', function (Blueprint $table) {
            $table->string('marital_status', 20)->default('tk')->after('gender');
            $table->unsignedTinyInteger('dependents_count')->default(0)->after('marital_status');
            $table->string('ptkp_status', 20)->default('TK/0')->after('dependents_count');
            $table->string('tax_method', 30)->default('gross')->after('ptkp_status');
            $table->boolean('has_npwp')->default(false)->after('tax_method');
            $table->string('npwp_number', 50)->nullable()->after('has_npwp');
            $table->string('nik', 50)->nullable()->after('npwp_number');
            $table->string('bpjs_kesehatan_number', 50)->nullable()->after('nik');
            $table->string('bpjs_ketenagakerjaan_number', 50)->nullable()->after('bpjs_kesehatan_number');
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->boolean('enable_pph21')->default(true)->after('tax_identification_number');
            $table->boolean('include_bpjs_in_tax')->default(true)->after('enable_pph21');
            $table->decimal('bpjs_jkk_risk_rate', 5, 4)->default(0.0024)->after('include_bpjs_in_tax');
            $table->decimal('bpjs_jkm_rate', 5, 4)->default(0.0030)->after('bpjs_jkk_risk_rate');
            $table->decimal('bpjs_kes_emp_rate', 5, 4)->default(0.0100)->after('bpjs_jkm_rate');
            $table->decimal('bpjs_kes_comp_rate', 5, 4)->default(0.0400)->after('bpjs_kes_emp_rate');
            $table->decimal('bpjs_jht_emp_rate', 5, 4)->default(0.0200)->after('bpjs_kes_comp_rate');
            $table->decimal('bpjs_jht_comp_rate', 5, 4)->default(0.0370)->after('bpjs_jht_emp_rate');
            $table->decimal('bpjs_jp_emp_rate', 5, 4)->default(0.0100)->after('bpjs_jht_comp_rate');
            $table->decimal('bpjs_jp_comp_rate', 5, 4)->default(0.0200)->after('bpjs_jp_emp_rate');
            $table->decimal('bpjs_kes_cap', 15, 2)->default(12000000.00)->after('bpjs_jp_comp_rate');
            $table->decimal('bpjs_jp_cap', 15, 2)->default(10042300.00)->after('bpjs_kes_cap');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->string('tax_method', 30)->default('gross')->after('net_salary');
            $table->string('ptkp_status', 20)->nullable()->after('tax_method');
            $table->string('ter_category', 10)->nullable()->after('ptkp_status');
            $table->decimal('ter_rate', 6, 4)->nullable()->after('ter_category');
            $table->decimal('taxable_gross', 15, 2)->default(0)->after('ter_rate');
            $table->decimal('tax_allowance', 15, 2)->default(0)->after('taxable_gross');
            $table->decimal('pph21_amount', 15, 2)->default(0)->after('tax_allowance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn([
                'tax_method',
                'ptkp_status',
                'ter_category',
                'ter_rate',
                'taxable_gross',
                'tax_allowance',
                'pph21_amount',
            ]);
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'enable_pph21',
                'include_bpjs_in_tax',
                'bpjs_jkk_risk_rate',
                'bpjs_jkm_rate',
                'bpjs_kes_emp_rate',
                'bpjs_kes_comp_rate',
                'bpjs_jht_emp_rate',
                'bpjs_jht_comp_rate',
                'bpjs_jp_emp_rate',
                'bpjs_jp_comp_rate',
                'bpjs_kes_cap',
                'bpjs_jp_cap',
            ]);
        });

        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropColumn([
                'marital_status',
                'dependents_count',
                'ptkp_status',
                'tax_method',
                'has_npwp',
                'npwp_number',
                'nik',
                'bpjs_kesehatan_number',
                'bpjs_ketenagakerjaan_number',
            ]);
        });
    }
};
