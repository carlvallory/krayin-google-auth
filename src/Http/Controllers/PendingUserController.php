<?php

namespace CarlVallory\KrayinGoogleAuth\Http\Controllers;

use Illuminate\Routing\Controller;
use Webkul\User\Models\User;

class PendingUserController extends Controller
{
    public function index()
    {
        $pending = User::where('auth_provider', 'google')
            ->where('status', 0)
            ->get();

        return view('google-auth::pending.index', compact('pending'));
    }

    public function approve(int $id)
    {
        $user = User::findOrFail($id);
        $user->status = 1;
        $user->save();

        session()->flash('success', 'Usuario aprobado.');

        return redirect()->route('google-auth.pending.index');
    }
}
