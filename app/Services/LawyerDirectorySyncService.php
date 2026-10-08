<?php

namespace App\Services;

use App\Enums\OfficeKind;
use App\Models\LawyerDirectoryEntry;
use App\Support\DirectoryText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LawyerDirectorySyncService
{
    public function __construct(private readonly LawyerDirectorySpreadsheet $spreadsheet) {}

    public function sync(?string $path = null): int
    {
        $temporary = null;
        $source = $path ?: (string) config('lawyer_directory.local_path');

        if ($source === '') {
            $temporary = $this->download();
            $source = $temporary;
        }

        try {
            $rows = $this->records($this->spreadsheet->read($source));
        } finally {
            if ($temporary !== null && is_file($temporary)) {
                unlink($temporary);
            }
        }

        if ($rows === []) {
            throw new RuntimeException('Imenik ne sadrži nijedan ured koji se može prijaviti.');
        }

        DB::transaction(function () use ($rows): void {
            LawyerDirectoryEntry::query()->delete();
            foreach (array_chunk($rows, 400) as $chunk) {
                LawyerDirectoryEntry::query()->insert($chunk);
            }
        });

        return count($rows);
    }

    /**
     * @param  list<array{name: string, status: string, address: ?string, city: ?string, phone: ?string}>  $rows
     * @return list<array<string, mixed>>
     */
    private function records(array $rows): array
    {
        $now = now()->toDateTimeString();
        $records = [];
        $seen = [];

        foreach ($rows as $row) {
            $kind = OfficeKind::fromDirectoryStatus($row['status']);
            if ($kind === null) {
                continue;
            }

            $place = DirectoryText::place($row['address'], $row['city']);
            $sourceKey = sha1(implode('|', [
                $kind->value,
                DirectoryText::fold($row['name']),
                DirectoryText::fold((string) $place['address']),
                DirectoryText::fold((string) $place['city']),
            ]));

            if (isset($seen[$sourceKey])) {
                continue;
            }
            $seen[$sourceKey] = true;

            $records[] = [
                'source_key' => $sourceKey,
                'name' => $row['name'],
                'office_kind' => $kind->value,
                'address' => $place['address'],
                'city' => $place['city'],
                'phone' => $row['phone'],
                'search_normalized' => mb_substr(DirectoryText::fold(implode(' ', array_filter([
                    $row['name'],
                    $place['city'],
                    $place['address'],
                ]))), 0, 255),
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $records;
    }

    private function download(): string
    {
        $url = (string) config('lawyer_directory.xls_url');
        if ($url === '') {
            throw new RuntimeException('Adresa imenika nije postavljena.');
        }

        $target = tempnam(sys_get_temp_dir(), 'hok-imenik');
        if ($target === false) {
            throw new RuntimeException('Privremena datoteka imenika nije stvorena.');
        }

        $response = Http::timeout(90)
            ->sink($target)
            ->get($url);

        if (! $response->successful()) {
            unlink($target);
            throw new RuntimeException('Imenik Hrvatske odvjetničke komore nije preuzet.');
        }

        return $target;
    }
}
