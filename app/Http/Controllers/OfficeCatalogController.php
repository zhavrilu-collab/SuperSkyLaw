<?php

namespace App\Http\Controllers;

use App\Enums\MatterKind;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\DisputeCategory;
use App\Models\OfficeStageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfficeCatalogController extends Controller
{
    use ResolvesOffice;

    public function index(string $slug): View
    {
        $this->authorizePerm('settings.manage');
        $organization = $this->office();
        app(\App\Services\OfficeCatalog::class)->provision($organization);

        return view('organization.catalog', [
            'organization' => $organization,
            'kinds' => MatterKind::cases(),
            'templates' => OfficeStageTemplate::query()
                ->where('organization_id', $organization->id)
                ->orderBy('position')
                ->get()
                ->groupBy(fn (OfficeStageTemplate $row): string => $row->kind->value),
            'categories' => DisputeCategory::query()
                ->where('organization_id', $organization->id)
                ->orderBy('sort')
                ->get()
                ->groupBy(fn (DisputeCategory $row): string => $row->kind->value),
            'statutoryNames' => collect(config('statutory_deadlines.rules'))
                ->pluck('category')
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    public function storeStage(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('settings.manage');
        $organization = $this->office();
        $data = $request->validate([
            'kind' => ['required', Rule::enum(MatterKind::class)],
            'name' => ['required', 'string', 'max:80', Rule::unique('office_stage_templates', 'name')->where(fn ($query) => $query->where('organization_id', $organization->id)->where('kind', (string) $request->input('kind')))],
        ]);
        $position = (int) OfficeStageTemplate::query()
            ->where('organization_id', $organization->id)
            ->where('kind', $data['kind'])
            ->max('position');

        OfficeStageTemplate::query()->create([
            'organization_id' => $organization->id,
            'kind' => $data['kind'],
            'name' => $data['name'],
            'position' => $position + 1,
        ]);

        return back();
    }

    public function updateStage(Request $request, string $slug, OfficeStageTemplate $template): RedirectResponse
    {
        $this->authorizePerm('settings.manage');
        abort_unless($template->organization_id === $this->office()->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('office_stage_templates', 'name')->where(fn ($query) => $query->where('organization_id', $template->organization_id)->where('kind', $template->kind->value))->ignore($template->id)],
            'position' => ['required', 'integer', 'min:1', 'max:50'],
        ]);
        $template->update(['name' => $data['name']]);
        $this->resequence($template, (int) $data['position']);

        return back();
    }

    public function destroyStage(string $slug, OfficeStageTemplate $template): RedirectResponse
    {
        $this->authorizePerm('settings.manage');
        abort_unless($template->organization_id === $this->office()->id, 404);
        $template->delete();
        $this->resequence($template);

        return back();
    }

    public function storeCategory(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('settings.manage');
        $organization = $this->office();
        $data = $request->validate([
            'kind' => ['required', Rule::enum(MatterKind::class)],
            'name' => ['required', 'string', 'max:160', Rule::unique('dispute_categories', 'name')->where(fn ($query) => $query->where('organization_id', $organization->id)->where('kind', (string) $request->input('kind')))],
        ]);
        $sort = (int) DisputeCategory::query()
            ->where('organization_id', $organization->id)
            ->max('sort');

        DisputeCategory::query()->create([
            'organization_id' => $organization->id,
            'kind' => $data['kind'],
            'name' => $data['name'],
            'sort' => $sort + 1,
            'active' => true,
        ]);

        return back();
    }

    public function updateCategory(Request $request, string $slug, DisputeCategory $category): RedirectResponse
    {
        $this->authorizePerm('settings.manage');
        abort_unless($category->organization_id === $this->office()->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('dispute_categories', 'name')->where(fn ($query) => $query->where('organization_id', $category->organization_id)->where('kind', $category->kind->value))->ignore($category->id)],
        ]);
        $statutory = collect(config('statutory_deadlines.rules'))
            ->where('kind', $category->kind->value)
            ->pluck('category')
            ->filter();
        $warn = $statutory->contains($category->name) && $data['name'] !== $category->name;
        $category->update(['name' => $data['name']]);

        return back()->with('status', $warn
            ? 'Naziv je promijenjen. Zakonski rok više neće iskociti jer traži stari naziv.'
            : 'Predmet spora je spremljen.');
    }

    public function destroyCategory(string $slug, DisputeCategory $category): RedirectResponse
    {
        $this->authorizePerm('settings.manage');
        abort_unless($category->organization_id === $this->office()->id, 404);

        if ($category->matters()->exists()) {
            return back()->withErrors(['category' => 'Predmet spora je već na predmetu i ne može se obrisati.']);
        }

        $category->delete();

        return back();
    }

    private function resequence(OfficeStageTemplate $template, ?int $place = null): void
    {
        $rows = OfficeStageTemplate::query()
            ->where('organization_id', $template->organization_id)
            ->where('kind', $template->kind->value)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->reject(fn (OfficeStageTemplate $row): bool => $row->id === $template->id)
            ->values();

        if ($template->exists) {
            $index = $place === null ? $rows->count() : max(0, min($place - 1, $rows->count()));
            $rows->splice($index, 0, [$template]);
        }

        foreach ($rows as $index => $row) {
            if ((int) $row->position !== $index + 1) {
                $row->update(['position' => $index + 1]);
            }
        }
    }
}
