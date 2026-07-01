<?php

namespace CarlVallory\KrayinGoogleAuth\Http\Controllers;

use CarlVallory\KrayinGoogleAuth\DataObjects\GoogleAccount;
use CarlVallory\KrayinGoogleAuth\Services\GoogleUserResolver;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(private GoogleUserResolver $resolver) {}

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('[google-auth] callback fallo: ' . $e->getMessage());
            session()->flash('error', 'No se pudo completar el ingreso con Google. Intenta de nuevo.');

            return redirect()->route('admin.session.create');
        }

        if (! $googleUser->getEmail()) {
            session()->flash('error', 'Tu cuenta de Google no expone un correo válido.');

            return redirect()->route('admin.session.create');
        }

        $claims = is_array($googleUser->user ?? null) ? $googleUser->user : [];

        $account = new GoogleAccount(
            email: $googleUser->getEmail(),
            googleId: $googleUser->getId(),
            name: $googleUser->getName() ?: $googleUser->getEmail(),
            avatar: $googleUser->getAvatar(),
            hostedDomain: $claims['hd'] ?? null,
            emailVerified: filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );

        $result = $this->resolver->resolve($account);

        if (! $result->allowed) {
            session()->flash('warning', 'Tu acceso está pendiente de aprobación de un administrador.');

            return redirect()->route('admin.session.create');
        }

        auth()->guard('user')->login($result->user);

        return redirect()->intended(route('admin.dashboard.index'));
    }
}
