<?php

namespace App\Services;

use App\Support\DirectoryText;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class LawyerDirectorySpreadsheet
{
    /**
     * @return list<array{name: string, status: string, address: ?string, city: ?string, phone: ?string}>
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Datoteka imenika nije pronađena.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $table = $extension === 'csv' ? $this->csv($path) : $this->workbook($path);

        return $this->extract($table);
    }

    /**
     * @return list<list<string>>
     */
    private function csv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Imenik se ne može otvoriti.');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($cell) => trim((string) $cell), $row);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function workbook(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $row) {
            $rows[] = array_map(fn ($cell) => trim((string) $cell), array_values($row));
        }

        return $rows;
    }

    /**
     * @param  list<list<string>>  $table
     * @return list<array{name: string, status: string, address: ?string, city: ?string, phone: ?string}>
     */
    private function extract(array $table): array
    {
        $header = null;
        $headerIndex = null;

        foreach (array_slice($table, 0, 25, true) as $index => $row) {
            $mapped = $this->headerMap($row);
            if ($mapped !== null) {
                $header = $mapped;
                $headerIndex = $index;
                break;
            }
        }

        if ($header === null) {
            $header = ['name' => 0, 'status' => 1, 'address' => 3, 'city' => 4, 'phone' => 5];
            $headerIndex = -1;
        }

        $entries = [];
        foreach ($table as $index => $row) {
            if ($index <= $headerIndex) {
                continue;
            }

            $name = $this->cell($row, $header['name'] ?? null);
            $status = $this->cell($row, $header['status'] ?? null);
            if ($name === '' || $status === '') {
                continue;
            }

            $entries[] = [
                'name' => $name,
                'status' => $status,
                'address' => $this->nullable($this->cell($row, $header['address'] ?? null)),
                'city' => $this->nullable($this->cell($row, $header['city'] ?? null)),
                'phone' => $this->nullable($this->cell($row, $header['phone'] ?? null)),
            ];
        }

        return $entries;
    }

    /**
     * @param  list<string>  $row
     * @return array{name: int, status: int, address?: int, city?: int, phone?: int}|null
     */
    private function headerMap(array $row): ?array
    {
        $map = [];
        foreach ($row as $index => $cell) {
            $label = DirectoryText::fold($cell);
            if ($label === '') {
                continue;
            }

            if (! isset($map['name']) && (str_contains($label, 'naziv') || str_contains($label, 'ime'))) {
                $map['name'] = $index;
            } elseif (! isset($map['status']) && (str_contains($label, 'status') || str_contains($label, 'kategor'))) {
                $map['status'] = $index;
            } elseif (! isset($map['address']) && str_contains($label, 'adresa')) {
                $map['address'] = $index;
            } elseif (! isset($map['city']) && (str_contains($label, 'mjesto') || str_contains($label, 'grad'))) {
                $map['city'] = $index;
            } elseif (! isset($map['phone']) && str_contains($label, 'telefon')) {
                $map['phone'] = $index;
            }
        }

        if (! isset($map['name'], $map['status'])) {
            return null;
        }

        return $map;
    }

    /**
     * @param  list<string>  $row
     */
    private function cell(array $row, ?int $index): string
    {
        if ($index === null) {
            return '';
        }

        return trim($row[$index] ?? '');
    }

    private function nullable(string $value): ?string
    {
        return $value !== '' ? $value : null;
    }
}
