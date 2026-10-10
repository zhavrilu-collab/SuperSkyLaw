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

    /** @return list<string> */
    public static function styleKeys(): array
    {
        return ThemeRecipes::styleKeys();
    }

    public static function resolve(?string $key): string
    {
        $key = $key ?: self::DEFAULT;

        return array_key_exists($key, self::all()) ? $key : self::DEFAULT;
    }

    /**
     * @return array{label: string, primary: string, dark: string, gold: string, light: string, text: string, accent: string, onPrimary: string, rgb: string}
     */
    public static function palette(?string $key, ?string $style = null): array
    {
        $resolved = self::resolve($key);
        $palette = ThemeRecipes::apply(self::all()[$resolved], $resolved, $style);

        if (! empty($palette['styled'])) {
            $palette['rgb'] = ThemeRecipes::rgb($palette['primary']);
        }

        return $palette;
    }

    /** @return array<string, array<string, string>> */
    public static function previewPayload(): array
    {
        $payload = [];

        foreach (self::all() as $key => $palette) {
            $payload[$key] = self::decoratePreview($palette, $key);
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    public static function clientPreview(?string $color = null, ?string $style = null): array
    {
        $resolved = self::resolve($color);

        return [
            'savedColor' => $resolved,
            'savedStyle' => ThemeRecipes::effectiveStyle($style),
            'palettes' => self::previewPayload(),
            'styles' => ThemeRecipes::styles(),
            'combinations' => self::combinationPayload(),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function combinationPayload(): array
    {
        $payload = [];

        foreach (self::keys() as $color) {
            foreach (ThemeRecipes::styleKeys() as $style) {
                $payload[$color.'|'.$style] = self::decoratePreview(self::palette($color, $style), $color);
            }
        }

        return $payload;
    }

    /** @param  array<string, mixed>  $palette */
    private static function decoratePreview(array $palette, string $colorKey): array
    {
        $rgb = $palette['rgb'] ?? ThemeRecipes::rgb($palette['primary']);

        return [
            'label' => $palette['label'],
            'styleLabel' => $palette['styleLabel'] ?? null,
            'styled' => (bool) ($palette['styled'] ?? false),
            'primary' => $palette['primary'],
            'dark' => $palette['dark'],
            'gold' => $palette['gold'],
            'light' => $palette['light'],
            'text' => $palette['text'],
            'accent' => $palette['accent'],
            'onPrimary' => $palette['onPrimary'],
            'rgb' => $rgb,
            'focusShadow' => 'rgba('.$rgb.', 0.15)',
            'tableBorder' => 'rgba('.$rgb.', 0.18)',
            'horizontalLogo' => asset(self::horizontalPath($colorKey)),
            'logoMark' => $palette['logoMark'] ?? null,
            'navBg' => $palette['navBg'] ?? null,
            'navFg' => $palette['navFg'] ?? null,
            'navBar' => $palette['navBar'] ?? null,
            'navWeight' => $palette['navWeight'] ?? null,
            'sideBg' => $palette['sideBg'] ?? null,
            'idle' => $palette['idle'] ?? null,
            'btnBg' => $palette['btnBg'] ?? null,
            'btnFg' => $palette['btnFg'] ?? null,
            'btnBorder' => $palette['btnBorder'] ?? null,
            'btnHoverBg' => $palette['btnHoverBg'] ?? null,
            'btnHoverFg' => $palette['btnHoverFg'] ?? null,
        ];
    }

    public static function verticalPath(?string $key): string
    {
        return 'brand/product/'.self::resolve($key).'.png';
    }

    public static function horizontalPath(?string $key): string
    {
        return 'brand/product/'.self::resolve($key).'-horizontal.png';
    }

    /**
     * Pale brand tint through to a dark brand ink, so a styled theme
     * whose primary is already dark still shows separate stages.
     *
     * @param  array<string, mixed>  $palette
     * @return list<string>
     */
    public static function stageShades(int $count, array $palette): array
    {
        $count = max(1, $count);
        $brand = $palette['logoMark'] ?? $palette['primary'];
        $light = self::mixHex('#ffffff', $brand, 0.48);
        $deep = self::mixHex($brand, '#12161a', 0.58);

        $shades = [];
        for ($index = 0; $index < $count; $index++) {
            $step = $count === 1 ? 0.15 : $index / ($count - 1);
            $shades[] = self::mixHex($light, $deep, $step);
        }

        return $shades;
    }

    private static function mixHex(string $from, string $to, float $amount): string
    {
        $amount = max(0, min(1, $amount));
        $start = self::hexChannels($from);
        $end = self::hexChannels($to);
        $channels = [];

        foreach ($start as $index => $channel) {
            $channels[] = (int) round($channel + ($end[$index] - $channel) * $amount);
        }

        return sprintf('#%02x%02x%02x', $channels[0], $channels[1], $channels[2]);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexChannels(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
