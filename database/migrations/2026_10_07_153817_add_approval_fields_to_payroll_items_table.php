<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->string('approval_status', 50)->default('pending')->after('payment_status')->index();
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });

        // Backfill existing approved/paid period items
        DB::table('payroll_items')
            ->join('payroll_periods', 'payroll_items.payroll_period_id', '=', 'payroll_periods.id')
            ->whereIn('payroll_periods.status', ['approved', 'paid'])
            ->update([
                'payroll_items.approval_status' => 'approved',
                'payroll_items.approved_by' => DB::raw('payroll_periods.approved_by'),
                'payroll_items.approved_at' => DB::raw('payroll_periods.approved_at'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
