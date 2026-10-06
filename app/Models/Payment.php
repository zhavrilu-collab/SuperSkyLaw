<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'amount_cents',
        'paid_on',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
