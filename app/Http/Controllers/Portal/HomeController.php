<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\MatterDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeController extends Controller
{
    public function index(string $slug): View
    {
        $client = auth('client')->user();

        return view('portal.home', [
            'matters' => $this->matters()->with('courtEvents')->orderByDesc('id')->get(),
            'client' => $client,
        ]);
    }

    public function show(string $slug, int $matter): View
    {
        $model = $this->matters()->with([
            'timelineEntries' => fn ($query) => $query->where('visible_to_client', true)->orderByDesc('occurred_at'),
            'courtEvents' => fn ($query) => $query->orderBy('starts_at'),
            'documents' => fn ($query) => $query->where('shared_with_client', true),
            'invoices' => fn ($query) => $query->where('party_id', auth('client')->user()->party_id),
        ])->findOrFail($matter);

        return view('portal.matter', ['matter' => $model]);
    }

    public function invoice(string $slug, int $invoice): Response
    {
        $model = Invoice::query()
            ->where('party_id', auth('client')->user()->party_id)
            ->with(['lines', 'matter'])
            ->findOrFail($invoice);
        $pdf = Pdf::loadView('organization.finance.pdf', [
            'invoice' => $model,
            'organization' => app('currentOrganization'),
        ]);

        return $pdf->download('racun-'.$model->number.'.pdf');
    }

    public function document(string $slug, int $document): StreamedResponse
    {
        $model = MatterDocument::query()
            ->where('shared_with_client', true)
            ->whereIn('matter_id', $this->matters()->select('id'))
            ->findOrFail($document);

        return Storage::disk('local')->download($model->path, $model->original_name);
    }

    private function matters()
    {
        return Matter::query()->whereHas(
            'parties',
            fn ($query) => $query->where('party_id', auth('client')->user()->party_id),
        );
    }
}
