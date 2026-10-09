<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\MatterStatute;
use App\Models\Statute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatuteController extends Controller
{
    use ResolvesOffice;

    public function index(Request $request, string $slug): View
    {
        $this->authorizePerm('matters.view');
        $term = trim($request->string('q')->toString());
        $statutes = Statute::query()
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('citation', 'like', $like)
                        ->orWhere('text_plain', 'like', $like);
                });
            })
            ->orderByDesc('published_on')
            ->orderBy('title')
            ->paginate(30)
            ->withQueryString();

        return view('organization.statutes.index', [
            'statutes' => $statutes,
            'term' => $term,
        ]);
    }

    public function show(string $slug, int $statute): View
    {
        $this->authorizePerm('matters.view');

        return view('organization.statutes.show', [
            'statute' => Statute::query()->findOrFail($statute),
        ]);
    }

    public function attach(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'statute_id' => ['required', 'integer'],
        ]);
        $statute = Statute::query()->findOrFail($data['statute_id']);
        MatterStatute::query()->firstOrCreate(
            ['matter_id' => $model->id, 'statute_id' => $statute->id],
            ['created_by_user_id' => auth()->id()],
        );

        return back()->with('status', 'Zakon je povezan s predmetom.');
    }

    public function detach(string $slug, int $matter, int $statute): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        MatterStatute::query()
            ->where('matter_id', $model->id)
            ->where('statute_id', $statute)
            ->delete();

        return back()->with('status', 'Zakon je uklonjen s predmeta.');
    }
}
