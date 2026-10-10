<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\MatterStatute;
use App\Models\Statute;
use App\Models\StatuteWork;
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
        $like = '%'.$term.'%';
        $works = StatuteWork::query()
            ->when($term !== '', function ($query) use ($like) {
                $query->where(function ($outer) use ($like) {
                    $outer->where('title', 'like', $like)
                        ->orWhereHas('statutes', function ($statutes) use ($like) {
                            $statutes->where('title', 'like', $like)
                                ->orWhere('citation', 'like', $like)
                                ->orWhere('text_plain', 'like', $like);
                        });
                });
            })
            ->orderBy('title')
            ->paginate(40);
        if ($term !== '') {
            $works->appends(['q' => $term]);
        }

        $selected = $works->getCollection()->firstWhere('id', $request->integer('zakon'))
            ?? $works->getCollection()->first();
        if ($selected !== null) {
            $selected->load(['statutes' => function ($statutes) use ($term, $like) {
                $statutes->select('id', 'work_id', 'citation', 'published_on')
                    ->orderByDesc('published_on')
                    ->orderByDesc('id');
                if ($term === '') {
                    return;
                }
                $statutes->where(function ($inner) use ($like) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('citation', 'like', $like)
                        ->orWhere('text_plain', 'like', $like)
                        ->orWhereHas('work', fn ($work) => $work->where('title', 'like', $like));
                });
            }]);
        }

        return view('organization.statutes.index', [
            'works' => $works,
            'selected' => $selected,
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
