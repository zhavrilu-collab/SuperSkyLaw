<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\SpnftCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpnftController extends Controller
{
    use ResolvesOffice;

    public function required(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $model->update(['spnft_required' => $request->boolean('spnft_required')]);

        return back()->with('status', $model->spnft_required
            ? 'SPNFT checklist je uključen na predmetu.'
            : 'SPNFT checklist je isključen na predmetu.');
    }

    public function toggle(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'item' => ['required', 'string', Rule::in(array_keys(config('spnft.items')))],
        ]);

        $existing = SpnftCheck::query()
            ->where('matter_id', $model->id)
            ->where('item', $data['item'])
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return back()->with('status', 'Stavka SPNFT checklista je poništena.');
        }

        SpnftCheck::query()->create([
            'matter_id' => $model->id,
            'item' => $data['item'],
            'completed_by_user_id' => auth()->id(),
            'completed_at' => now(),
        ]);

        return back()->with('status', 'Stavka SPNFT checklista je potvrđena.');
    }
}
