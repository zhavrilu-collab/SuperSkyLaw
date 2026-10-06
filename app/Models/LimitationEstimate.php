<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitationEstimate extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'basis',
        'starts_on',
        'suggested_on',
        'court_event_id',
        'confirmed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'suggested_on' => 'date',
        ];
    }

    public function basisLabel(): string
    {
        return (string) config('limitation.bases.'.$this->basis.'.label', $this->basis);
    }
}
