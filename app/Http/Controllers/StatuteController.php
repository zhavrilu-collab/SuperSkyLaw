<?php

namespace App\Http\Controllers;

use App\Enums\StatuteArea;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\MatterStatute;
use App\Models\Statute;
use App\Models\StatuteWork;
use App\Services\StatuteWorkGrouper;
use App\Support\PerPage;
use App\Support\TextFold;
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
        $like = TextFold::like($term);
        $area = StatuteArea::tryFrom($request->string('podrucje')->toString());
        $listSort = $request->string('lsort')->toString() === 'podrucje' ? 'podrucje' : 'naziv';
        $listDir = $request->string('ldir')->toString() === 'desc' ? 'desc' : 'asc';
        $works = StatuteWork::query()
            ->when($area !== null, fn ($query) => $query->where('area', $area->value))
            ->when($term !== '', function ($query) use ($like) {
                $query->where(function ($outer) use ($like) {
                    $outer->whereRaw(TextFold::expression('statute_works.title').' like ? escape ?', [$like, '\\'])
                        ->orWhereHas('statutes', function ($statutes) use ($like) {
                            $statutes->whereRaw(TextFold::expression('statutes.title').' like ? escape ?', [$like, '\\'])
                                ->orWhereRaw(TextFold::expression('statutes.citation').' like ? escape ?', [$like, '\\']);
                        });
                });
            });
        if ($listSort === 'podrucje') {
            $works->orderByRaw($this->areaOrderSql().' '.$listDir)->orderBy('title');
        } else {
            $works->orderBy('title', $listDir);
        }
        $works = $works->paginate(PerPage::resolve($request));

        $sort = $request->string('sort')->toString();
        if (! in_array($sort, ['objava', 'datum', 'naziv'], true)) {
            $sort = 'datum';
        }
        $dir = $request->string('dir')->toString() === 'asc' ? 'asc' : 'desc';

        $selected = $works->getCollection()->firstWhere('id', $request->integer('zakon'))
            ?? $works->getCollection()->first();
        if ($selected !== null) {
            $selected->load(['statutes' => function ($statutes) use ($term, $like) {
                $statutes->select('id', 'work_id', 'title', 'citation', 'published_on');
                if ($term === '') {
                    return;
                }
                $statutes->where(function ($inner) use ($like) {
                    $inner->whereRaw(TextFold::expression('statutes.title').' like ? escape ?', [$like, '\\'])
                        ->orWhereRaw(TextFold::expression('statutes.citation').' like ? escape ?', [$like, '\\'])
                        ->orWhereHas('work', fn ($work) => $work->whereRaw(TextFold::expression('statute_works.title').' like ? escape ?', [$like, '\\']));
                });
            }]);
            $selected->setRelation('statutes', $this->sortPublications($selected->statutes, $sort, $dir));
        }
        $reading = $selected === null ? null : $this->readingPublication($selected);

        return view('organization.statutes.index', [
            'works' => $works,
            'selected' => $selected,
            'reading' => $reading,
            'readingConsolidated' => $reading !== null && $this->isConsolidated($reading->title),
            'term' => $term,
            'sort' => $sort,
            'dir' => $dir,
            'listSort' => $listSort,
            'listDir' => $listDir,
            'area' => $area,
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

    /**
     * @param  \Illuminate\Support\Collection<int, Statute>  $statutes
     * @return \Illuminate\Support\Collection<int, Statute>
     */
    private function sortPublications($statutes, string $sort, string $dir)
    {
        $collator = class_exists(\Collator::class) ? new \Collator('hr') : null;

        return $statutes->sort(function (Statute $left, Statute $right) use ($sort, $dir, $collator): int {
            $empty = match ($sort) {
                'naziv' => [(string) $left->title === '', (string) $right->title === ''],
                'objava' => [$this->citationParts((string) $left->citation) === null, $this->citationParts((string) $right->citation) === null],
                default => [$left->published_on === null, $right->published_on === null],
            };
            if ($empty[0] || $empty[1]) {
                if ($empty[0] === $empty[1]) {
                    return $left->id <=> $right->id;
                }

                return $empty[0] ? 1 : -1;
            }

            $cmp = match ($sort) {
                'naziv' => $this->compareText((string) $left->title, (string) $right->title, $collator),
                'objava' => $this->compareCitation((string) $left->citation, (string) $right->citation),
                default => $left->published_on <=> $right->published_on,
            };
            if ($cmp === 0) {
                $cmp = $left->id <=> $right->id;
            }

            return $dir === 'desc' ? -$cmp : $cmp;
        })->values();
    }

    private function readingPublication(StatuteWork $work): ?Statute
    {
        $rows = Statute::query()
            ->where('work_id', $work->id)
            ->get(['id', 'title', 'published_on']);
        $grouper = app(StatuteWorkGrouper::class);
        $latest = fn (Statute $statute): string => sprintf('%010d-%010d', $statute->published_on?->timestamp ?? 0, $statute->id);
        $chosen = $rows
            ->filter(fn (Statute $statute) => $this->isConsolidated($statute->title))
            ->sortByDesc($latest)
            ->first();
        if ($chosen === null) {
            $chosen = $rows
                ->filter(fn (Statute $statute) => ! $grouper->isAmendment($statute->title))
                ->sortByDesc($latest)
                ->first();
        }
        if ($chosen === null) {
            return null;
        }

        return Statute::query()->find($chosen->id);
    }

    private function isConsolidated(string $title): bool
    {
        return preg_match('/\((?:pro|pre)čišćeni tekst\)/iu', $title) === 1;
    }

    private function areaOrderSql(): string
    {
        $cases = [];
        foreach (StatuteArea::cases() as $index => $area) {
            $cases[] = "when '{$area->value}' then ".($index + 1);
        }

        return 'case statute_works.area '.implode(' ', $cases).' else 99 end';
    }

    private function compareText(string $left, string $right, ?\Collator $collator): int
    {
        if ($left === '' || $right === '') {
            return $left === $right ? 0 : ($left === '' ? 1 : -1);
        }

        return $collator ? (int) $collator->compare($left, $right) : strcasecmp($left, $right);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function compareCitation(string $left, string $right): int
    {
        $a = $this->citationParts($left);
        $b = $this->citationParts($right);
        if ($a === null || $b === null) {
            return $a === $b ? 0 : ($a === null ? 1 : -1);
        }

        return $a <=> $b;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function citationParts(string $citation): ?array
    {
        if (preg_match('/(\d+)\s*\/\s*(\d{4})/', $citation, $match) !== 1) {
            return null;
        }

        return [(int) $match[2], (int) $match[1]];
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
