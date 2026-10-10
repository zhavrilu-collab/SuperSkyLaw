<?php

namespace App\Console\Commands;

use App\Models\Statute;
use App\Models\StatuteWork;
use App\Services\StatuteImporter;
use Illuminate\Console\Command;

class ImportBaseTexts extends Command
{
    protected $signature = 'legal:import-base-texts {--limit=20 : Broj zahtjeva prema Narodnim novinama}';

    protected $description = 'Uvozi osnovne tekstove zakona objavljenih prije 2015. i službene pročišćene tekstove';

    public function handle(StatuteImporter $importer): int
    {
        $fetched = $importer->importPublications($this->publications(), max(1, (int) $this->option('limit')));
        $this->attachTakeover();
        $this->info('Preuzeto tekstova: '.$fetched);

        return self::SUCCESS;
    }

    private function attachTakeover(): void
    {
        $takeover = Statute::query()->where('external_id', 'https://narodne-novine.nn.hr/eli/sluzbeni/1991/53/1297')->first();
        $work = StatuteWork::query()->where('title', 'Zakon o parničnom postupku')->first();
        if ($takeover === null || $work === null || (int) $takeover->work_id === (int) $work->id) {
            return;
        }

        $previous = $takeover->work_id;
        $takeover->work_id = $work->id;
        $takeover->save();
        if ($previous !== null) {
            StatuteWork::query()->whereKey($previous)->whereDoesntHave('statutes')->delete();
        }
    }

    /**
     * @return list<array{eli: string, title: string, citation: string, base: bool}>
     */
    private function publications(): array
    {
        return [
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2005/35/707', 'title' => 'Zakon o obveznim odnosima', 'citation' => 'NN 35/2005', 'base' => true],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2011/125/2498', 'title' => 'Kazneni zakon', 'citation' => 'NN 125/2011', 'base' => true],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2008/152/4149', 'title' => 'Zakon o kaznenom postupku', 'citation' => 'NN 152/2008', 'base' => true],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2011/121/2386', 'title' => 'Zakon o kaznenom postupku (pročišćeni tekst)', 'citation' => 'NN 121/2011', 'base' => true],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2007/107/3125', 'title' => 'Prekršajni zakon', 'citation' => 'NN 107/2007', 'base' => true],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2014/93/1872', 'title' => 'Zakon o radu', 'citation' => 'NN 93/2014', 'base' => true],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2012/112/2421', 'title' => 'Ovršni zakon', 'citation' => 'NN 112/2012', 'base' => true],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2011/148/2993', 'title' => 'Zakon o parničnom postupku (pročišćeni tekst)', 'citation' => 'NN 148/2011', 'base' => true],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/1991/53/1297', 'title' => 'Zakon o preuzimanju Zakona o parničnom postupku', 'citation' => 'NN 53/1991', 'base' => false],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/1993/111/2133', 'title' => 'Odluka o proglašenju Zakona o trgovačkim društvima', 'citation' => 'NN 111/1993', 'base' => false],
            ['eli' => 'https://narodne-novine.nn.hr/eli/sluzbeni/2011/152/3144', 'title' => 'Zakon o trgovačkim društvima (pročišćeni tekst)', 'citation' => 'NN 152/2011', 'base' => true],
        ];
    }
}
