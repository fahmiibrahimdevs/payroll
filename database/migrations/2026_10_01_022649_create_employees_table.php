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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('full_name');
            $table->date('join_date');
            $table->date('contract_end_date')->nullable();
            $table->string('employment_status')->default('tetap'); // tetap, kontrak, magang
            $table->string('status')->default('active'); // active, inactive
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('total_cash_receipt', 15, 2)->default(0);
            $table->string('photo_path')->nullable();
            $table->timestamps();

            // Single and Composite Indexes
            $table->index('full_name');
            $table->index('department_id');
            $table->index('position_id');
            $table->index('employment_status');
            $table->index('status');
            $table->index(['status', 'department_id']);
            $table->index(['status', 'position_id']);
            $table->index(['employment_status', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
