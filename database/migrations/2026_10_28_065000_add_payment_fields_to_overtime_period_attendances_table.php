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
        Schema::table('overtime_period_attendances', function (Blueprint $table) {
            $table->string('payment_status', 50)->default('unpaid')->after('total_daily_wage')->index();
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->string('payment_method', 50)->nullable()->after('paid_at');
            $table->string('bank_name', 100)->nullable()->after('payment_method');
            $table->string('bank_account_number', 100)->nullable()->after('bank_name');
            $table->string('bank_account_name', 150)->nullable()->after('bank_account_number');
            $table->string('payment_proof_path', 255)->nullable()->after('bank_account_name');
            $table->text('payment_notes')->nullable()->after('payment_proof_path');
            $table->foreignId('paid_by')->nullable()->after('payment_notes')->constrained('users')->nullOnDelete();

            $table->index(['overtime_period_id', 'payment_status'], 'idx_overtime_att_period_payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_period_attendances', function (Blueprint $table) {
            $table->dropIndex('idx_overtime_att_period_payment_status');
            $table->dropForeign(['paid_by']);
            $table->dropColumn([
                'payment_status',
                'paid_at',
                'payment_method',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'payment_proof_path',
                'payment_notes',
                'paid_by',
            ]);
        });
    }
};
