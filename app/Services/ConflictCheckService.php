<?php

namespace App\Services;

use App\Enums\ConflictResult;
use App\Enums\PartySide;
use App\Models\Matter;
use App\Models\Party;

class ConflictCheckService
{
    /**
     * @param  list<array{name: string, oib: ?string, side: PartySide}>  $subjects
     * @return array{result: ConflictResult, matches: list<array<string, mixed>>}
     */
    public function evaluate(array $subjects): array
    {
        $matches = [];
        $result = ConflictResult::Clear;

        foreach ($subjects as $subject) {
            $name = trim($subject['name']);
            $oib = $subject['oib'] !== null && $subject['oib'] !== '' ? $subject['oib'] : null;
            $side = $subject['side'];

            if ($name === '' && $oib === null) {
                continue;
            }

            $parties = Party::query()
                ->with(['matterParties.matter.ethicalWalls'])
                ->when($oib !== null, fn ($query) => $query->where('oib', $oib))
                ->when($oib === null, fn ($query) => $query->whereRaw('lower(name) = ?', [mb_strtolower($name)]))
                ->get();

            if ($oib !== null && $name !== '') {
                $nameMatches = Party::query()
                    ->with(['matterParties.matter.ethicalWalls'])
                    ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
                    ->where(function ($query) use ($oib) {
                        $query->whereNull('oib')->orWhere('oib', '!=', $oib);
                    })
                    ->get();

                foreach ($nameMatches as $party) {
                    $matches[] = $this->matchRow($party, $side, 'potential', 'Ime se podudara, OIB se razlikuje ili nedostaje.');
                    $result = $this->raise($result, ConflictResult::Potential);
                }
            }

            foreach ($parties as $party) {
                $linked = $party->matterParties;

                if ($linked->isEmpty()) {
                    continue;
                }

                foreach ($linked as $link) {
                    $hidden = $this->hiddenByWall($link->matter);
                    $number = $hidden ? null : $link->matter->internal_number;
                    if ($oib !== null && $side->conflictsWith($link->side)) {
                        $reason = $hidden
                            ? 'Isti OIB je na predmetu iza etičkog zida.'
                            : 'Isti OIB je na predmetu '.$number.' kao '.$link->side->label().'.';
                        $matches[] = $this->matchRow($party, $side, 'hard', $reason, $number);
                        $result = $this->raise($result, ConflictResult::Hard);
                    } elseif ($oib === null) {
                        $reason = $hidden
                            ? 'Isto ime je na predmetu iza etičkog zida.'
                            : 'Isto ime je na predmetu '.$number.' bez potvrde OIB-a.';
                        $matches[] = $this->matchRow($party, $side, 'potential', $reason, $number);
                        $result = $this->raise($result, ConflictResult::Potential);
                    }
                }
            }
        }

        return [
            'result' => $result,
            'matches' => $matches,
        ];
    }

    private function hiddenByWall(?Matter $matter): bool
    {
        $userId = auth()->id();
        if ($matter === null || $userId === null) {
            return false;
        }

        return $matter->ethicalWalls->contains(fn ($wall) => (int) $wall->user_id === (int) $userId);
    }

    private function raise(ConflictResult $current, ConflictResult $next): ConflictResult
    {
        if ($current === ConflictResult::Hard || $next === ConflictResult::Hard) {
            return ConflictResult::Hard;
        }

        if ($current === ConflictResult::Potential || $next === ConflictResult::Potential) {
            return ConflictResult::Potential;
        }

        return ConflictResult::Clear;
    }

    /**
     * @return array<string, mixed>
     */
    private function matchRow(Party $party, PartySide $side, string $level, string $reason, ?string $matterNumber = null): array
    {
        return [
            'party_id' => $party->id,
            'name' => $party->name,
            'oib' => $party->oib,
            'side' => $side->value,
            'level' => $level,
            'reason' => $reason,
            'matter_number' => $matterNumber,
        ];
    }
}
