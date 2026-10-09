<?php

namespace App\Services;

use App\Enums\MatterKind;
use App\Models\DisputeCategory;
use App\Models\OfficeStageTemplate;
use App\Models\Organization;

class OfficeCatalog
{
    public function provision(Organization $organization): void
    {
        $this->templates($organization);
        $this->categories($organization);
    }

    private function templates(Organization $organization): void
    {
        if (OfficeStageTemplate::query()->where('organization_id', $organization->id)->exists()) {
            return;
        }

        $configured = config('matter_stages.templates');

        foreach (MatterKind::cases() as $kind) {
            $rows = $configured[$kind->value] ?? $configured['default'];

            foreach ($rows as $index => $row) {
                OfficeStageTemplate::query()->create([
                    'organization_id' => $organization->id,
                    'kind' => $kind->value,
                    'name' => $row['name'],
                    'position' => $index + 1,
                ]);
            }
        }
    }

    private function categories(Organization $organization): void
    {
        if (DisputeCategory::query()->where('organization_id', $organization->id)->exists()) {
            return;
        }

        $globals = DisputeCategory::query()->whereNull('organization_id')->orderBy('sort')->get();

        foreach ($globals as $global) {
            DisputeCategory::query()->create([
                'organization_id' => $organization->id,
                'kind' => $global->kind,
                'name' => $global->name,
                'hint' => $global->hint,
                'sort' => $global->sort,
                'active' => $global->active,
            ]);
        }
    }
}
