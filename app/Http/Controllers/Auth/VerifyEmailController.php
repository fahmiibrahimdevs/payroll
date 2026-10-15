<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request, ActivityLogService $logger): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            $logger->info(
                action: 'EMAIL_VERIFIED',
                module: 'auth',
                message: 'User email verified',
                context: ['user_id' => $request->user()->id],
                userId: $request->user()->id
            );
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
