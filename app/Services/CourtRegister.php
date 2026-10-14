<?php

namespace App\Services;

use App\Enums\PartyKind;
use App\Models\Party;
use App\Support\OfficeName;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CourtRegister
{
    /**
     * @return array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string, postal_code: ?string}
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

        return $this->fetchDetails($this->token(), $type, $identifier);
    }

    /**
     * @return list<array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string}>
     */
    public function findByCompanyName(string $name): array
    {
        $token = $this->token();

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->base().'javni/subjekti', [
                    'tvrtka_naziv' => $name,
                    'only_active' => 'true',
                    'limit' => 10,
                    'expand_relations' => 'true',
                    'no_data_error' => '0',
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar trenutno nije dostupan.',
            ]);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar nije vratio subjekte za taj naziv.',
            ]);
        }

        $matches = [];
        $detailCalls = 0;
        foreach ($this->rows($response->json()) as $row) {
            if ($this->isInactive($row)) {
                continue;
            }

            $mapped = $this->named($row);
            if (($mapped === null || $mapped['oib'] === null || $mapped['address'] === null) && $detailCalls < 5) {
                $detailCalls++;
                $detailed = $this->detailsForRow($token, $row);
                if ($detailed !== null) {
                    $mapped = $detailed;
                }
            }

            if ($mapped === null || ! OfficeName::matches($mapped['name'], $name)) {
                continue;
            }

            $matches[] = $this->forRegistration($mapped);
            if (count($matches) >= 8) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @return array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string}
     */
    public function officialRecord(string $oib): array
    {
        $digits = preg_replace('/\D+/', '', $oib) ?? '';

        return $this->forRegistration($this->fetchDetails($this->token(), 'oib', $digits));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string, postal_code: ?string}
     */
    public function map(array $payload): array
    {
        $subject = isset($payload['subjekt']) && is_array($payload['subjekt']) ? $payload['subjekt'] : $payload;
        $name = $this->subjectName($subject);
        $seat = $subject['sjedista'][0] ?? $subject['sjediste'][0] ?? [];
        $seat = is_array($seat) ? $seat : [];
        $address = trim(implode(' ', array_filter([
            $seat['ulica'] ?? null,
            $seat['kucni_broj'] ?? null,
        ])));
        $postal = $seat['postanski_broj'] ?? $seat['broj_poste'] ?? null;

        if ($name === '') {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar nije vratio naziv subjekta.',
            ]);
        }

        $mbs = $subject['mbs'] ?? $payload['mbs'] ?? null;
        $oib = $subject['potpuni_oib'] ?? $payload['potpuni_oib'] ?? $subject['oib'] ?? $payload['oib'] ?? null;

        return [
            'name' => $name,
            'oib' => $this->digits($oib, 11),
            'mbs' => $mbs !== null && $mbs !== '' ? (string) $mbs : null,
            'address' => $address !== '' ? $address : null,
            'city' => isset($seat['naziv_naselja']) ? (string) $seat['naziv_naselja'] : null,
            'postal_code' => $postal !== null && $postal !== '' ? (string) $postal : null,
        ];
    }

    /**
     * @param  array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string, postal_code: ?string}  $mapped
     * @return array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string}
     */
    public function forRegistration(array $mapped): array
    {
        $city = $mapped['city'];
        $postal = $mapped['postal_code'];
        if ($postal !== null && $city !== null && preg_match('/^\d{5}\b/u', $city) !== 1) {
            $city = $postal.' '.$city;
        } elseif ($postal !== null && $city === null) {
            $city = $postal;
        }

        return [
            'name' => $mapped['name'],
            'oib' => $mapped['oib'],
            'mbs' => $mapped['mbs'],
            'address' => $mapped['address'],
            'city' => $city,
        ];
    }

    /**
     * @param  array<string, mixed>  $subject
     */
    private function subjectName(array $subject): string
    {
        $names = $subject['tvrtke'] ?? $subject['nazivi'] ?? [];
        $first = is_array($names) && isset($names[0]) && is_array($names[0]) ? $names[0] : [];
        $name = (string) ($first['naziv'] ?? $first['ime'] ?? '');

        if ($name === '' && isset($subject['tvrtka']) && is_array($subject['tvrtka'])) {
            $name = (string) ($subject['tvrtka']['ime'] ?? $subject['tvrtka']['naziv'] ?? '');
        }

        if ($name === '' && isset($subject['tvrtka']) && is_string($subject['tvrtka'])) {
            $name = $subject['tvrtka'];
        }

        if ($name === '' && isset($subject['naziv']) && is_string($subject['naziv'])) {
            $name = $subject['naziv'];
        }

        return $name;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string, postal_code: ?string}|null
     */
    private function named(array $row): ?array
    {
        try {
            $mapped = $this->map($row);
        } catch (ValidationException) {
            return null;
        }

        return $mapped['name'] !== '' ? $mapped : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string, postal_code: ?string}|null
     */
    private function detailsForRow(string $token, array $row): ?array
    {
        $oib = $row['potpuni_oib'] ?? $row['oib'] ?? null;
        if ($oib !== null && $oib !== '') {
            return $this->fetchDetails($token, 'oib', (string) $oib);
        }

        if (! isset($row['mbs']) || $row['mbs'] === '') {
            return null;
        }

        return $this->fetchDetails($token, 'mbs', (string) $row['mbs']);
    }

    /**
     * @return array{name: string, oib: ?string, mbs: ?string, address: ?string, city: ?string, postal_code: ?string}
     */
    private function fetchDetails(string $token, string $type, string $identifier): array
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->base().'javni/detalji_subjekta', [
                    'tip_identifikatora' => $type,
                    'identifikator' => $identifier,
                    'expand_relations' => 'true',
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar trenutno nije dostupan.',
            ]);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar nije vratio subjekt za taj OIB.',
            ]);
        }

        $payload = $response->json();

        return $this->map(is_array($payload) ? $payload : []);
    }

    private function token(): string
    {
        $clientId = (string) config('services.sudreg.client_id');
        $secret = (string) config('services.sudreg.client_secret');
        if ($clientId === '' || $secret === '') {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar nije spojen. U postavkama nedostaju pristupni podaci.',
            ]);
        }

        try {
            $token = Http::withBasicAuth($clientId, $secret)
                ->asForm()
                ->post($this->base().'oauth/token', ['grant_type' => 'client_credentials'])
                ->throw()
                ->json('access_token');
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar trenutno nije dostupan.',
            ]);
        }

        if (! is_string($token) || $token === '') {
            throw ValidationException::withMessages([
                'oib' => 'Sudski registar nije vratio pristupni token.',
            ]);
        }

        return $token;
    }

    private function base(): string
    {
        return rtrim((string) config('services.sudreg.base_url'), '/').'/';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return array_values(array_filter($payload, is_array(...)));
        }

        foreach (['subjekti', 'data', 'items'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $rows = array_is_list($payload[$key]) ? $payload[$key] : [$payload[$key]];

                return array_values(array_filter($rows, is_array(...)));
            }
        }

        if (isset($payload['mbs']) || isset($payload['oib']) || isset($payload['subjekt'])) {
            return [$payload];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isInactive(array $row): bool
    {
        $status = $row['status'] ?? null;
        if ($status === 0 || $status === '0') {
            return true;
        }

        return is_string($status) && str_contains(mb_strtolower($status), 'brisan');
    }

    private function digits(mixed $value, int $length): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_float($value) && floor($value) == $value)) {
            $value = sprintf('%.0f', $value);
        }

        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($digits === '') {
            return null;
        }

        return str_pad($digits, $length, '0', STR_PAD_LEFT);
    }
}
