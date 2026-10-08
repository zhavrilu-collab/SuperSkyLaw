<?php

namespace App\Models;

use App\Enums\OfficeKind;
use App\Enums\OrganizationStatus;
use App\Support\OfficeThemes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'office_kind',
        'slug',
        'status',
        'plan',
        'trial_ends_at',
        'status_changed_at',
        'email',
        'oib',
        'mbs',
        'phone',
        'city',
        'address',
        'iban',
        'trust_iban',
        'stripe_customer_id',
        'stripe_subscription_id',
        'calendar_feed_token',
        'mail_intake_token',
        'theme_color',
        'theme_style',
    ];

    protected function casts(): array
    {
        return [
            'office_kind' => OfficeKind::class,
            'status' => OrganizationStatus::class,
            'status_changed_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function navbarBrandPrefix(): string
    {
        return mb_strtoupper($this->name);
    }

    public function themeColor(): string
    {
        return OfficeThemes::resolve($this->theme_color);
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function trialExpired(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }

    public function feedToken(string $column): string
    {
        if ($this->{$column} === null || $this->{$column} === '') {
            $this->forceFill([$column => Str::random(48)])->save();
        }

        return (string) $this->{$column};
    }

    /**
     * @return HasMany<OrganizationUser, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }
}
