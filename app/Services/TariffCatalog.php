<?php

namespace App\Services;

use App\Models\TariffAction;
use App\Models\TariffBand;
use App\Models\TariffVersion;
use InvalidArgumentException;

class TariffCatalog
{
    public function install(): void
    {
        $versionData = config('hok_tariff.version');
        $version = TariffVersion::query()->updateOrCreate(
            ['code' => $versionData['code']],
            $versionData,
        );

        $version->bands()->delete();
        foreach (config('hok_tariff.bands') as $band) {
            $version->bands()->create($band);
        }

        foreach (config('hok_tariff.actions') as $action) {
            $version->actions()->updateOrCreate(
                ['code' => $action['code']],
                $action,
            );
        }
    }

    public function current(): TariffVersion
    {
        return TariffVersion::query()->orderByDesc('effective_from')->firstOrFail();
    }

    /**
     * @return array{points: int, amount_cents: int}
     */
    public function quote(TariffAction $action, ?int $disputeValueCents): array
    {
        $version = $action->version ?? TariffVersion::query()->findOrFail($action->tariff_version_id);
        $base = $action->kind === 'fixed'
            ? (int) $action->fixed_points
            : $this->bandPoints($version, $disputeValueCents);

        $points = (int) round($base * $action->multiplier_percent / 100);

        if ($action->max_points !== null) {
            $points = min($points, (int) $action->max_points);
        }

        return [
            'points' => $points,
            'amount_cents' => $points * (int) $version->point_value_cents,
        ];
    }

    public function bandPoints(TariffVersion $version, ?int $disputeValueCents): int
    {
        if ($disputeValueCents === null) {
            throw new InvalidArgumentException('Za ovu radnju treba vrijednost predmeta spora.');
        }

        $band = $version->bands()
            ->orderBy('value_from_cents')
            ->get()
            ->first(function (TariffBand $band) use ($disputeValueCents): bool {
                return $disputeValueCents >= $band->value_from_cents
                    && ($band->value_to_cents === null || $disputeValueCents <= $band->value_to_cents);
            });

        if ($band === null) {
            throw new InvalidArgumentException('Vrijednost spora nije u katalogu tarife.');
        }

        $points = (int) $band->base_points;

        if ($band->step_cents) {
            $excess = max(0, $disputeValueCents - (int) $band->threshold_cents);
            $points += (int) ceil($excess / $band->step_cents) * (int) $band->step_points;
        }

        if ($band->max_points !== null) {
            $points = min($points, (int) $band->max_points);
        }

        return $points;
    }
}
