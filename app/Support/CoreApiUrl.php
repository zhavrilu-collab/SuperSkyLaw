<?php

namespace App\Support;

class CoreApiUrl
{
    public static function basePath(): string
    {
        $version = trim((string) config('identity.core_api_version', 'v1'));

        if ($version === '' || $version === 'legacy') {
            return '/api';
        }

        return '/api/'.$version;
    }

    public static function endpoint(string $path): string
    {
        $normalized = '/'.ltrim($path, '/');

        return rtrim((string) config('identity.core_api_url'), '/').self::basePath().$normalized;
    }
}
