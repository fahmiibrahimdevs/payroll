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
        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropIndex(['calculation_type']);
            $table->dropIndex(['calculation_type', 'is_active']);
            $table->dropColumn(['calculation_type', 'default_amount', 'percentage_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_components', function (Blueprint $table) {
            $table->string('calculation_type')->default('fixed');
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->decimal('percentage_rate', 5, 2)->nullable();

            $table->index('calculation_type');
            $table->index(['calculation_type', 'is_active']);
        });
    }
};
