<?php

namespace App\Services;

use App\Models\Matter;
use App\Models\MatterStage;
use Illuminate\Support\Collection;

class MatterStagePlan
{
    public function open(Matter $matter): MatterStage
    {
        $template = $this->templates($matter)[0];

        return $matter->stages()->create([
            'name' => $template['name'],
            'color' => $template['color'],
            'started_on' => now()->toDateString(),
            'position' => 1,
        ]);
    }

    public function suggestion(Matter $matter): ?string
    {
        $used = $matter->stages->pluck('name');

        foreach ($this->templates($matter) as $template) {
            if (! $used->contains($template['name'])) {
                return $template['name'];
            }
        }

        return null;
    }

    public function colorFor(int $position): string
    {
        $colors = config('matter_stages.colors');

        return $colors[($position - 1) % count($colors)];
    }

    /**
     * @param  Collection<int, MatterStage>  $stages
     * @return list<float>
     */
    public function shares(Collection $stages): array
    {
        $weights = $stages->map(function (MatterStage $stage): int {
            $start = $stage->started_on->copy()->startOfDay();
            $end = ($stage->ended_on?->copy() ?? now())->startOfDay();
            if ($end->lt($start)) {
                $end = $start->copy();
            }

            return max(1, (int) $start->diffInDays($end) + 1);
        });
        $total = max(1, (int) $weights->sum());

        return $weights->map(fn (int $weight): float => round($weight / $total * 100, 2))->values()->all();
    }

    /**
     * @return list<array{name: string, color: string}>
     */
    private function templates(Matter $matter): array
    {
        return config('matter_stages.templates.'.$matter->kind->value)
            ?? config('matter_stages.templates.default');
    }
}
