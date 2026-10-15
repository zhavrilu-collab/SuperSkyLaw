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
            'krug' => ['label' => 'U krugu'],
            'kvadrat' => ['label' => 'U kvadratu'],
            'zaobljen' => ['label' => 'Zaobljeni kvadrat'],
            'prsten' => ['label' => 'Dvostruki prsten'],
            'negativ' => ['label' => 'Negativ'],
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
        $frame = array_merge($fill, [
            '--ikona-glyph' => '13px',
            '--ikona-border' => '1px solid currentColor',
        ]);

        return [
            'ispuna' => $fill,
            'krug' => array_merge($frame, [
                '--ikona-radius' => '999px',
            ]),
            'kvadrat' => array_merge($frame, [
                '--ikona-radius' => '0',
            ]),
            'zaobljen' => array_merge($frame, [
                '--ikona-radius' => '6px',
            ]),
            'prsten' => array_merge($frame, [
                '--ikona-glyph' => '12px',
                '--ikona-radius' => '999px',
                '--ikona-bg' => '#fff',
                '--ikona-bg-hover' => '#fff',
                '--ikona-shadow' => 'inset 0 0 0 3px #fff, inset 0 0 0 4px currentColor',
                '--ikona-opasno-bg' => '#fff',
                '--ikona-opasno-hover-bg' => '#fff',
                '--ikona-opasno-hover-fg' => 'var(--sustav-greska-tinta)',
            ]),
            'negativ' => array_merge($fill, [
                '--ikona-glyph' => '13px',
                '--ikona-radius' => '999px',
                '--ikona-bg' => 'var(--primarna-zelena)',
                '--ikona-bg-hover' => 'var(--primarna-tamna)',
                '--ikona-fg' => 'var(--tekst-na-primarnoj)',
                '--ikona-fg-hover' => '#fff',
                '--ikona-opasno-fg' => '#fff',
                '--ikona-opasno-bg' => 'var(--sustav-greska)',
                '--ikona-opasno-hover-bg' => 'var(--sustav-greska-tinta)',
                '--ikona-opasno-hover-fg' => '#fff',
                '--ikona-gotovo-fg' => '#fff',
                '--ikona-gotovo-bg' => 'var(--primarna-tamna)',
                '--ikona-gotovo-hover-bg' => 'var(--primarna-tamna)',
                '--ikona-gotovo-hover-fg' => '#fff',
                '--ikona-disabled-fg' => '#fff',
                '--ikona-disabled-bg' => 'color-mix(in srgb, var(--crta-zaglavlja) 45%, #fff)',
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
            '--ikona-bg-hover' => 'var(--svijetlo-zelena)',
            '--ikona-fg' => 'var(--crta-zaglavlja)',
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
            '--ikona-opasno-fg' => 'var(--sustav-greska)',
            '--ikona-opasno-bg' => 'transparent',
            '--ikona-opasno-hover-bg' => 'var(--tema-greska-svijetla)',
            '--ikona-opasno-hover-fg' => 'var(--sustav-greska-tinta)',
            '--ikona-gotovo-fg' => 'var(--primarna-tamna)',
            '--ikona-gotovo-bg' => 'transparent',
            '--ikona-gotovo-hover-bg' => 'var(--svijetlo-zelena)',
            '--ikona-gotovo-hover-fg' => 'var(--primarna-tamna)',
            '--ikona-disabled-fg' => 'color-mix(in srgb, var(--crta-zaglavlja) 40%, #b9b9b4)',
            '--ikona-disabled-bg' => 'transparent',
        ];
    }
}
