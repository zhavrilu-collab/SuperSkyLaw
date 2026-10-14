<?php

namespace App\Console\Commands;

use App\Services\LawyerDirectorySyncService;
use Illuminate\Console\Command;

class SyncLawyerDirectoryCommand extends Command
{
    protected $signature = 'legal:sync-lawyer-directory {--path= : Lokalna CSV ili Excel datoteka umjesto preuzimanja}';

    protected $description = 'Preuzima javni imenik Hrvatske odvjetničke komore za prijavu ureda';

    public function handle(LawyerDirectorySyncService $sync): int
    {
        $path = $this->option('path');

        try {
            $count = $sync->sync(is_string($path) && $path !== '' ? $path : null);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Učitano ureda: '.$count);

        return self::SUCCESS;
    }
}
