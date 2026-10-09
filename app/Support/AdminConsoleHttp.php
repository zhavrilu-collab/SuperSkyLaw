<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class AdminConsoleHttp
{
    public static function client(?string $token = null): PendingRequest
    {
        $request = Http::acceptJson()->timeout(10);

        if (is_string($token) && $token !== '') {
            $request = $request->withToken($token);
        }

        if (! config('admin_console.http_verify', true)) {
            $request = $request->withoutVerifying();
        }

        if (config('admin_console.http_resolve_loopback', false)) {
            $request = $request->withOptions([
                'curl' => self::resolveLoopbackOptions([
                    (string) config('admin_console.webhook_url'),
                    (string) config('admin_console.api_url'),
                    (string) config('identity.core_api_url'),
                    CoreApiUrl::endpoint('/'),
                ]),
            ]);
        }

        return $request;
    }

    /**
     * @param  list<string>  $urls
     * @return array<int, mixed>
     */
    public static function resolveLoopbackOptions(array $urls): array
    {
        $hosts = [];

        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            $port = parse_url($url, PHP_URL_PORT) ?: (str_starts_with($url, 'http://') ? 80 : 443);

            if (! is_string($host) || $host === '' || $host === '127.0.0.1' || $host === 'localhost') {
                continue;
            }

            $hosts[sprintf('%s:%d:127.0.0.1', $host, (int) $port)] = true;
        }

        if ($hosts === []) {
            return [];
        }

        return [
            CURLOPT_RESOLVE => array_keys($hosts),
        ];
    }
}
