<?php

namespace App\Models;

use App\Enums\SignatureStatus;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureRequest extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_document_id',
        'status',
        'provider_reference',
        'error',
        'signed_at',
        'requested_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SignatureStatus::class,
            'signed_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(MatterDocument::class, 'matter_document_id');
    }
}
