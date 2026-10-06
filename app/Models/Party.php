<?php

namespace App\Models;

use App\Enums\PartyKind;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'kind',
        'name',
        'oib',
        'mbs',
        'address',
        'city',
        'email',
        'phone',
        'sms_consent_at',
        'iban',
        'contact_person',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PartyKind::class,
            'sms_consent_at' => 'datetime',
        ];
    }

    public function matterParties(): HasMany
    {
        return $this->hasMany(MatterParty::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }
}
