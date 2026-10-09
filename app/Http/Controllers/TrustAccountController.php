<?php

namespace App\Http\Controllers;

use App\Enums\TrustDirection;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Matter;
use App\Models\TrustMovement;
use App\Services\TrustLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrustAccountController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly TrustLedger $ledger) {}

    public function index(string $slug): View
    {
        $this->authorizePerm('finance.view');
        $office = $this->office();

        return view('organization.finance.trust', [
            'movements' => TrustMovement::query()->with('matter')->orderByDesc('id')->get(),
            'balanceCents' => $this->ledger->balance($office->id),
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
            'trustIban' => $office->trust_iban,
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('finance.manage');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'direction' => ['required', Rule::enum(TrustDirection::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:255'],
        ]);

        $matter = $this->findVisibleMatter((int) $data['matter_id']);
        $this->ledger->post(
            $this->office()->id,
            $matter->id,
            TrustDirection::from($data['direction']),
            (int) round(((float) $data['amount']) * 100),
            $data['purpose'],
            $data['counterparty'] ?? null,
        );

        return $this->redirectToMatterLedger($request, $matter->id, 'Promet depozitnog računa je upisan. Nije povezan s poslovnim računom ureda.');
    }
}
