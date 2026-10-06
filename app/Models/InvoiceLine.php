<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price_cents',
        'line_total_cents',
        'vat_rate',
        'time_entry_id',
        'expense_id',
        'tariff_charge_id',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
