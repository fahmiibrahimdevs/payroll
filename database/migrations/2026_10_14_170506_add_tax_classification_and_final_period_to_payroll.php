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
            $table->string('tax_recipient_type', 40)->nullable()->after('tax_method');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->string('tax_recipient_type', 40)->nullable()->after('tax_method');
            $table->boolean('is_final_tax_period')->default(false)->after('tax_recipient_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn(['tax_recipient_type', 'is_final_tax_period']);
        });

        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropColumn('tax_recipient_type');
        });
    }
};
