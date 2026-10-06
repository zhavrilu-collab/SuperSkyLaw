<?php

namespace App\Models;

use App\Enums\AuditAction;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(AuditAction $action, Model $model, string $description): void
    {
        if (! app()->bound('currentOrganization')) {
            return;
        }

        static::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'description' => $description,
        ]);
    }
}
