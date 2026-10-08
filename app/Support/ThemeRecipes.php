<?php

namespace App\Support;

class ThemeRecipes
{
    public const DEFAULT_STYLE = 'pruga';

    /** @return array<string, array{label: string, note: string}> */
    public static function styles(): array
    {
        return [
            'kreda' => ['label' => 'Kreda', 'note' => 'Odabir je pastel. Gumb je tamna tinta.'],
            'obrub' => ['label' => 'Obrub', 'note' => 'Gumb je bijel, boja je na rubu i crti.'],
            'pruga' => ['label' => 'Pruga', 'note' => 'Sučelje je sivo. Boja logotipa je crta.'],
            'sjena' => ['label' => 'Sjena', 'note' => 'Cijeli izbornik je pastel. Odabrana stavka je bijela.'],
            'slovo' => ['label' => 'Slovo', 'note' => 'Odabir nema podlogu. Obojen je samo tekst stavke.'],
            'noc' => ['label' => 'Noć', 'note' => 'Izbornik je taman. Stranica i gumb ostaju svijetli.'],
        ];
    }

    /** @return list<string> */
    public static function styleKeys(): array
    {
        return array_keys(self::styles());
    }

    public static function resolveStyle(?string $style): ?string
    {
        if ($style === null || $style === '') {
            return null;
        }

        return array_key_exists($style, self::styles()) ? $style : null;
    }

    public static function effectiveStyle(?string $style): string
    {
        return self::resolveStyle($style) ?? self::DEFAULT_STYLE;
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    public static function apply(array $base, string $colorKey, ?string $style): array
    {
        $style = self::resolveStyle($style);
        $hue = self::hues()[$colorKey] ?? null;

        if ($style === null || $hue === null) {
            return $base;
        }

        $built = self::build($hue, $style);
        $primary = $built['primary'];
        $hover = self::hover($built['btnBg'], $primary);

        return array_merge($base, [
            'primary' => $primary,
            'dark' => self::shade($primary, -0.28),
            'light' => $built['light'],
            'text' => $built['text'],
            'onPrimary' => self::onColor($primary),
            'styled' => true,
            'style' => $style,
            'styleLabel' => self::styles()[$style]['label'],
            'logoMark' => $hue['logo'],
            'navBg' => $built['navBg'],
            'navFg' => $built['navFg'],
            'navBar' => $built['navBar'],
            'navWeight' => $built['navWeight'],
            'sideBg' => $built['sideBg'],
            'idle' => $built['idle'],
            'btnBg' => $built['btnBg'],
            'btnFg' => $built['btnFg'],
            'btnBorder' => $built['btnBorder'],
            'btnHoverBg' => $hover['bg'],
            'btnHoverFg' => $hover['fg'],
        ]);
    }

    /** @param  array<string, mixed>  $palette */
    public static function chromeDeclarations(array $palette): string
    {
        if (empty($palette['styled'])) {
            return '';
        }

        $lines = [
            '--gumb-pozadina: '.$palette['btnBg'],
            '--gumb-tekst: '.$palette['btnFg'],
            '--gumb-rub: '.$palette['btnBorder'],
            '--gumb-debljina: 2px',
            '--gumb-hover-pozadina: '.$palette['btnHoverBg'],
            '--gumb-hover-tekst: '.$palette['btnHoverFg'],
            '--odabir-pozadina: '.$palette['navBg'],
            '--odabir-tekst: '.$palette['navFg'],
            '--odabir-crta: '.$palette['navBar'],
            '--odabir-tezina: '.$palette['navWeight'],
            '--izbornik-pozadina: '.$palette['sideBg'],
            '--izbornik-tekst: '.$palette['idle'],
            '--postavke-odabir-pozadina: '.$palette['navBg'],
            '--postavke-odabir-tekst: '.$palette['navFg'],
            '--postavke-odabir-crta: '.$palette['navBar'],
        ];

        return implode(";\n        ", $lines).';';
    }

    public static function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        return hexdec(substr($hex, 0, 2)).', '.hexdec(substr($hex, 2, 2)).', '.hexdec(substr($hex, 4, 2));
    }

    /** @return array<string, array{h: float, s: float, logo: string}> */
    private static function hues(): array
    {
        return [
            'zelena' => ['h' => 69, 's' => 73, 'logo' => '#b0cb1f'],
            'plava' => ['h' => 201, 's' => 68, 'logo' => '#50abde'],
            'crvena' => ['h' => 358, 's' => 78, 'logo' => '#e31e24'],
            'zuta' => ['h' => 49, 's' => 100, 'logo' => '#ffd310'],
            'narancasta' => ['h' => 28, 's' => 87, 'logo' => '#ef7f1a'],
        ];
    }

    /**
     * @param  array{h: float, s: float, logo: string}  $color
     * @return array{primary: string, light: string, text: string, navBg: string, navFg: string, navBar: string, navWeight: string, sideBg: string, idle: string, btnBg: string, btnFg: string, btnBorder: string}
     */
    private static function build(array $color, string $kind): array
    {
        $h = $color['h'];
        $s = $color['s'];
        $logo = $color['logo'];
        $ink = self::hsl($h, 28, 12);
        $paper = [
            'light' => '#f7f7f5',
            'text' => '#1c1c1a',
            'sideBg' => '#ffffff',
            'idle' => '#2a2a28',
            'navWeight' => '650',
        ];

        if ($kind === 'kreda') {
            $wash = self::hsl($h, 46, 86);
            $primary = self::hsl($h, $s * 0.72, 26);

            return array_merge($paper, [
                'primary' => $primary,
                'navBg' => $wash,
                'navFg' => $ink,
                'navBar' => 'transparent',
                'btnBg' => $primary,
                'btnFg' => self::onColor($primary),
                'btnBorder' => $primary,
            ]);
        }

        if ($kind === 'obrub') {
            $primary = self::hsl($h, $s * 0.8, 24);

            return array_merge($paper, [
                'primary' => $primary,
                'navBg' => '#ffffff',
                'navFg' => '#1c1c1a',
                'navBar' => $logo,
                'btnBg' => '#ffffff',
                'btnFg' => $primary,
                'btnBorder' => $primary,
            ]);
        }

        if ($kind === 'pruga') {
            return array_merge($paper, [
                'primary' => '#2a2a28',
                'navBg' => '#ececea',
                'navFg' => '#1c1c1a',
                'navBar' => $logo,
                'btnBg' => '#2a2a28',
                'btnFg' => '#ffffff',
                'btnBorder' => '#2a2a28',
            ]);
        }

        if ($kind === 'sjena') {
            $primary = self::hsl($h, $s * 0.7, 26);

            return array_merge($paper, [
                'primary' => $primary,
                'sideBg' => self::hsl($h, 38, 88),
                'idle' => $ink,
                'navBg' => '#ffffff',
                'navFg' => $ink,
                'navBar' => 'transparent',
                'btnBg' => $primary,
                'btnFg' => self::onColor($primary),
                'btnBorder' => $primary,
            ]);
        }

        if ($kind === 'slovo') {
            $primary = self::hsl($h, $s * 0.78, 28);

            return array_merge($paper, [
                'primary' => $primary,
                'navBg' => 'transparent',
                'navFg' => $primary,
                'navBar' => 'transparent',
                'navWeight' => '700',
                'btnBg' => $primary,
                'btnFg' => self::onColor($primary),
                'btnBorder' => $primary,
            ]);
        }

        $side = self::hsl($h, 24, 13);

        return array_merge($paper, [
            'primary' => $side,
            'sideBg' => $side,
            'idle' => '#eceae4',
            'navBg' => self::hsl($h, 18, 20),
            'navFg' => '#ffffff',
            'navBar' => $logo,
            'btnBg' => '#ffffff',
            'btnFg' => $side,
            'btnBorder' => $side,
        ]);
    }

    /** @return array{bg: string, fg: string} */
    private static function hover(string $buttonBackground, string $primary): array
    {
        if (in_array(strtolower($buttonBackground), ['#fff', '#ffffff'], true)) {
            return ['bg' => $primary, 'fg' => self::onColor($primary)];
        }

        $background = self::shade($buttonBackground, -0.16);

        return ['bg' => $background, 'fg' => self::onColor($background)];
    }

    private static function hsl(float $h, float $s, float $l): string
    {
        $h = fmod($h, 360);
        if ($h < 0) {
            $h += 360;
        }

        $s = max(0, min(100, $s)) / 100;
        $l = max(0, min(100, $l)) / 100;
        $a = $s * min($l, 1 - $l);
        $hex = '#';

        foreach ([0, 8, 4] as $n) {
            $k = fmod($n + ($h / 30), 12);
            if ($k < 0) {
                $k += 12;
            }

            $channel = $l - $a * max(min($k - 3, min(9 - $k, 1)), -1);
            $hex .= sprintf('%02x', (int) round(255 * max(0, min(1, $channel))));
        }

        return $hex;
    }

    private static function onColor(string $hex): string
    {
        $luminance = self::luminance($hex);
        $white = 1.05 / ($luminance + 0.05);
        $black = ($luminance + 0.05) / 0.05;

        return $white >= $black ? '#ffffff' : '#1a1a1a';
    }

    private static function luminance(string $hex): float
    {
        $value = hexdec(ltrim($hex, '#'));
        $channels = [($value >> 16) & 255, ($value >> 8) & 255, $value & 255];
        $linear = array_map(static function (int $channel): float {
            $channel /= 255;

            return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, $channels);

        return (0.2126 * $linear[0]) + (0.7152 * $linear[1]) + (0.0722 * $linear[2]);
    }

    private static function shade(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#'.$hex;
        }

        $adjust = static fn (string $pair): int => max(0, min(255, (int) round(hexdec($pair) + ($amount * 255))));

        return sprintf('#%02x%02x%02x', $adjust(substr($hex, 0, 2)), $adjust(substr($hex, 2, 2)), $adjust(substr($hex, 4, 2)));
    }
}
