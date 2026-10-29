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
        Schema::create('overtime_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 50)->unique();
            $table->foreignId('overtime_period_id')->nullable()->constrained('overtime_periods')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('duration_hours', 5, 2);
            $table->decimal('hourly_rate', 14, 2);
            $table->decimal('meal_allowance', 14, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->text('reason');
            $table->string('submission_source', 20)->default('karyawan')->index(); // karyawan, admin
            $table->string('status', 50)->default('pending_admin')->index(); // pending_admin, pending_direktur, approved, rejected_admin, rejected_direktur
            $table->text('admin_notes')->nullable();
            $table->timestamp('admin_adjusted_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('verified_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_direktur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'date']);
            $table->index(['status', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_submissions');
    }
};
