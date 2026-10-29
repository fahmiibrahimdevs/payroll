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
        Schema::create('overtime_period_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_period_id')->constrained('overtime_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('meal_allowance_type', 20)->default('standar'); // standar, khusus
            $table->decimal('meal_allowance_per_day', 12, 2)->default(0);
            $table->unsignedTinyInteger('attended_days')->default(0);
            $table->json('attendance_days')->nullable(); // e.g. ["mon", "tue", "wed", "thu", "fri"]
            $table->decimal('total_meal_allowance', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['overtime_period_id', 'employee_id'], 'period_emp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_period_attendances');
    }
};
