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
            $table->unsignedTinyInteger('work_days_per_week')
                ->default(6)
                ->after('tax_identification_number')
                ->comment('Standard weekly work days for prorate calculations: 5 (Mon-Fri) or 6 (Mon-Sat)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('work_days_per_week');
        });
    }
};
