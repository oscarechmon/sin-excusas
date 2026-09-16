<?php

namespace App\Http\Controllers\Web\Shop;

use App\Actions\Shop\RegisterClientUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterClientUserRequest;
use App\Models\ClientUser;
use App\Services\Shop\EmailVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/** Registro e inicio de sesión con correo en la tienda web. */
class CustomerAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('site.shop.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $account = ClientUser::query()->where('email', $credentials['email'])->first();

            return back()->withErrors([
                'email' => $account?->usesOnlyGoogle()
                    ? 'Esta cuenta se creó con Google. Usa el botón "Continuar con Google".'
                    : 'Correo o contraseña incorrectos.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Quien se registró y no llegó a ingresar el código lo completa ahora.
        if (! $request->user('customer')->hasVerifiedEmail()) {
            return redirect()->route('shop.verification.notice');
        }

        return redirect()->intended(route('shop.account.orders'));
    }

    public function showRegister(): View
    {
        return view('site.shop.auth.register');
    }

    public function register(
        RegisterClientUserRequest $request,
        RegisterClientUserAction $register,
        EmailVerificationService $verification,
    ): RedirectResponse {
        $user = $register->execute($request->validated());

        Auth::guard('customer')->login($user);
        $request->session()->regenerate();

        try {
            $verification->send($user);
        } catch (Throwable $e) {
            // La cuenta ya existe: si el correo falla, puede pedir el reenvío.
            report($e);

            return redirect()->route('shop.verification.notice')
                ->with('error', 'No pudimos enviar el código. Usa "Reenviar código" en unos segundos.');
        }

        return redirect()->route('shop.verification.notice')
            ->with('success', "Te enviamos un código de verificación a {$user->email}.");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('site.home');
    }
}
