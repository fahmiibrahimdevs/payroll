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
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (! Schema::hasColumn('attendances', 'overtime_in')) {
                    $table->time('overtime_in')->nullable()->after('clock_out');
                }
                if (! Schema::hasColumn('attendances', 'overtime_out')) {
                    $table->time('overtime_out')->nullable()->after('overtime_in');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('attendances', 'overtime_in')) {
                    $columns[] = 'overtime_in';
                }
                if (Schema::hasColumn('attendances', 'overtime_out')) {
                    $columns[] = 'overtime_out';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
