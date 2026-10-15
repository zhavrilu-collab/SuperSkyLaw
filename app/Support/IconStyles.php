<?php

namespace App\Support;

class IconStyles
{
    public const DEFAULT = 'ispuna';

    /**
     * @return array<string, array{label: string}>
     */
    public static function all(): array
    {
        return [
            'ispuna' => ['label' => 'Ispuna'],
            'tanka' => ['label' => 'Tanka linija'],
            'krug' => ['label' => 'Znak u krugu'],
            'plocica' => ['label' => 'Pločica'],
            'prijelaz' => ['label' => 'Prijelaz mišem'],
            'krupno' => ['label' => 'Krupniji znak'],
            'negativ' => ['label' => 'Negativ'],
            'kvadrat' => ['label' => 'Oštri kvadrat'],
            'dijamant' => ['label' => 'Dijamant'],
            'prsten' => ['label' => 'Dvostruki prsten'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function resolve(?string $style): string
    {
        return array_key_exists((string) $style, self::all()) ? (string) $style : self::DEFAULT;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function cssVariables(): array
    {
        $fill = self::defaults();
        $stroke = array_merge($fill, [
            '--ikona-fill' => 'none',
            '--ikona-stroke' => 'currentColor',
            '--ikona-stroke-width' => '1.5',
        ]);

        return [
            'ispuna' => $fill,
            'tanka' => array_merge($stroke, [
                '--ikona-glyph' => '16px',
                '--ikona-stroke-width' => '1.25',
                '--ikona-bg-hover' => 'transparent',
            ]),
            'krug' => array_merge($stroke, [
                '--ikona-glyph' => '14px',
                '--ikona-radius' => '999px',
                '--ikona-border' => '1px solid currentColor',
            ]),
            'plocica' => array_merge($fill, [
                '--ikona-glyph' => '13px',
                '--ikona-bg' => '#f3f6ea',
                '--ikona-bg-hover' => '#e7eed4',
            ]),
            'prijelaz' => array_merge($stroke, [
                '--ikona-bg' => 'transparent',
                '--ikona-bg-hover' => '#f3f6ea',
            ]),
            'krupno' => array_merge($fill, [
                '--ikona-size' => '32px',
                '--ikona-glyph' => '18px',
            ]),
            'negativ' => array_merge($fill, [
                '--ikona-glyph' => '13px',
                '--ikona-radius' => '999px',
                '--ikona-bg' => '#5c6540',
                '--ikona-bg-hover' => 'var(--primarna-tamna)',
                '--ikona-fg' => '#fff',
                '--ikona-fg-hover' => '#fff',
                '--ikona-opasno-fg' => '#fff',
                '--ikona-opasno-bg' => '#9b1c1c',
                '--ikona-opasno-hover-bg' => '#7a1515',
                '--ikona-opasno-hover-fg' => '#fff',
                '--ikona-gotovo-fg' => '#fff',
                '--ikona-gotovo-bg' => '#2f6b3a',
                '--ikona-gotovo-hover-bg' => '#245530',
                '--ikona-gotovo-hover-fg' => '#fff',
                '--ikona-disabled-fg' => '#fff',
                '--ikona-disabled-bg' => '#c5cbb0',
            ]),
            'kvadrat' => array_merge($stroke, [
                '--ikona-glyph' => '13px',
                '--ikona-radius' => '0',
                '--ikona-border' => '1px solid currentColor',
                '--ikona-linecap' => 'square',
                '--ikona-linejoin' => 'miter',
            ]),
            'dijamant' => array_merge($stroke, [
                '--ikona-size' => '22px',
                '--ikona-glyph' => '11px',
                '--ikona-radius' => '0',
                '--ikona-border' => '1px solid currentColor',
                '--ikona-rotate' => '45deg',
                '--ikona-glyph-rotate' => '-45deg',
            ]),
            'prsten' => array_merge($stroke, [
                '--ikona-glyph' => '12px',
                '--ikona-radius' => '999px',
                '--ikona-bg' => '#fff',
                '--ikona-bg-hover' => '#fff',
                '--ikona-border' => '1px solid currentColor',
                '--ikona-shadow' => 'inset 0 0 0 3px #fff, inset 0 0 0 4px currentColor',
                '--ikona-opasno-bg' => '#fff',
                '--ikona-opasno-hover-bg' => '#fff',
                '--ikona-opasno-hover-fg' => '#7a1515',
            ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function defaults(): array
    {
        return [
            '--ikona-size' => '28px',
            '--ikona-glyph' => '15px',
            '--ikona-radius' => '6px',
            '--ikona-bg' => 'transparent',
            '--ikona-bg-hover' => '#f3f6ea',
            '--ikona-fg' => '#5c6540',
            '--ikona-fg-hover' => 'var(--primarna-tamna)',
            '--ikona-fill' => 'currentColor',
            '--ikona-stroke' => 'none',
            '--ikona-stroke-width' => '0',
            '--ikona-border' => '0',
            '--ikona-shadow' => 'none',
            '--ikona-rotate' => '0deg',
            '--ikona-glyph-rotate' => '0deg',
            '--ikona-linecap' => 'round',
            '--ikona-linejoin' => 'round',
            '--ikona-opasno-fg' => '#9b1c1c',
            '--ikona-opasno-bg' => 'transparent',
            '--ikona-opasno-hover-bg' => '#fdecec',
            '--ikona-opasno-hover-fg' => '#9b1c1c',
            '--ikona-gotovo-fg' => '#2f6b3a',
            '--ikona-gotovo-bg' => 'transparent',
            '--ikona-gotovo-hover-bg' => '#f3f6ea',
            '--ikona-gotovo-hover-fg' => '#2f6b3a',
            '--ikona-disabled-fg' => '#c5cbb0',
            '--ikona-disabled-bg' => 'transparent',
        ];
    }
}
