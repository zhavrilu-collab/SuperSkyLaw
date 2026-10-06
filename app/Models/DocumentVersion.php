<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_document_id',
        'version',
        'original_name',
        'path',
        'size_bytes',
        'mime',
        'uploaded_by_user_id',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(MatterDocument::class, 'matter_document_id');
    }
}
