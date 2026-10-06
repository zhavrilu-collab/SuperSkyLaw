<?php

namespace App\Support;

class ThemeRecipes
{
    /** @return array<string, array{label: string, note: string}> */
    public static function styles(): array
    {
        return [
            'tiha' => ['label' => 'Tiha', 'note' => 'Manje zasićena, siva pozadina.'],
            'duboka' => ['label' => 'Duboka', 'note' => 'Težak gumb, nijansa pozadine.'],
            'kontrast' => ['label' => 'Kontrast', 'note' => 'Taman gumb, svijetla stranica.'],
            'topla' => ['label' => 'Topla', 'note' => 'Ton pomaknut prema zlatu i bakru.'],
            'hladna' => ['label' => 'Hladna', 'note' => 'Ton pomaknut prema kadulji i škriljevcu.'],
            'kreda' => ['label' => 'Kreda', 'note' => 'Odabir je pastel. Gumb je tamna tinta.'],
            'papir' => ['label' => 'Papir', 'note' => 'Cijela ploha je obojena, gumb je taman.'],
            'obrub' => ['label' => 'Obrub', 'note' => 'Gumb je bijel, boja je na rubu i crti.'],
            'pruga' => ['label' => 'Pruga', 'note' => 'Sučelje je sivo. Boja logotipa je crta.'],
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
     * @return array{primary: string, light: string, text: string, navBg: string, navFg: string, navBar: string, btnBg: string, btnFg: string, btnBorder: string}
     */
    private static function build(array $color, string $kind): array
    {
        $h = $color['h'];
        $s = $color['s'];
        $logo = $color['logo'];
        $tint = self::hsl($h, 32, 96);
        $ink = self::hsl($h, 28, 12);

        if ($kind === 'tiha') {
            return self::solid(self::hsl($h, $s * 0.28, 32), '#f4f3ef', '#1c1c1a');
        }

        if ($kind === 'duboka') {
            return self::solid(self::hsl($h, $s * 0.78, 22), $tint, $ink);
        }

        if ($kind === 'kontrast') {
            return self::solid(self::hsl($h, $s * 0.62, 16), '#f7f7f5', '#1c1c1a');
        }

        if ($kind === 'topla') {
            return self::solid(self::hsl(self::toward($h, 32, 0.55), 58, 30), '#f7f3ea', '#2a241c');
        }

        if ($kind === 'hladna') {
            return self::solid(self::hsl(self::toward($h, 168, 0.5), 38, 30), '#eef2f1', '#1a2220');
        }

        if ($kind === 'kreda') {
            $wash = self::hsl($h, 46, 86);
            $primary = self::hsl($h, $s * 0.72, 26);
            $buttonText = self::onColor($primary);

            return [
                'primary' => $primary,
                'light' => '#f7f7f5',
                'text' => '#1c1c1a',
                'navBg' => $wash,
                'navFg' => $ink,
                'navBar' => 'transparent',
                'btnBg' => $primary,
                'btnFg' => $buttonText,
                'btnBorder' => $primary,
            ];
        }

        if ($kind === 'papir') {
            return self::solid(self::hsl($h, $s * 0.7, 20), self::hsl($h, 48, 90), $ink);
        }

        if ($kind === 'obrub') {
            $primary = self::hsl($h, $s * 0.8, 24);

            return [
                'primary' => $primary,
                'light' => '#f7f7f5',
                'text' => '#1c1c1a',
                'navBg' => '#ffffff',
                'navFg' => '#1c1c1a',
                'navBar' => $logo,
                'btnBg' => '#ffffff',
                'btnFg' => $primary,
                'btnBorder' => $primary,
            ];
        }

        return [
            'primary' => '#2a2a28',
            'light' => '#f7f7f5',
            'text' => '#1c1c1a',
            'navBg' => '#ececea',
            'navFg' => '#1c1c1a',
            'navBar' => $logo,
            'btnBg' => '#2a2a28',
            'btnFg' => '#ffffff',
            'btnBorder' => '#2a2a28',
        ];
    }

    /** @return array{primary: string, light: string, text: string, navBg: string, navFg: string, navBar: string, btnBg: string, btnFg: string, btnBorder: string} */
    private static function solid(string $primary, string $light, string $text): array
    {
        $ink = self::onColor($primary);

        return [
            'primary' => $primary,
            'light' => $light,
            'text' => $text,
            'navBg' => $primary,
            'navFg' => $ink,
            'navBar' => 'transparent',
            'btnBg' => $primary,
            'btnFg' => $ink,
            'btnBorder' => $primary,
        ];
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

    private static function toward(float $h, float $target, float $amount): float
    {
        $delta = fmod($target - $h + 540, 360) - 180;

        return fmod($h + ($delta * $amount) + 360, 360);
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
