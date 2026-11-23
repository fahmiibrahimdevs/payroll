<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
        Schema::create('employee_default_work_locations', function (Blueprint $table): void {
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('work_location_id')->constrained('work_locations')->cascadeOnDelete();

            $table->primary(['employee_id', 'work_location_id']);
            $table->index(['work_location_id', 'employee_id'], 'emp_default_work_locations_reverse_idx');
        });

        DB::table('employee_default_work_locations')->insertUsing(
            ['employee_id', 'work_location_id'],
            DB::table('employees')
                ->select('id', 'work_location_id')
                ->whereNotNull('work_location_id')
        );
    }

    /**
     * Preserve one default location in the legacy primary column before dropping
     * the many-to-many table. Additional defaults cannot be represented there.
     */
    public function down(): void
    {
        DB::table('employees')
            ->whereNull('work_location_id')
            ->whereExists(function (QueryBuilder $query): void {
                $query->selectRaw('1')
                    ->from('employee_default_work_locations')
                    ->whereColumn('employee_default_work_locations.employee_id', 'employees.id');
            })
            ->update([
                'work_location_id' => DB::table('employee_default_work_locations')
                    ->select('work_location_id')
                    ->whereColumn('employee_default_work_locations.employee_id', 'employees.id')
                    ->orderBy('work_location_id')
                    ->limit(1),
            ]);

        Schema::dropIfExists('employee_default_work_locations');
    }
};
