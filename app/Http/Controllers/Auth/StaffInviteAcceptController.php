<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\OrganizationUser;
use App\Models\StaffInvite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffInviteAcceptController extends Controller
{
    public function show(string $token): View
    {
        $invite = StaffInvite::query()
            ->with('organization')
            ->where('token', $token)
            ->firstOrFail();

        if (! $invite->isValid()) {
            abort(410, 'Pozivnica je istekla ili je već iskorištena.');
        }

        return view('auth.staff-invite-accept', [
            'invite' => $invite,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invite = StaffInvite::query()
            ->with('organization')
            ->where('token', $token)
            ->firstOrFail();

        if (! $invite->isValid()) {
            abort(410, 'Pozivnica je istekla ili je već iskorištena.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->where('email', $invite->email)->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $invite->email,
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
            ])->save();
        }

        OrganizationUser::query()->updateOrCreate(
            [
                'organization_id' => $invite->organization_id,
                'user_id' => $user->id,
            ],
            ['role' => $invite->role],
        );

        $invite->forceFill(['accepted_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('organization.dashboard', ['slug' => $invite->organization->slug])
            ->with('status', 'Pozivnica je prihvaćena.');
    }
}
