<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'category',
        'description',
        'bill_to',
        'amount_cents',
        'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
