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
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->string('payment_method', 50)->nullable()->after('paid_at');
            $table->string('payment_proof_path', 255)->nullable()->after('payment_method');
            $table->text('payment_notes')->nullable()->after('payment_proof_path');
            $table->foreignId('paid_by')->nullable()->after('payment_notes')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['payment_method', 'payment_proof_path', 'payment_notes', 'paid_by']);
        });
    }
};
