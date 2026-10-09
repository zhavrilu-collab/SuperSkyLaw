<?php

namespace App\Services;

use App\Models\Organization;

class PlanFeatureService
{
    public function allows(Organization $organization, string $feature): bool
    {
        $value = $this->value($organization, $feature);

        if (is_bool($value)) {
            return $value;
        }

        return $value === null || (int) $value > 0;
    }

    public function limit(Organization $organization, string $feature): ?int
    {
        $value = $this->value($organization, $feature);

        return $value === null ? null : (int) $value;
    }

    public function value(Organization $organization, string $feature): mixed
    {
        $plan = $organization->plan ?: 'basic';
        $features = config('plan_features.'.$plan, config('plan_features.basic'));

        return array_key_exists($feature, $features) ? $features[$feature] : false;
    }
}
