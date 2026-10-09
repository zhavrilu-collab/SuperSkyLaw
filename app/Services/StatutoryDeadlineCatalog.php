<?php

namespace App\Services;

use App\Models\Matter;

class StatutoryDeadlineCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forMatter(Matter $matter): array
    {
        $category = $matter->disputeCategory?->name;
        $kind = $matter->kind->value;

        return array_values(array_filter(
            config('statutory_deadlines.rules'),
            function (array $rule) use ($kind, $category): bool {
                if ($rule['kind'] !== $kind) {
                    return false;
                }

                return $rule['category'] === null || $rule['category'] === $category;
            },
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        foreach (config('statutory_deadlines.rules') as $rule) {
            if ($rule['key'] === $key) {
                return $rule;
            }
        }

        return null;
    }
}
