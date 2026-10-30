<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Bukti pembayaran payroll/lembur dipindahkan dari disk public (dapat diakses
 * tanpa autentikasi) ke disk local yang hanya tersaji lewat SecureDocumentController.
 */
return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('public');
        $local = Storage::disk('local');

        foreach (['payroll_items', 'overtime_period_attendances'] as $table) {
            $rows = \DB::table($table)
                ->whereNotNull('payment_proof_path')
                ->pluck('payment_proof_path');

            foreach ($rows as $path) {
                if ($local->exists($path) || ! $public->exists($path)) {
                    continue;
                }

                if ($local->write($path, $public->read($path))) {
                    $public->delete($path);
                }
            }
        }
    }

    public function down(): void
    {
        // File bukti tidak dikembalikan ke disk publik demi keamanan.
    }
};
