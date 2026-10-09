<?php

namespace App\Services;

use App\Models\Statute;
use App\Models\StatuteSyncState;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class StatuteImporter
{
    private int $budget = 0;

    public function pull(int $limit): int
    {
        $this->budget = max(1, $limit);
        $state = StatuteSyncState::query()->firstOrCreate(
            ['source' => 'hr'],
            ['cursor' => $this->freshCursor()],
        );
        $cursor = $state->cursor ?: $this->freshCursor();
        if (($cursor['done'] ?? false) === true) {
            $handled = $this->fillMissingTexts();
            $state->forceFill(['cursor' => $cursor, 'finished_at' => $state->finished_at ?? now()])->save();

            return $handled;
        }

        $handled = 0;
        $handled += $this->fillMissingTexts();

        while ($this->budget > 0 && ($cursor['done'] ?? false) !== true) {
            $before = $this->budget;
            $previous = json_encode($cursor);
            $cursor = $this->step($cursor);
            if ($this->budget < $before) {
                $handled++;
            }
            if ($this->budget === $before && json_encode($cursor) === $previous) {
                break;
            }
        }

        $state->forceFill([
            'cursor' => $cursor,
            'finished_at' => ($cursor['done'] ?? false) ? now() : null,
        ])->save();

        return $handled;
    }

    /**
     * @param  array<string, mixed>  $cursor
     * @return array<string, mixed>
     */
    private function step(array $cursor): array
    {
        if ($cursor['years'] === null) {
            $years = $this->years();
            if ($years === null) {
                return $cursor;
            }
            $cursor['years'] = $years;

            return $cursor;
        }

        $years = $cursor['years'];
        if ($cursor['year_index'] >= count($years)) {
            $cursor['done'] = true;

            return $cursor;
        }

        $year = (int) $years[$cursor['year_index']];
        if ($cursor['editions'] === null) {
            $editions = $this->editions($year);
            if ($editions === null) {
                return $cursor;
            }
            $cursor['editions'] = $editions;
            $cursor['edition_index'] = 0;
            $cursor['acts'] = null;
            $cursor['act_index'] = 0;

            return $cursor;
        }

        $editions = $cursor['editions'];
        if ($cursor['edition_index'] >= count($editions)) {
            $cursor['year_index']++;
            $cursor['editions'] = null;

            return $cursor;
        }

        $edition = (int) $editions[$cursor['edition_index']];
        if ($cursor['acts'] === null) {
            $acts = $this->acts($year, $edition);
            if ($acts === null) {
                return $cursor;
            }
            $cursor['acts'] = $acts;
            $cursor['act_index'] = 0;

            return $cursor;
        }

        $acts = $cursor['acts'];
        if ($cursor['act_index'] >= count($acts)) {
            $cursor['edition_index']++;
            $cursor['acts'] = null;

            return $cursor;
        }

        $act = (string) $acts[$cursor['act_index']];
        $result = $this->act($year, $edition, $act);
        if ($result === null) {
            return $cursor;
        }
        if (! isset($result['skip'])) {
            $statute = Statute::query()->updateOrCreate(
                ['external_id' => $result['external_id']],
                [
                    'title' => $result['title'],
                    'citation' => $result['citation'],
                    'document_type' => 'ZAKON',
                    'published_on' => $result['published_on'],
                    'source_url' => $result['source_url'],
                ],
            );
            if ($statute->fetched_at === null && $this->budget > 0) {
                $this->storeText($statute);
            }
        }

        $cursor['act_index']++;

        return $cursor;
    }

    private function fillMissingTexts(): int
    {
        $filled = 0;
        $pending = Statute::query()->whereNull('fetched_at')->orderBy('id')->limit($this->budget)->get();
        foreach ($pending as $statute) {
            if ($this->budget < 1) {
                break;
            }
            $this->storeText($statute);
            $filled++;
        }

        return $filled;
    }

    private function storeText(Statute $statute): void
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get($statute->source_url));
        if ($response === null) {
            return;
        }
        $clean = $response->successful() ? $this->clean((string) $response->body()) : null;
        $statute->forceFill([
            'text_html' => $clean,
            'text_plain' => $clean === null ? null : $this->plain($clean),
            'fetched_at' => now(),
        ])->save();
    }

    /**
     * @return list<int>|null
     */
    private function years(): ?array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->acceptJson()->get($this->url('/api/index')));
        $years = $response?->json();
        if ($response === null || ! $response->successful() || ! is_array($years)) {
            return null;
        }

        return array_reverse(array_values(array_map('intval', $years)));
    }

    /**
     * @return list<int>|null
     */
    private function editions(int $year): ?array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->acceptJson()->post($this->url('/api/editions'), [
            'part' => 'SL',
            'year' => $year,
        ]));
        $editions = $response?->json();
        if ($response === null || ! $response->successful() || ! is_array($editions)) {
            return null;
        }

        return array_reverse(array_values(array_map('intval', $editions)));
    }

    /**
     * @return list<string>|null
     */
    private function acts(int $year, int $number): ?array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->acceptJson()->post($this->url('/api/acts'), [
            'part' => 'SL',
            'year' => $year,
            'number' => $number,
        ]));
        $acts = $response?->json();
        if ($response === null || ! $response->successful() || ! is_array($acts)) {
            return null;
        }

        return array_values(array_map('strval', $acts));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function act(int $year, int $number, string $act): ?array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->acceptJson()->post($this->url('/api/act'), [
            'part' => 'SL',
            'year' => $year,
            'number' => $number,
            'act_num' => $act,
            'format' => 'JSON-LD',
        ]));
        if ($response === null || $response->serverError() || $response->status() === 429) {
            return null;
        }
        if (! $response->successful()) {
            return ['skip' => true];
        }

        return $this->interpret($response, $year, $number, $act) ?? ['skip' => true];
    }

    /**
     * @return array{external_id: string, title: string, citation: string, published_on: ?string, source_url: string}|null
     */
    private function interpret(Response $response, int $year, int $number, string $act): ?array
    {
        $graph = $response->json();
        if (! is_array($graph)) {
            return null;
        }

        $type = null;
        $eli = null;
        $title = null;
        $published = null;
        foreach ($graph as $node) {
            if (! is_array($node)) {
                continue;
            }
            $typeUrl = $node['http://data.europa.eu/eli/ontology#type_document'][0]['@id'] ?? null;
            if (is_string($typeUrl) && $typeUrl !== '') {
                $type = strtoupper(basename($typeUrl));
                $eli = is_string($node['@id'] ?? null) ? $node['@id'] : $eli;
            }
            $nodeTitle = $node['http://data.europa.eu/eli/ontology#title'][0]['@value'] ?? null;
            if (is_string($nodeTitle) && $nodeTitle !== '' && $title === null) {
                $title = $nodeTitle;
            }
            $nodeDate = $node['http://data.europa.eu/eli/ontology#date_publication'][0]['@value'] ?? null;
            if (is_string($nodeDate) && $published === null) {
                $published = $nodeDate;
            }
        }

        if ($type !== 'ZAKON' || $title === null) {
            return null;
        }

        $eli = $eli ?: $this->url('/eli/sluzbeni/'.$year.'/'.$number.'/'.$act);

        return [
            'external_id' => $eli,
            'title' => $title,
            'citation' => 'NN '.$number.'/'.$year,
            'published_on' => $published,
            'source_url' => rtrim($eli, '/').'/hrv/html',
        ];
    }

    private function clean(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = strip_tags($html, '<p><br><h1><h2><h3><h4><table><thead><tbody><tr><th><td><ul><ol><li><strong><em><b><i><div><span>');
        $html = preg_replace('/\s(on\w+|style)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;

        return trim($html);
    }

    private function plain(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function send(\Closure $request): ?Response
    {
        try {
            return $request($this->call());
        } catch (ConnectionException) {
            return null;
        }
    }

    private function call(): PendingRequest
    {
        $this->budget--;
        if (! app()->runningUnitTests()) {
            usleep(350000);
        }

        $client = Http::timeout(20);
        $ca = config('services.nn.ca_bundle');
        if (! is_string($ca) || $ca === '') {
            $ca = storage_path('app/cacert.pem');
        }
        if (is_file($ca)) {
            $client = $client->withOptions(['verify' => $ca]);
        }

        return $client;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.nn.base_url'), '/').$path;
    }

    /**
     * @return array<string, mixed>
     */
    private function freshCursor(): array
    {
        return [
            'years' => null,
            'year_index' => 0,
            'editions' => null,
            'edition_index' => 0,
            'acts' => null,
            'act_index' => 0,
            'done' => false,
        ];
    }
}
