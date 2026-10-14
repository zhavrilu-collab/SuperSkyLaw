<?php

namespace App\Services;

use App\Models\LawyerDirectoryEntry;
use App\Support\DirectoryText;

class LawyerDirectoryLookupService
{
    /**
     * @return list<array{id: int, name: string, office_kind: string, office_kind_label: string, address: ?string, city: ?string, phone: ?string}>
     */
    public function search(string $query, int $limit = 25): array
    {
        $tokens = array_values(array_filter(
            explode(' ', DirectoryText::fold($query)),
            fn (string $token) => $token !== '',
        ));

        if ($tokens === []) {
            return [];
        }

        $builder = LawyerDirectoryEntry::query()->orderBy('name');
        foreach ($tokens as $token) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $token).'%';
            $builder->where('search_normalized', 'like', $like);
        }

        return $builder
            ->limit($limit)
            ->get()
            ->map(fn (LawyerDirectoryEntry $entry) => [
                'id' => $entry->id,
                'name' => $entry->name,
                'office_kind' => $entry->office_kind->value,
                'office_kind_label' => $entry->office_kind->label(),
                'address' => $entry->address,
                'city' => $entry->city,
                'phone' => $entry->phone,
            ])
            ->all();
    }

    public function count(): int
    {
        return LawyerDirectoryEntry::query()->count();
    }
}
