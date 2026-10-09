<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\Matter;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->courts() as $court) {
            Court::query()->updateOrCreate(['name' => $court['name']], $court);
        }

        Matter::query()
            ->withoutGlobalScope('organization')
            ->whereNull('court_id')
            ->whereNotNull('court_name')
            ->each(function (Matter $matter): void {
                $court = Court::query()->where('name', $matter->court_name)->first();
                if ($court !== null) {
                    $matter->forceFill(['court_id' => $court->id])->save();
                }
            });
    }

    /**
     * @return list<array{name: string, type: string, city: string, active: bool, sort: int}>
     */
    private function courts(): array
    {
        $rows = [];
        $add = function (string $type, string $city, string $name) use (&$rows): void {
            $rows[] = [
                'type' => $type,
                'city' => $city,
                'name' => $name,
                'active' => true,
                'sort' => count($rows) + 1,
            ];
        };

        $add('supreme', 'Zagreb', 'Vrhovni sud Republike Hrvatske');
        $add('high_criminal', 'Zagreb', 'Visoki kazneni sud Republike Hrvatske');
        $add('high_misdemeanor', 'Zagreb', 'Visoki prekršajni sud Republike Hrvatske');
        $add('high_commercial', 'Zagreb', 'Visoki trgovački sud Republike Hrvatske');
        $add('high_administrative', 'Zagreb', 'Visoki upravni sud Republike Hrvatske');

        foreach ([
            'Bjelovaru' => 'Bjelovar',
            'Dubrovniku' => 'Dubrovnik',
            'Karlovcu' => 'Karlovac',
            'Osijeku' => 'Osijek',
            'Puli' => 'Pula',
            'Rijeci' => 'Rijeka',
            'Sisku' => 'Sisak',
            'Slavonskom Brodu' => 'Slavonski Brod',
            'Splitu' => 'Split',
            'Šibeniku' => 'Šibenik',
            'Varaždinu' => 'Varaždin',
            'Velikoj Gorici' => 'Velika Gorica',
            'Vukovaru' => 'Vukovar',
            'Zadru' => 'Zadar',
            'Zagrebu' => 'Zagreb',
        ] as $locative => $city) {
            $add('county', $city, 'Županijski sud u '.$locative);
        }

        foreach ([
            'Bjelovaru' => 'Bjelovar',
            'Čakovcu' => 'Čakovec',
            'Đakovu' => 'Đakovo',
            'Dubrovniku' => 'Dubrovnik',
            'Gospiću' => 'Gospić',
            'Imotskom' => 'Imotski',
            'Karlovcu' => 'Karlovac',
            'Kninu' => 'Knin',
            'Koprivnici' => 'Koprivnica',
            'Krapini' => 'Krapina',
            'Kutini' => 'Kutina',
            'Makarskoj' => 'Makarska',
            'Metkoviću' => 'Metković',
            'Našicama' => 'Našice',
            'Novoj Gradiški' => 'Nova Gradiška',
            'Ogulinu' => 'Ogulin',
            'Osijeku' => 'Osijek',
            'Pazinu' => 'Pazin',
            'Petrinji' => 'Petrinja',
            'Poreču' => 'Poreč',
            'Požegi' => 'Požega',
            'Puli' => 'Pula',
            'Rijeci' => 'Rijeka',
            'Sinju' => 'Sinj',
            'Sisku' => 'Sisak',
            'Slavonskom Brodu' => 'Slavonski Brod',
            'Splitu' => 'Split',
            'Šibeniku' => 'Šibenik',
            'Varaždinu' => 'Varaždin',
            'Velikoj Gorici' => 'Velika Gorica',
            'Vinkovcima' => 'Vinkovci',
            'Virovitici' => 'Virovitica',
            'Vrbovcu' => 'Vrbovec',
            'Vukovaru' => 'Vukovar',
            'Zaboku' => 'Zabok',
            'Zadru' => 'Zadar',
            'Zlataru' => 'Zlatar',
            'Županji' => 'Županja',
        ] as $locative => $city) {
            $add('municipal', $city, 'Općinski sud u '.$locative);
        }

        $add('municipal_civil', 'Zagreb', 'Općinski građanski sud u Zagrebu');
        $add('municipal_criminal', 'Zagreb', 'Općinski kazneni sud u Zagrebu');
        $add('municipal_misdemeanor', 'Zagreb', 'Općinski prekršajni sud u Zagrebu');
        $add('municipal_labor', 'Zagreb', 'Općinski radni sud u Zagrebu');

        foreach ([
            'Bjelovaru' => 'Bjelovar',
            'Dubrovniku' => 'Dubrovnik',
            'Osijeku' => 'Osijek',
            'Pazinu' => 'Pazin',
            'Rijeci' => 'Rijeka',
            'Splitu' => 'Split',
            'Varaždinu' => 'Varaždin',
            'Zadru' => 'Zadar',
            'Zagrebu' => 'Zagreb',
        ] as $locative => $city) {
            $add('commercial', $city, 'Trgovački sud u '.$locative);
        }

        foreach ([
            'Osijeku' => 'Osijek',
            'Rijeci' => 'Rijeka',
            'Splitu' => 'Split',
            'Zagrebu' => 'Zagreb',
        ] as $locative => $city) {
            $add('administrative', $city, 'Upravni sud u '.$locative);
        }

        return $rows;
    }
}
