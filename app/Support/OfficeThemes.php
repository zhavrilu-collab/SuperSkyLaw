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
     * @return array<string, array{label: string, primary: string, dark: string, accent: string, light: string, text: string, rgb: string}>
     */
    public static function all(): array
    {
        return [
            'zelena' => [
                'label' => 'Zelena',
                'primary' => '#6b7a31',
                'dark' => '#515d25',
                'accent' => '#adc650',
                'light' => '#f6f9ed',
                'text' => '#4c5726',
                'rgb' => '107, 122, 49',
            ],
            'plava' => [
                'label' => 'Plava',
                'primary' => '#3b7ba0',
                'dark' => '#2d5e7a',
                'accent' => '#53acdf',
                'light' => '#edf6fb',
                'text' => '#2d586f',
                'rgb' => '59, 123, 160',
            ],
            'crvena' => [
                'label' => 'Crvena',
                'primary' => '#c43534',
                'dark' => '#a42c2b',
                'accent' => '#e24b48',
                'light' => '#f9eaea',
                'text' => '#862a28',
                'rgb' => '196, 53, 52',
            ],
            'zuta' => [
                'label' => 'Žuta',
                'primary' => '#84732e',
                'dark' => '#635623',
                'accent' => '#ffdf5a',
                'light' => '#fffbee',
                'text' => '#5c5324',
                'rgb' => '132, 115, 46',
            ],
            'narancasta' => [
                'label' => 'Narančasta',
                'primary' => '#a5652a',
                'dark' => '#7e4d20',
                'accent' => '#f3953e',
                'light' => '#fdf4eb',
                'text' => '#724a22',
                'rgb' => '165, 101, 42',
            ],
        ];
    }

    public static function resolve(?string $key): string
    {
        $key = $key ?: self::DEFAULT;

        return array_key_exists($key, self::all()) ? $key : self::DEFAULT;
    }

    /**
     * @return array{label: string, primary: string, dark: string, accent: string, light: string, text: string, rgb: string}
     */
    public static function palette(?string $key): array
    {
        return self::all()[self::resolve($key)];
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
