<?php

namespace App\Services;

use App\Enums\TrustDirection;
use App\Models\TrustMovement;
use Illuminate\Validation\ValidationException;

class TrustLedger
{
    public function balance(int $organizationId, ?int $matterId = null): int
    {
        $query = TrustMovement::query()->where('organization_id', $organizationId);
        if ($matterId !== null) {
            $query->where('matter_id', $matterId);
        }

        $in = (clone $query)->where('direction', TrustDirection::In->value)->sum('amount_cents');
        $out = (clone $query)->where('direction', TrustDirection::Out->value)->sum('amount_cents');

        return (int) $in - (int) $out;
    }

    public function post(
        int $organizationId,
        int $matterId,
        TrustDirection $direction,
        int $amountCents,
        string $purpose,
        ?string $counterparty,
    ): TrustMovement {
        if ($direction === TrustDirection::Out) {
            $matterBalance = $this->balance($organizationId, $matterId);
            $officeBalance = $this->balance($organizationId);
            if ($amountCents > $matterBalance || $amountCents > $officeBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'Depozitni račun nema dovoljno sredstava. Poslovni IBAN se ne dira.',
                ]);
            }
        }

        return TrustMovement::query()->create([
            'organization_id' => $organizationId,
            'matter_id' => $matterId,
            'direction' => $direction,
            'amount_cents' => $amountCents,
            'occurred_on' => now()->toDateString(),
            'counterparty' => $counterparty,
            'purpose' => $purpose,
            'created_by_user_id' => auth()->id(),
        ]);
    }
}
