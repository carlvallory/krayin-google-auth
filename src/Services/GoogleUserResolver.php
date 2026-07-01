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
        $user = User::where('google_id', $account->googleId)->first()
            ?? User::where('email', $account->email)->first();

        if ($user) {
            if (! $user->google_id) {
                $user->google_id = $account->googleId;
                $user->save();
            }

            $allowed = (int) $user->status === 1;

            return new ResolutionResult($user, $allowed, $allowed ? null : 'pending');
        }

        $allowedDomains = config('google-auth.allowed_domains', []);
        $isAllowedDomain = $account->hostedDomain !== null
            && in_array($account->hostedDomain, $allowedDomains, true);

        $role = Role::where('name', config('google-auth.default_role_name'))->first();

        $user = User::create([
            'name'          => $account->name,
            'email'         => $account->email,
            'google_id'     => $account->googleId,
            'image'         => $account->avatar,
            'auth_provider' => 'google',
            'role_id'       => $role?->id,
            'status'        => $isAllowedDomain ? 1 : 0,
            'password'      => null,
        ]);

        return new ResolutionResult($user, $isAllowedDomain, $isAllowedDomain ? null : 'pending');
    }
}
