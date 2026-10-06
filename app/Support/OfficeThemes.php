<?php

namespace App\Support;

class OfficeThemes
{
    public const DEFAULT = 'zelena';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string, onPrimary: string, rgb: string}>
     */
    public static function all(): array
    {
        return [
            'zelena' => [
                'label' => 'Zelena',
                'primary' => '#b0cb1f',
                'dark' => '#434d0c',
                'gold' => '#ffd310',
                'light' => '#f7fae9',
                'text' => '#272d07',
                'accent' => '#e31e24',
                'onPrimary' => '#1a1a1a',
                'rgb' => '176, 203, 31',
            ],
            'plava' => [
                'label' => 'Plava',
                'primary' => '#50abde',
                'dark' => '#1e4154',
                'gold' => '#ffd310',
                'light' => '#eef7fc',
                'text' => '#122631',
                'accent' => '#e31e24',
                'onPrimary' => '#1a1a1a',
                'rgb' => '80, 171, 222',
            ],
            'crvena' => [
                'label' => 'Crvena',
                'primary' => '#e31e24',
                'dark' => '#560b0e',
                'gold' => '#ffd310',
                'light' => '#fce8e9',
                'text' => '#320708',
                'accent' => '#560b0e',
                'onPrimary' => '#ffffff',
                'rgb' => '227, 30, 36',
            ],
            'zuta' => [
                'label' => 'Žuta',
                'primary' => '#ffd310',
                'dark' => '#615006',
                'gold' => '#ef7f1a',
                'light' => '#fffbe7',
                'text' => '#382e04',
                'accent' => '#e31e24',
                'onPrimary' => '#1a1a1a',
                'rgb' => '255, 211, 16',
            ],
            'narancasta' => [
                'label' => 'Narančasta',
                'primary' => '#ef7f1a',
                'dark' => '#5b300a',
                'gold' => '#ffd310',
                'light' => '#fdf2e8',
                'text' => '#351c06',
                'accent' => '#e31e24',
                'onPrimary' => '#1a1a1a',
                'rgb' => '239, 127, 26',
            ],
        ];
    }

    public static function resolve(?string $key): string
    {
        $key = $key ?: self::DEFAULT;

        return array_key_exists($key, self::all()) ? $key : self::DEFAULT;
    }

    /**
     * @return array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string, onPrimary: string, rgb: string}
     */
    public static function palette(?string $key): array
    {
        return self::all()[self::resolve($key)];
    }

    /** @return array<string, array<string, string>> */
    public static function previewPayload(): array
    {
        $payload = [];

        foreach (self::all() as $key => $palette) {
            $payload[$key] = [
                'label' => $palette['label'],
                'primary' => $palette['primary'],
                'dark' => $palette['dark'],
                'gold' => $palette['gold'],
                'light' => $palette['light'],
                'text' => $palette['text'],
                'accent' => $palette['accent'],
                'onPrimary' => $palette['onPrimary'],
                'rgb' => $palette['rgb'],
                'focusShadow' => 'rgba('.$palette['rgb'].', 0.15)',
                'tableBorder' => 'rgba('.$palette['rgb'].', 0.18)',
                'horizontalLogo' => asset(self::horizontalPath($key)),
            ];
        }

        return $payload;
    }

    public static function verticalPath(?string $key): string
    {
        return 'brand/product/'.self::resolve($key).'.png';
    }

    public static function horizontalPath(?string $key): string
    {
        return 'brand/product/'.self::resolve($key).'-horizontal.png';
    }
}
