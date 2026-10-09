<?php

namespace Database\Seeders;

use App\Models\DisputeCategory;
use Illuminate\Database\Seeder;

class DisputeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'civil' => [
                ['Naknada štete (materijalna / nematerijalna)', 'Prometne nezgode, ozljede na javnim površinama, osiguranja.'],
                ['Smetanje posjeda', 'Brzi postupci zaštite posjeda.'],
                ['Utvrđenje prava vlasništva i brisovna tužba', 'Sporovi oko nekretnina i zemljišnoknjižnih upisa.'],
                ['Razvrgnuće suvlasničke zajednice', 'Izvanparnični postupci podjele imovine.'],
                ['Ospravljanje / utvrđenje očinstva i majčinstva', 'Obiteljskopravni osjetljivi predmeti.'],
                ['Razvod braka i povjeravanje djece', 'S pripadajućim mjerama uzdržavanja.'],
                ['Ostavinski postupci i nasljednički sporovi', 'Pred javnim bilježnicima ili sudom.'],
            ],
            'criminal' => [
                ['Gospodarski kriminal i koruptivna djela', 'Zlouporaba povjerenja u gospodarskom poslovanju, utaja poreza, subvencijske prijevare.'],
                ['Kaznena djela protiv imovine', 'Teške krađe, prijevare, iznude.'],
                ['Kaznena djela protiv opće sigurnosti i prometa', 'Izazivanje prometnih nesreća (čl. 227. KZ).'],
                ['Kaznena djela protiv života i tijela / privatnosti', 'Teške tjelesne ozljede, prijetnje, klevete (privatne tužbe).'],
                ['Zastupanje oštećenika (imovinskopravni zahtjev)', 'Kada ured ne brani okrivljenika, već zastupa žrtvu.'],
            ],
            'commercial' => [
                ['Isplata iz trgovačkih ugovora', 'Tužbe radi naplate potraživanja među tvrtkama.'],
                ['Pobijanje odluka skupštine / Nadzornog odbora', 'Statusni i korporativni sporovi.'],
                ['Isključenje / istupanje člana društva', 'Sukobi među suosnivačima d.o.o.'],
                ['Prijava tražbine u stečajnom postupku / predstečaju', 'Zastupanje vjerovnika u stečajevima.'],
                ['Povreda prava intelektualnog vlasništva', 'Žigovi, patenti, autorska prava, nelojalna utakmica.'],
            ],
            'labor' => [
                ['Nedopuštenost otkaza ugovora o radu', 'Sporovi s kratkim prekluzivnim rokovima (zahtjev za zaštitu prava).'],
                ['Isplata materijalnih prava', 'Neisplaćene plaće, prekovremeni rad, otpremnine.'],
                ['Naknada štete zbog ozljede na radu', 'Profesionalne bolesti i nezgode na radu.'],
                ['Uznemiravanje i diskriminacija (Mobing)', 'Postupci zaštite dostojanstva radnika.'],
            ],
            'enforcement' => [
                ['Ovrha na temelju vjerodostojne isprave', 'Prigovori na rješenja o ovrsi (fakture, izvodi iz poslovnih knjiga).'],
                ['Ovrha na temelju ovršne isprave', 'Pokretanje postupka na temelju pravomoćne presude ili zadužnice.'],
                ['Protuovrha i odgoda ovrhe', 'Pravni lijekovi ovršenika.'],
                ['Privremene i prethodne mjere', 'Hitni postupci osiguranja potraživanja prije ili tijekom spora.'],
            ],
            'administrative' => [
                ['Upravni spor protiv rješenja Porezne uprave', 'Porezni nadzori, obračuni PDV-a i dobiti.'],
                ['Sporovi u vezi s gradnjom i prostornim uređenjem', 'Lokacijske i građevinske dozvole, rješenja o izvedenom stanju.'],
                ['Javna nabava (postupci pred DKOM-om)', 'Žalbe na natječajnu dokumentaciju i odabire.'],
                ['Eksproprijacija / Izvlaštenje', 'Sporovi oko naknade za oduzete nekretnine.'],
            ],
        ];

        $sort = 1;
        foreach ($groups as $kind => $items) {
            foreach ($items as [$name, $hint]) {
                DisputeCategory::query()->updateOrCreate(
                    ['organization_id' => null, 'kind' => $kind, 'name' => $name],
                    ['hint' => $hint, 'sort' => $sort, 'active' => true],
                );
                $sort++;
            }
        }
    }
}
