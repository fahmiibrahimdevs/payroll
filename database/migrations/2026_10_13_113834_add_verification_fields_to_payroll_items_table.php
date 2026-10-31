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
            $table->foreignId('verified_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });

        // Backfill existing approved or paid period items as verified.
        // UPDATE ... JOIN hanya valid di MySQL/MariaDB; PostgreSQL dan SQLite
        // memakai UPDATE ... FROM.
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement(<<<'SQL'
UPDATE payroll_items
SET verified_by = COALESCE(payroll_periods.submitted_by, payroll_periods.created_by),
    verified_at = COALESCE(payroll_periods.submitted_at, payroll_periods.created_at)
FROM payroll_periods
WHERE payroll_items.payroll_period_id = payroll_periods.id
    AND payroll_periods.status IN (?, ?)
SQL, ['approved', 'paid']);
        } else {
            DB::table('payroll_items')
                ->join('payroll_periods', 'payroll_items.payroll_period_id', '=', 'payroll_periods.id')
                ->whereIn('payroll_periods.status', ['approved', 'paid'])
                ->update([
                    'payroll_items.verified_by' => DB::raw('COALESCE(payroll_periods.submitted_by, payroll_periods.created_by)'),
                    'payroll_items.verified_at' => DB::raw('COALESCE(payroll_periods.submitted_at, payroll_periods.created_at)'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verified_by', 'verified_at']);
        });
    }
};
