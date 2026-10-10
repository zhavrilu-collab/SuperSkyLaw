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

    public function __construct(private StatuteWorkGrouper $grouper) {}

    public function pull(int $limit): int
    {
        $this->budget = max(1, $limit);
        $this->grouper->attachMissing();
        $state = StatuteSyncState::query()->firstOrCreate(
            ['source' => 'hr'],
            ['cursor' => $this->freshCursor()],
        );
        $cursor = $state->cursor ?: $this->freshCursor();
        if (($cursor['v'] ?? 0) !== 2) {
            $cursor = $this->freshCursor();
        }

        if (($cursor['done'] ?? false) === true) {
            $handled = $this->fillMissingTexts();
            $state->forceFill(['cursor' => $cursor, 'finished_at' => $state->finished_at ?? now()])->save();

            return $handled;
        }

        $handled = 0;
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
        $loaded = $cursor['loaded_years'] ?? [];
        if (! in_array($year, $loaded, true)) {
            $rows = $this->indexRows($year);
            if ($rows === null) {
                return $cursor;
            }
            $this->storeRows($rows);
            $cursor['loaded_years'][] = $year;

            return $cursor;
        }

        $pending = Statute::query()->whereNull('fetched_at')->orderByDesc('published_on')->orderByDesc('id')->first();
        if ($pending !== null) {
            $this->storeText($pending);

            return $cursor;
        }

        $cursor['year_index']++;

        return $cursor;
    }

    private function fillMissingTexts(): int
    {
        $filled = 0;
        while ($this->budget > 0) {
            $pending = Statute::query()->whereNull('fetched_at')->orderByDesc('published_on')->orderByDesc('id')->first();
            if ($pending === null) {
                break;
            }
            $this->storeText($pending);
            $filled++;
        }

        return $filled;
    }

    /**
     * @param  list<array{eli: string, title: string, citation: string, base: bool}>  $rows
     */
    public function importPublications(array $rows, int $limit): int
    {
        $this->budget = max(1, $limit);
        $this->storeRows($rows);

        return $this->fillMissingTexts();
    }

    /**
     * @param  list<array{eli: string, title: string, citation: string, base: bool}>  $rows
     */
    private function storeRows(array $rows): void
    {
        foreach ($rows as $row) {
            $work = $this->grouper->workFor($row['title'], $row['eli'], $row['base']);
            $statute = Statute::query()->firstOrNew(['external_id' => $row['eli']]);
            $statute->fill([
                'work_id' => $work->id,
                'title' => $row['title'],
                'citation' => $row['citation'],
                'document_type' => 'ZAKON',
                'source_url' => rtrim($row['eli'], '/').'/hrv/html',
            ]);
            $statute->save();
        }
    }

    /**
     * @return list<array{eli: string, title: string, citation: string, base: bool}>|null
     */
    private function indexRows(int $year): ?array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get($this->url('/get_index_file.aspx?year='.$year.'&type=csv')));
        if ($response === null || ! $response->successful()) {
            return null;
        }

        $body = (string) $response->body();
        if (! str_contains(ltrim($body, "\xEF\xBB\xBF"), 'Izdanje')) {
            return null;
        }

        $lines = preg_split('/\r\n|\n|\r/', $body) ?: [];
        $header = null;
        $delimiter = "\t";
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            if ($header === null) {
                $line = ltrim($line, "\xEF\xBB\xBF");
                $delimiter = str_contains($line, "\t") ? "\t" : (str_contains($line, ';') ? ';' : ',');
                $header = array_map(fn ($column) => mb_strtolower(trim($column)), str_getcsv($line, $delimiter));

                continue;
            }
            $cells = str_getcsv($line, $delimiter);
            $record = [];
            foreach ($header as $index => $name) {
                $record[$name] = trim((string) ($cells[$index] ?? ''));
            }
            $kind = mb_strtolower($record['vrsta dokumenta'] ?? '');
            $eli = $this->normalizeEli($record['poveznica'] ?? '');
            $title = trim(preg_replace('/\s+/u', ' ', $record['naziv dokumenta'] ?? '') ?? '');
            if ($kind !== 'zakon' || $title === '' || ! str_contains($eli, '/eli/sluzbeni/')) {
                continue;
            }
            $citation = trim($record['izdanje'] ?? '');
            if ($citation === '') {
                $citation = 'NN /'.$year;
            }
            $role = mb_strtolower($record['cjeloviti dokument/izmjene/dopune/ukinut'] ?? '');
            $rows[] = [
                'eli' => $eli,
                'title' => $title,
                'citation' => $citation,
                'base' => str_contains($role, 'cjeloviti'),
            ];
        }

        return $rows;
    }

    private function normalizeEli(string $url): string
    {
        $url = trim($url);
        $url = preg_replace('#^https:/(?!/)#', 'https://', $url) ?? $url;

        return rtrim($url, '/');
    }

    private function storeText(Statute $statute): void
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get($statute->source_url));
        if ($response === null) {
            return;
        }
        $body = (string) $response->body();
        $clean = $response->successful() ? $this->clean($body) : null;
        $published = $statute->published_on;
        if ($published === null && $response->successful()) {
            $published = $this->publicationDate($body);
        }
        $statute->forceFill([
            'text_html' => $clean,
            'text_plain' => $clean === null ? null : $this->plain($clean),
            'published_on' => $published,
            'fetched_at' => now(),
        ])->save();
    }

    public function fillPublicationDates(): int
    {
        $filled = 0;
        Statute::query()
            ->whereNull('published_on')
            ->whereNotNull('text_html')
            ->select('id', 'text_html')
            ->orderBy('id')
            ->chunkById(40, function ($statutes) use (&$filled): void {
                foreach ($statutes as $statute) {
                    $date = $this->publicationDate((string) $statute->text_html);
                    if ($date === null) {
                        continue;
                    }
                    $statute->forceFill(['published_on' => $date])->save();
                    $filled++;
                }
            });

        return $filled;
    }

    public function publicationDate(string $html): ?string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (! preg_match('/Datum tiskanog izdanja:\s*(\d{1,2})\.(\d{1,2})\.(\d{4})\./u', $text, $match)) {
            return null;
        }
        $day = (int) $match[1];
        $month = (int) $match[2];
        $year = (int) $match[3];
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
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

        $years = array_values(array_filter(array_map('intval', $years), fn (int $year) => $year >= 2015));
        rsort($years);

        return $years;
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

        $client = Http::timeout(90);
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
            'v' => 2,
            'years' => null,
            'year_index' => 0,
            'loaded_years' => [],
            'done' => false,
        ];
    }
}
