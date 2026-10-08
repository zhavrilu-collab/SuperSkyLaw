<?php

namespace App\Support;

class DirectoryText
{
    public static function fold(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'č' => 'c',
            'ć' => 'c',
            'đ' => 'd',
            'š' => 's',
            'ž' => 'z',
        ]);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * @return array{address: ?string, city: ?string}
     */
    public static function place(?string $address, ?string $city): array
    {
        $lines = preg_split('/\R/u', trim((string) $address)) ?: [];
        $lines = array_values(array_filter(array_map(trim(...), $lines), fn (string $line) => $line !== ''));
        $city = trim((string) $city);
        $postal = null;
        $street = [];

        foreach ($lines as $line) {
            if (preg_match('/^(\d{5})\s+(.+)$/u', $line, $matches) === 1) {
                $postal = $matches[1];
                if ($city === '') {
                    $city = trim($matches[2]);
                }

                continue;
            }

            $street[] = $line;
        }

        $streetText = implode(', ', $street);
        $place = $city;
        if ($postal !== null && $city !== '' && preg_match('/^\d{5}\b/u', $city) !== 1) {
            $place = $postal.' '.$city;
        } elseif ($postal !== null && $city === '') {
            $place = $postal;
        }

        return [
            'address' => $streetText !== '' ? $streetText : null,
            'city' => $place !== '' ? $place : null,
        ];
    }
}
