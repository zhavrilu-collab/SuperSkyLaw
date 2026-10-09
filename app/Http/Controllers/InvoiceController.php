<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\FeeAudience;
use App\Enums\TimeEntryStatus;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\Party;
use App\Models\Payment;
use App\Models\TariffCharge;
use App\Models\TimeEntry;
use App\Services\InvoiceBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly InvoiceBuilder $builder) {}

    public function index(string $slug): View
    {
        $this->authorizePerm('finance.view');

        $visibleMatterIds = Matter::query()->visibleTo($this->membership())->pluck('id');
        $invoices = Invoice::query()->with('matter')->whereIn('matter_id', $visibleMatterIds)->orderByDesc('id')->get();
        foreach ($invoices as $invoice) {
            if ($invoice->status->value !== 'paid') {
                $invoice->refreshPaymentStatus();
            }
        }

        return view('organization.finance.index', [
            'invoices' => $invoices->fresh('matter'),
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
            'approvedEntries' => TimeEntry::query()->with('matter')->whereIn('matter_id', $visibleMatterIds)->where('status', TimeEntryStatus::Approved)->whereNull('invoice_id')->get(),
            'openExpenses' => Expense::query()->with('matter')->whereIn('matter_id', $visibleMatterIds)->where('bill_to', FeeAudience::Client->value)->whereNull('invoice_id')->get(),
            'tariffCharges' => TariffCharge::query()->with('matter')->whereIn('matter_id', $visibleMatterIds)->where('audience', FeeAudience::Client)->whereNull('invoice_id')->get(),
            'parties' => Party::query()->orderBy('name')->get(),
        ]);
    }

    public function storeExpense(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('finance.manage');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bill_to' => ['required', Rule::enum(FeeAudience::class)],
        ]);

        $matter = $this->findVisibleMatter((int) $data['matter_id']);

        Expense::query()->create([
            'matter_id' => $matter->id,
            'category' => $data['category'],
            'description' => $data['description'],
            'bill_to' => $data['bill_to'],
            'amount_cents' => (int) round(((float) $data['amount']) * 100),
        ]);

        return $this->redirectToMatterLedger($request, $matter->id, 'Trošak je unesen.');
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('finance.manage');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'party_id' => ['required', 'integer'],
            'time_entry_ids' => ['nullable', 'array'],
            'time_entry_ids.*' => ['integer'],
            'expense_ids' => ['nullable', 'array'],
            'expense_ids.*' => ['integer'],
            'tariff_charge_ids' => ['nullable', 'array'],
            'tariff_charge_ids.*' => ['integer'],
        ]);

        $matter = $this->findVisibleMatter((int) $data['matter_id']);
        $party = Party::query()->findOrFail($data['party_id']);
        $invoice = $this->builder->create(
            $this->office(),
            $matter,
            $party,
            $data['time_entry_ids'] ?? [],
            $data['expense_ids'] ?? [],
            25,
            $data['tariff_charge_ids'] ?? [],
        );

        $message = 'Račun '.$invoice->number.' je izdan.';
        if ($request->input('return_to') === 'matter') {
            return $this->redirectToMatterLedger($request, $matter->id, $message);
        }

        return redirect()
            ->route('organization.invoices.show', [$this->office()->slug, $invoice->id])
            ->with('status', $message);
    }

    public function show(string $slug, int $invoice): View
    {
        $this->authorizePerm('finance.view');
        $model = Invoice::query()->with(['lines', 'payments', 'matter', 'party'])->findOrFail($invoice);
        if ($model->matter_id !== null) {
            $this->findVisibleMatter($model->matter_id);
        }
        $model->refreshPaymentStatus();

        return view('organization.finance.show', ['invoice' => $model->fresh(['lines', 'payments', 'matter'])]);
    }

    public function pay(Request $request, string $slug, int $invoice): RedirectResponse
    {
        $this->authorizePerm('finance.manage');
        $model = Invoice::query()->findOrFail($invoice);
        if ($model->matter_id !== null) {
            $this->findVisibleMatter($model->matter_id);
        }
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        Payment::query()->create([
            'invoice_id' => $model->id,
            'amount_cents' => (int) round(((float) $data['amount']) * 100),
            'paid_on' => $data['paid_on'],
            'note' => $data['note'] ?? null,
        ]);

        $model->refreshPaymentStatus();

        return back()->with('status', 'Uplata je evidentirana.');
    }

    public function pdf(string $slug, int $invoice): Response
    {
        $this->authorizePerm('finance.view');
        $model = Invoice::query()->with(['lines', 'matter'])->findOrFail($invoice);
        if ($model->matter_id !== null) {
            $this->findVisibleMatter($model->matter_id);
        }
        $pdf = Pdf::loadView('organization.finance.pdf', [
            'invoice' => $model,
            'organization' => $this->office(),
        ]);

        return $pdf->download('racun-'.$model->number.'.pdf');
    }
}
