<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\ClientAccount;
use App\Models\Party;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientAccountController extends Controller
{
    use ResolvesOffice;

    public function store(Request $request, string $slug, int $party): RedirectResponse
    {
        $this->authorizePerm('parties.manage');
        $this->authorizeFeature('client_portal');
        $model = Party::query()->findOrFail($party);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        ClientAccount::query()->updateOrCreate(
            ['party_id' => $model->id],
            [
                'name' => $model->name,
                'email' => $data['email'],
                'password' => $data['password'],
            ],
        );

        if ($model->email === null || $model->email === '') {
            $model->forceFill(['email' => $data['email']])->save();
        }

        return back()->with('status', 'Portal je otvoren. Klijent se prijavljuje na /'.$this->office()->slug.'/portal.');
    }
}
