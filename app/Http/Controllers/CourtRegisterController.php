<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Party;
use App\Services\CourtRegister;
use Illuminate\Http\RedirectResponse;

class CourtRegisterController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly CourtRegister $register) {}

    public function lookup(string $slug, int $party): RedirectResponse
    {
        $this->authorizePerm('parties.manage');
        $model = Party::query()->findOrFail($party);
        $data = $this->register->lookup($model);
        $model->forceFill([
            'name' => $data['name'],
            'mbs' => $data['mbs'] ?: $model->mbs,
            'address' => $data['address'] ?: $model->address,
            'city' => $data['city'] ?: $model->city,
        ])->save();

        return back()->with('status', 'Podaci su preuzeti iz sudskog registra.');
    }
}
