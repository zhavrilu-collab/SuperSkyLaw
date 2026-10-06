<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationStatus;
use App\Models\OrganizationUser;
use App\Support\UserOrganizationNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizationPickerController extends Controller
{
    public function index(): View
    {
        $entries = OrganizationUser::query()
            ->with('organization')
            ->where('user_id', Auth::id())
            ->get()
            ->filter(fn (OrganizationUser $ou) => $ou->organization->status === OrganizationStatus::Active);

        return view('auth.pick-organization', [
            'entries' => $entries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string'],
        ]);

        $entry = OrganizationUser::query()
            ->with('organization')
            ->where('user_id', Auth::id())
            ->whereHas('organization', fn ($q) => $q->where('slug', $data['slug'])->where('status', OrganizationStatus::Active))
            ->first();

        if ($entry === null) {
            return back()->withErrors(['slug' => 'Odabrana tvrtka nije dostupna.']);
        }

        return redirect()->route('organization.dashboard', ['slug' => $entry->organization->slug]);
    }
}
