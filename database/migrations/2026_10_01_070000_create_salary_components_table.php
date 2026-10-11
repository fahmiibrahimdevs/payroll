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
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('type')->default('allowance'); // allowance, deduction
            $table->string('calculation_type')->default('fixed'); // fixed, percentage, manual
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->decimal('percentage_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes for optimal query performance
            $table->index('name');
            $table->index('type');
            $table->index('calculation_type');
            $table->index('is_active');
            $table->index(['type', 'is_active']);
            $table->index(['calculation_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
