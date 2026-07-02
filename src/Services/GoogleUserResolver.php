<?php

namespace CarlVallory\KrayinGoogleAuth\Services;

use CarlVallory\KrayinGoogleAuth\DataObjects\GoogleAccount;
use CarlVallory\KrayinGoogleAuth\DataObjects\ResolutionResult;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class GoogleUserResolver
{
    public function resolve(GoogleAccount $account): ResolutionResult
    {
        // Usuario ya vinculado por google_id: retorna directo según su estado.
        $user = User::where('google_id', $account->googleId)->first();

        if ($user) {
            $allowed = (int) $user->status === 1;

            return new ResolutionResult($user, $allowed, $allowed ? null : 'pending');
        }

        // Fallback por email: SOLO si Google verificó el correo (evita account-takeover
        // por cuentas Google con email_verified=false que colisionan con un usuario local).
        $existingByEmail = User::where('email', $account->email)->first();

        if ($existingByEmail) {
            if (! $account->emailVerified) {
                return new ResolutionResult($existingByEmail, false, 'pending');
            }

            if (! $existingByEmail->google_id) {
                $existingByEmail->google_id     = $account->googleId;
                $existingByEmail->auth_provider = 'google';
                $existingByEmail->save();
            }

            $allowed = (int) $existingByEmail->status === 1;

            return new ResolutionResult($existingByEmail, $allowed, $allowed ? null : 'pending');
        }

        // Usuario nuevo: exige que el rol por defecto exista (no crear con role_id huérfano).
        $role = Role::where('name', config('google-auth.default_role_name'))->first();

        if (! $role) {
            throw new \RuntimeException(
                'El rol por defecto "' . config('google-auth.default_role_name')
                . '" no existe; corré las migraciones de google-auth antes de usar el login.'
            );
        }

        $allowedDomains = config('google-auth.allowed_domains', []);
        $isAllowedDomain = $account->hostedDomain !== null
            && in_array($account->hostedDomain, $allowedDomains, true);

        // Auto-aprobación solo con dominio permitido Y correo verificado por Google.
        $autoApprove = $isAllowedDomain && $account->emailVerified;

        $user = new User([
            'name'     => $account->name,
            'email'    => $account->email,
            'image'    => $account->avatar,
            'role_id'  => $role->id,
            'status'   => $autoApprove ? 1 : 0,
            'password' => null,
        ]);
        $user->google_id     = $account->googleId;
        $user->auth_provider = 'google';
        $user->save();

        return new ResolutionResult($user, $autoApprove, $autoApprove ? null : 'pending');
    }
}
