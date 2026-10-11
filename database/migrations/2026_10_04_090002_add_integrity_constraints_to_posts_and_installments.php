<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $usedSlugs = [];
        DB::table('posts')->orderBy('id')->each(function (object $post) use (&$usedSlugs): void {
            $slug = $post->slug ?: 'post';
            $base = $slug;
            $suffix = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }
            $usedSlugs[$slug] = true;
            if ($slug !== $post->slug) {
                DB::table('posts')->where('id', $post->id)->update(['slug' => $slug]);
            }
        });

        if (! Schema::hasIndex('posts', 'posts_slug_unique', 'unique')) {
            Schema::table('posts', function (Blueprint $table): void {
                $table->unique('slug', 'posts_slug_unique');
            });
        }

        if (! Schema::hasIndex('cash_advance_installments', 'cash_adv_installments_loan_no_unique', 'unique')) {
            Schema::table('cash_advance_installments', function (Blueprint $table): void {
                $table->unique(
                    ['cash_advance_id', 'installment_number'],
                    'cash_adv_installments_loan_no_unique'
                );
            });
        }

        if (! Schema::hasIndex('cash_advance_installments', 'cash_adv_installments_status_idx')) {
            Schema::table('cash_advance_installments', function (Blueprint $table): void {
                $table->index(
                    ['cash_advance_id', 'status', 'due_date'],
                    'cash_adv_installments_status_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
        });

        Schema::table('cash_advance_installments', function (Blueprint $table): void {
            $table->dropUnique('cash_adv_installments_loan_no_unique');
            $table->dropIndex('cash_adv_installments_status_idx');
        });
    }
};
