<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('salary_components')
            ->where('category', 'potongan')
            ->update(['type' => 'deduction']);

        DB::table('salary_components')
            ->whereIn('category', ['pendapatan_tetap', 'tunjangan_tidak_tetap'])
            ->update(['type' => 'allowance']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
