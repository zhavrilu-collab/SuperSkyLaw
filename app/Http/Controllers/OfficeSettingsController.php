<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Support\OfficeThemes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfficeSettingsController extends Controller
{
    use ResolvesOffice;

    public function edit(string $slug): View
    {
        $this->authorizePerm('settings.manage');

        return view('organization.settings', [
            'organization' => $this->office(),
            'themes' => OfficeThemes::all(),
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

    public function updateTheme(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('settings.manage');

        $data = $request->validate([
            'theme_color' => ['required', 'string', Rule::in(OfficeThemes::keys())],
        ], [
            'theme_color.required' => 'Odaberite boju ureda.',
            'theme_color.in' => 'Odaberite jednu od službenih boja.',
        ]);

        $this->office()->update($data);

        return back()->with('status', 'Tema ureda je spremljena.');
    }
}
