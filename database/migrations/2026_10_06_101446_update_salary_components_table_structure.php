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
        Schema::table('salary_components', function (Blueprint $table) {
            $table->string('category', 30)->default('pendapatan_tetap')->after('code');
            $table->string('calculation_type', 30)->default('nominal')->after('type');
            $table->decimal('default_value', 15, 2)->nullable()->after('calculation_type');
            $table->boolean('kena_pajak')->default(true)->after('default_value');
            $table->boolean('is_system')->default(false)->after('kena_pajak');
            $table->integer('sort_order')->default(0)->after('is_active');
            $table->softDeletes()->after('updated_at');

            $table->index('category');
            $table->index('calculation_type');
            $table->index('sort_order');
            $table->index('is_system');
            $table->index(['category', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });

        // Rename column kena_pajak to is_taxable for standard English convention
        Schema::table('salary_components', function (Blueprint $table) {
            $table->renameColumn('kena_pajak', 'is_taxable');
        });

        // Backfill existing rows if any
        DB::table('salary_components')->where('type', 'deduction')->update([
            'category' => 'potongan',
            'is_taxable' => false,
        ]);
        DB::table('salary_components')->where('type', 'allowance')->update([
            'category' => 'tunjangan_tidak_tetap',
            'is_taxable' => true,
        ]);
        DB::table('salary_components')->whereIn('code', ['KASBON', 'POT-KASBON', 'GAJI_POKOK'])->update([
            'is_system' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['calculation_type']);
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['is_system']);
            $table->dropIndex(['category', 'is_active']);
            $table->dropIndex(['is_active', 'sort_order']);

            $table->dropSoftDeletes();
            $table->dropColumn([
                'category',
                'calculation_type',
                'default_value',
                'is_taxable',
                'is_system',
                'sort_order',
            ]);
        });
    }
};
