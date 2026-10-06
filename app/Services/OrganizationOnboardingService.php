<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Str;

class OrganizationOnboardingService
{
    public static function makeUniqueSlug(string $name): string
    {
        $base = \App\Support\CroatianOib::slugFromName($name);
        $slug = $base;
        $suffix = 1;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
