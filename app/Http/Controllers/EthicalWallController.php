<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\EthicalWall;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EthicalWallController extends Controller
{
    use ResolvesOffice;

    public function store(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('walls.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ((int) $data['user_id'] === (int) auth()->id()) {
            return back()->withErrors(['user_id' => 'Ne možete postaviti etički zid sami sebi.']);
        }

        OrganizationUser::query()
            ->where('organization_id', $this->office()->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        EthicalWall::query()->updateOrCreate(
            ['matter_id' => $model->id, 'user_id' => $data['user_id']],
            ['reason' => $data['reason'], 'created_by_user_id' => auth()->id()],
        );

        return back()->with('status', 'Etički zid je postavljen. Ta osoba više ne vidi predmet.');
    }

    public function destroy(string $slug, int $matter, int $wall): RedirectResponse
    {
        $this->authorizePerm('walls.manage');
        $model = $this->findVisibleMatter($matter);
        EthicalWall::query()->where('matter_id', $model->id)->findOrFail($wall)->delete();

        return back()->with('status', 'Etički zid je uklonjen.');
    }
}
