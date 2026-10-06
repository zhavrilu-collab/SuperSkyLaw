<?php

namespace App\Models;

use App\Enums\EInvoiceStatus;
use App\Enums\InvoiceStatus;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'party_id',
        'number',
        'issue_date',
        'due_date',
        'status',
        'subtotal_cents',
        'vat_cents',
        'total_cents',
        'paid_cents',
        'vat_rate',
        'buyer_name',
        'buyer_oib',
        'buyer_address',
        'e_invoice_status',
        'e_invoice_path',
        'e_invoice_error',
        'e_invoice_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'e_invoice_status' => EInvoiceStatus::class,
            'e_invoice_sent_at' => 'datetime',
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refreshPaymentStatus(): void
    {
        $paid = (int) $this->payments()->sum('amount_cents');
        $this->paid_cents = $paid;

        if ($paid >= $this->total_cents && $this->total_cents > 0) {
            $this->status = InvoiceStatus::Paid;
        } elseif ($this->due_date->isPast()) {
            $this->status = InvoiceStatus::Overdue;
        } elseif ($paid > 0) {
            $this->status = InvoiceStatus::Partial;
        } else {
            $this->status = InvoiceStatus::Unpaid;
        }

        $this->save();
    }
}
