<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeSettingsController extends Controller
{
    use ResolvesOffice;

    public function edit(string $slug): View
    {
        $this->authorizePerm('settings.manage');

        return view('organization.settings', [
            'organization' => $this->office(),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('settings.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:34'],
            'trust_iban' => ['nullable', 'string', 'max:34'],
        ]);

        $this->office()->update($data);

        return back()->with('status', 'Podaci ureda su spremljeni.');
    }
}
