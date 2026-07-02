<?php

namespace CarlVallory\KrayinGoogleAuth\Http\Controllers;

use Illuminate\Routing\Controller;
use Webkul\User\Models\User;

class PendingUserController extends Controller
{
    public function index()
    {
        abort_unless(bouncer()->hasPermission('settings.user.users.google_pending'), 401);

        $pending = User::where('auth_provider', 'google')
            ->where('status', 0)
            ->get();

        return view('google-auth::pending.index', compact('pending'));
    }

    public function approve(int $id)
    {
        abort_unless(bouncer()->hasPermission('settings.user.users.google_pending'), 401);

        $user = User::where('id', $id)
            ->where('status', 0)
            ->where('auth_provider', 'google')
            ->firstOrFail();
        $user->status = 1;
        $user->save();

        session()->flash('success', 'Usuario aprobado.');

        return redirect()->route('google-auth.pending.index');
    }
}
