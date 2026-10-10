<?php

namespace App\Console\Commands;

use App\Services\StatuteImporter;
use Illuminate\Console\Command;

class SyncStatutes extends Command
{
    protected $signature = 'legal:sync-statutes {--limit=60 : Broj zahtjeva prema Narodnim novinama}';

    protected $description = 'Uvozi paket hrvatskih zakona iz Narodnih novina';

    public function handle(StatuteImporter $importer): int
    {
        $handled = $importer->pull(max(1, (int) $this->option('limit')));
        $this->info('Obrađeno zahtjeva: '.$handled);

        return self::SUCCESS;
    }
}
