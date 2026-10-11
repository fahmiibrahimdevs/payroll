<?php

namespace App\Http\Controllers;

use App\Models\CashAdvancePayment;
use App\Models\Employee;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SecureDocumentController
{
    public function employeeCv(Employee $employee): Response
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('admin'), 403);

        $employee->loadMissing('detail');
        $path = $employee->detail?->curriculum_vitae;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function paymentProof(CashAdvancePayment $payment): Response
    {
        $payment->loadMissing('cashAdvance');
        Gate::authorize('view', $payment->cashAdvance);

        $path = $payment->proof_document;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
