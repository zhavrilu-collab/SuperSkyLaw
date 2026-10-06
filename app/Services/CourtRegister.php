<?php

namespace App\Services;

use App\Enums\PartyKind;
use App\Models\Party;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CourtRegister
{
    /**
     * @return array{name: string, mbs: ?string, address: ?string, city: ?string}
     */
    public function lookup(Party $party): array
    {
        if ($party->kind !== PartyKind::Company) {
            throw ValidationException::withMessages([
                'oib' => 'Osobni OIB se ne dohvaća iz sudskog registra.',
            ]);
        }

        $identifier = $party->oib ?: $party->mbs;
        $type = $party->oib ? 'oib' : 'mbs';
        if ($identifier === null || $identifier === '') {
            throw ValidationException::withMessages([
                'oib' => 'Upišite OIB ili MBS pravne osobe.',
            ]);
        }

        $clientId = (string) config('services.sudreg.client_id');
        $secret = (string) config('services.sudreg.client_secret');
        if ($clientId === '' || $secret === '') {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar nije spojen. U postavkama nedostaju pristupni podaci.',
            ]);
        }

        $base = rtrim((string) config('services.sudreg.base_url'), '/').'/';
        $token = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($base.'oauth/token', ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $payload = Http::withToken($token)
            ->get($base.'javni/detalji_subjekta', [
                'tip_identifikatora' => $type,
                'identifikator' => $identifier,
                'expand_relations' => 'true',
            ])
            ->throw()
            ->json();

        return $this->map(is_array($payload) ? $payload : []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{name: string, mbs: ?string, address: ?string, city: ?string}
     */
    public function map(array $payload): array
    {
        $subject = isset($payload['subjekt']) && is_array($payload['subjekt']) ? $payload['subjekt'] : $payload;
        $names = $subject['tvrtke'] ?? $subject['nazivi'] ?? [];
        $first = is_array($names) && isset($names[0]) && is_array($names[0]) ? $names[0] : [];
        $name = (string) ($first['naziv'] ?? $first['ime'] ?? $subject['tvrtka'] ?? '');
        $seat = $subject['sjedista'][0] ?? $subject['sjediste'][0] ?? [];
        $seat = is_array($seat) ? $seat : [];
        $address = trim(implode(' ', array_filter([
            $seat['ulica'] ?? null,
            $seat['kucni_broj'] ?? null,
        ])));

        if ($name === '') {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar nije vratio naziv subjekta.',
            ]);
        }

        return [
            'name' => $name,
            'mbs' => isset($subject['mbs']) ? (string) $subject['mbs'] : null,
            'address' => $address !== '' ? $address : null,
            'city' => isset($seat['naziv_naselja']) ? (string) $seat['naziv_naselja'] : null,
        ];
    }
}
