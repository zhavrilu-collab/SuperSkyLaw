<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Invoice;
use App\Services\EInvoiceService;
use Illuminate\Http\RedirectResponse;

class EInvoiceController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly EInvoiceService $invoices) {}

    public function store(string $slug, int $invoice): RedirectResponse
    {
        $this->authorizePerm('finance.manage');
        $this->authorizeFeature('e_invoice');
        $model = Invoice::query()->findOrFail($invoice);
        $this->invoices->submit($model);

        return back()->with('status', 'e-Račun: '.$model->fresh()->e_invoice_status->label().'.');
    }
}
