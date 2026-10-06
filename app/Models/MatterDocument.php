<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatterDocument extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'folder',
        'original_name',
        'path',
        'size_bytes',
        'mime',
        'version',
        'uploaded_by_user_id',
        'shared_with_client',
    ];

    protected function casts(): array
    {
        return [
            'shared_with_client' => 'boolean',
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function signatureRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SignatureRequest::class);
    }
}
