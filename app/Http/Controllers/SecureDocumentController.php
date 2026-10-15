<?php

namespace App\Http\Controllers;

use App\Models\CashAdvancePayment;
use App\Models\Employee;
use App\Services\ActivityLogService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SecureDocumentController
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    public function employeeCv(Employee $employee): Response
    {
        $user = auth()->user();
        $isOwnCv = $user?->hasRole('karyawan') && (int) $user->getEmployeeRecord()?->id === (int) $employee->id;
        abort_unless($user?->hasRole(['admin', 'direktur']) || $isOwnCv, 403);

        $employee->loadMissing('detail');
        $path = $employee->detail?->curriculum_vitae;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $this->logger->info(
            action: 'EMPLOYEE_CV_VIEWED',
            module: 'documents',
            message: 'Employee CV viewed',
            context: ['employee_id' => $employee->id],
            userId: $user?->id
        );

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

        $this->logger->info(
            action: 'CASH_ADVANCE_PROOF_VIEWED',
            module: 'documents',
            message: 'Cash advance payment proof viewed',
            context: [
                'payment_id' => $payment->id,
                'cash_advance_id' => $payment->cash_advance_id,
            ]
        );

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
