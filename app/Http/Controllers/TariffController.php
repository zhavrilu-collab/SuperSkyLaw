<?php

namespace App\Http\Controllers;

use App\Enums\FeeAudience;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Matter;
use App\Models\TariffAction;
use App\Models\TariffCharge;
use App\Services\TariffCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class TariffController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly TariffCatalog $catalog) {}

    public function index(string $slug): View
    {
        $this->authorizePerm('finance.view');
        $this->authorizeFeature('tariff_hok');
        $version = $this->catalog->current();

        return view('organization.finance.tariff', [
            'version' => $version,
            'bands' => $version->bands()->orderBy('value_from_cents')->get(),
            'actions' => $version->actions()->orderBy('id')->get(),
            'charges' => TariffCharge::query()->with('matter')->whereIn('matter_id', Matter::query()->visibleTo($this->membership())->select('id'))->orderByDesc('id')->get(),
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('finance.manage');
        $this->authorizeFeature('tariff_hok');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'tariff_action_id' => ['required', 'integer'],
            'audience' => ['required', Rule::enum(FeeAudience::class)],
        ]);

        $matter = $this->findVisibleMatter((int) $data['matter_id']);
        $action = TariffAction::query()->findOrFail($data['tariff_action_id']);

        try {
            $quote = $this->catalog->quote($action, $matter->dispute_value_cents);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['matter_id' => $exception->getMessage()]);
        }

        TariffCharge::query()->create([
            'matter_id' => $matter->id,
            'tariff_version_id' => $action->tariff_version_id,
            'tariff_action_id' => $action->id,
            'audience' => $data['audience'],
            'description' => $action->label,
            'points' => $quote['points'],
            'amount_cents' => $quote['amount_cents'],
            'created_by_user_id' => auth()->id(),
        ]);

        return back()->with('status', 'Nagrada je obračunata: '.$quote['points'].' bodova.');
    }
}
