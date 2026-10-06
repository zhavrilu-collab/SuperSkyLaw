<?php

namespace App\Services;

use App\Enums\EInvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class EInvoiceService
{
    public function submit(Invoice $invoice): Invoice
    {
        $invoice->loadMissing(['lines', 'matter', 'organization']);
        $xml = $this->xml($invoice);
        $path = 'eracuni/'.$invoice->organization_id.'/'.$invoice->number.'.xml';
        Storage::disk('local')->put($path, $xml);

        $url = (string) config('services.moj_eracun.url');
        if ($url === '') {
            $invoice->forceFill([
                'e_invoice_status' => EInvoiceStatus::Prepared,
                'e_invoice_path' => $path,
                'e_invoice_error' => null,
            ])->save();

            return $invoice;
        }

        $response = Http::withBasicAuth(
            (string) config('services.moj_eracun.username'),
            (string) config('services.moj_eracun.password'),
        )->withBody($xml, 'application/xml')->post($url);

        $invoice->forceFill([
            'e_invoice_path' => $path,
            'e_invoice_status' => $response->successful() ? EInvoiceStatus::Sent : EInvoiceStatus::Failed,
            'e_invoice_error' => $response->successful() ? null : mb_substr($response->body(), 0, 500),
            'e_invoice_sent_at' => $response->successful() ? now() : null,
        ])->save();

        return $invoice;
    }

    public function xml(Invoice $invoice): string
    {
        $organization = $invoice->organization;
        $lines = '';
        foreach ($invoice->lines as $index => $line) {
            $amount = number_format($line->line_total_cents / 100, 2, '.', '');
            $lines .= '<cac:InvoiceLine>'
                .'<cbc:ID>'.($index + 1).'</cbc:ID>'
                .'<cbc:InvoicedQuantity unitCode="C62">'.$line->quantity.'</cbc:InvoicedQuantity>'
                .'<cbc:LineExtensionAmount currencyID="EUR">'.$amount.'</cbc:LineExtensionAmount>'
                .'<cac:Item><cbc:Name>'.$this->escape($line->description).'</cbc:Name></cac:Item>'
                .'<cac:Price><cbc:PriceAmount currencyID="EUR">'.number_format($line->unit_price_cents / 100, 2, '.', '').'</cbc:PriceAmount></cac:Price>'
                .'</cac:InvoiceLine>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">'
            .'<cbc:CustomizationID>urn:cen.eu:en16931:2017</cbc:CustomizationID>'
            .'<cbc:ID>'.$this->escape($invoice->number).'</cbc:ID>'
            .'<cbc:IssueDate>'.$invoice->issue_date->format('Y-m-d').'</cbc:IssueDate>'
            .'<cbc:DueDate>'.$invoice->due_date->format('Y-m-d').'</cbc:DueDate>'
            .'<cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>'
            .'<cac:AccountingSupplierParty><cac:Party><cac:PartyName><cbc:Name>'.$this->escape($organization->name).'</cbc:Name></cac:PartyName><cac:PartyTaxScheme><cbc:CompanyID>'.$this->escape((string) $organization->oib).'</cbc:CompanyID></cac:PartyTaxScheme></cac:Party></cac:AccountingSupplierParty>'
            .'<cac:AccountingCustomerParty><cac:Party><cac:PartyName><cbc:Name>'.$this->escape($invoice->buyer_name).'</cbc:Name></cac:PartyName><cac:PartyTaxScheme><cbc:CompanyID>'.$this->escape((string) $invoice->buyer_oib).'</cbc:CompanyID></cac:PartyTaxScheme></cac:Party></cac:AccountingCustomerParty>'
            .'<cac:TaxTotal><cbc:TaxAmount currencyID="EUR">'.number_format($invoice->vat_cents / 100, 2, '.', '').'</cbc:TaxAmount></cac:TaxTotal>'
            .'<cac:LegalMonetaryTotal><cbc:TaxExclusiveAmount currencyID="EUR">'.number_format($invoice->subtotal_cents / 100, 2, '.', '').'</cbc:TaxExclusiveAmount><cbc:PayableAmount currencyID="EUR">'.number_format($invoice->total_cents / 100, 2, '.', '').'</cbc:PayableAmount></cac:LegalMonetaryTotal>'
            .$lines
            .'</Invoice>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
