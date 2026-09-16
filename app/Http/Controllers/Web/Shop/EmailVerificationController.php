<?php

namespace App\Http\Controllers\Web\Shop;

use App\Enums\EmailVerificationResult;
use App\Http\Controllers\Controller;
use App\Services\Shop\EmailVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/** Pantalla donde el cliente ingresa el código enviado a su correo. */
class EmailVerificationController extends Controller
{
    public function show(Request $request, EmailVerificationService $verification): View|RedirectResponse
    {
        $user = $request->user('customer');

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('shop.account.orders'));
        }

        return view('site.shop.auth.verify', [
            'email' => $user->email,
            'resendIn' => $verification->secondsUntilResend($user),
            'minutes' => EmailVerificationService::CODE_TTL_MINUTES,
        ]);
    }

    public function verify(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        $data = $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Ingresa el código.', 'code.digits' => 'El código tiene 6 dígitos.']
        );

        $result = $verification->verify($request->user('customer'), $data['code']);

        if ($result !== EmailVerificationResult::VERIFIED) {
            return back()->withErrors(['code' => $result->message()]);
        }

        return redirect()->intended(route('shop.account.orders'))
            ->with('success', '¡Listo! Tu correo quedó verificado.');
    }

    public function resend(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        $user = $request->user('customer');

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('shop.account.orders');
        }

        $wait = $verification->secondsUntilResend($user);

        if ($wait > 0) {
            return back()->with('error', "Espera {$wait} segundos para pedir otro código.");
        }

        try {
            $verification->send($user);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'No pudimos enviar el código. Inténtalo nuevamente en unos minutos.');
        }

        return back()->with('success', "Te enviamos un código nuevo a {$user->email}.");
    }
}
