<?php

namespace App\Http\Controllers;

use App\Enums\FeeAudience;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Expense;
use App\Models\Matter;
use App\Models\TariffCharge;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CostBillController extends Controller
{
    use ResolvesOffice;

    public function index(Request $request, string $slug): View
    {
        $this->authorizePerm('finance.view');
        $this->authorizeFeature('tariff_hok');
        $matterId = (int) $request->query('matter_id');
        $matter = $matterId > 0 ? $this->findVisibleMatter($matterId) : null;

        return view('organization.finance.cost-bill', [
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
            'matter' => $matter,
            'fees' => $matter ? TariffCharge::query()->where('matter_id', $matter->id)->where('audience', FeeAudience::Opposing)->get() : collect(),
            'costs' => $matter ? Expense::query()->where('matter_id', $matter->id)->where('bill_to', FeeAudience::Opposing->value)->get() : collect(),
        ]);
    }

    public function pdf(string $slug, int $matter): Response
    {
        $this->authorizePerm('finance.view');
        $this->authorizeFeature('tariff_hok');
        $model = $this->findVisibleMatter($matter);
        $fees = TariffCharge::query()->where('matter_id', $model->id)->where('audience', FeeAudience::Opposing)->get();
        $costs = Expense::query()->where('matter_id', $model->id)->where('bill_to', FeeAudience::Opposing->value)->get();
        $pdf = Pdf::loadView('organization.finance.cost-bill-pdf', [
            'organization' => $this->office(),
            'matter' => $model,
            'fees' => $fees,
            'costs' => $costs,
        ]);

        return $pdf->download('troskovnik-'.str_replace(['/', '\\'], '-', $model->internal_number).'.pdf');
    }
}
