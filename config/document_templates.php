<?php

return [
    [
        'code' => 'punomoc',
        'name' => 'Punomoć',
        'body' => <<<'TXT'
PUNOMOĆ

{{stranka.naziv}}, OIB {{stranka.oib}}, {{stranka.adresa}}, ovlašćuje

{{ured.naziv}}, OIB {{ured.oib}}, {{ured.adresa}}, {{ured.grad}}

da ga/je zastupa u pravnoj stvari {{predmet.naziv}}, spis {{predmet.broj}}, pred {{predmet.sud}} ({{predmet.oznaka}}), protiv {{protustranka.naziv}}, sa svim ovlastima redovnog punomoćnika, uključujući primanje pismena i podnošenje pravnih lijekova.

{{ured.grad}}, {{datum}}.

________________________
{{stranka.naziv}}
TXT,
    ],
    [
        'code' => 'tuzba',
        'name' => 'Tužba',
        'body' => <<<'TXT'
{{predmet.sud}}

TUŽBA

Tužitelj: {{stranka.naziv}}, OIB {{stranka.oib}}, {{stranka.adresa}}
Tuženik: {{protustranka.naziv}}
Vrijednost predmeta spora: {{predmet.vrijednost}}
Spis ureda: {{predmet.broj}} — {{predmet.naziv}}

Punomoćnik tužitelja: {{ured.naziv}}, OIB {{ured.oib}}, {{ured.adresa}}, {{ured.grad}}

I. Tuže se tuženik da tužitelju plati tražbinu po predmetu {{predmet.naziv}}, sa zakonskom zateznom kamatom i troškom postupka.

II. Obrazloženje
[Činjenice i dokazi]

{{ured.grad}}, {{datum}}.

________________________
{{ured.naziv}}
TXT,
    ],
    [
        'code' => 'opomena',
        'name' => 'Opomena',
        'body' => <<<'TXT'
OPOMENA PRIJE POKRETANJA POSTUPKA

Primatelj: {{protustranka.naziv}}
Pošiljatelj: {{stranka.naziv}}, OIB {{stranka.oib}}, {{stranka.adresa}}
Putem: {{ured.naziv}}, OIB {{ured.oib}}, {{ured.adresa}}, {{ured.grad}}
Predmet: {{predmet.naziv}} ({{predmet.broj}})

Pozivamo Vas da u roku od 8 dana od primitka podmirite dospjelu obvezu prema predmetu {{predmet.naziv}}, u vrijednosti {{predmet.vrijednost}}.

Ako obveza ne bude podmirena, ovlašteni smo pokrenuti postupak i zatražiti trošak.

{{ured.grad}}, {{datum}}.

________________________
{{ured.naziv}}
TXT,
    ],
];
