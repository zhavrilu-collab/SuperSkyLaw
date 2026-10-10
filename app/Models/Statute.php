<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Statute extends Model
{
    protected $fillable = [
        'external_id',
        'work_id',
        'amends_external_id',
        'title',
        'citation',
        'document_type',
        'published_on',
        'source_url',
        'text_html',
        'text_plain',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'published_on' => 'date',
            'fetched_at' => 'datetime',
        ];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(StatuteWork::class, 'work_id');
    }

    public function matters(): BelongsToMany
    {
        return $this->belongsToMany(Matter::class, 'matter_statute')->withTimestamps();
    }
}
