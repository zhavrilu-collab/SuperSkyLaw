<?php

namespace App\Support;

use App\Models\Organization;
use Throwable;

class ErrorPageTheme
{
    /** @return array<string, mixed> */
    public static function palette(): array
    {
        try {
            $organization = self::organization();
        } catch (Throwable) {
            $organization = null;
        }

        try {
            if ($organization instanceof Organization) {
                return OfficeThemes::palette(
                    $organization->theme_color,
                    ThemeRecipes::effectiveStyle($organization->theme_style),
                );
            }

            return OfficeThemes::palette(OfficeThemes::DEFAULT, ThemeRecipes::DEFAULT_STYLE);
        } catch (Throwable) {
            return self::safeFallback();
        }
    }

    private static function organization(): ?Organization
    {
        if (app()->bound('currentOrganization') && app('currentOrganization') instanceof Organization) {
            return app('currentOrganization');
        }

        $slug = request()->route('slug') ?: request()->segment(1);
        if (! is_string($slug) || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,80}$/', $slug)) {
            return null;
        }

        return Organization::query()->where('slug', $slug)->first();
    }

    /** @return array<string, string> */
    private static function safeFallback(): array
    {
        return [
            'primary' => '#2a2a28',
            'dark' => '#1c1c1a',
            'light' => '#f7f7f5',
            'text' => '#1c1c1a',
            'logoMark' => '#b0cb1f',
            'btnBg' => '#2a2a28',
            'btnFg' => '#ffffff',
            'btnBorder' => '#2a2a28',
            'btnHoverBg' => '#1a1a18',
            'btnHoverFg' => '#ffffff',
        ];
    }
}
