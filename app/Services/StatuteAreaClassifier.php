<?php

namespace App\Services;

use App\Enums\StatuteArea;

class StatuteAreaClassifier
{
    public function classify(string $title): StatuteArea
    {
        $title = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $title) ?? $title));

        foreach ($this->rules() as $area => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($title, $needle)) {
                    return StatuteArea::from($area);
                }
            }
        }

        return StatuteArea::Other;
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(): array
    {
        return [
            StatuteArea::Enforcement->value => [
                'ovršn',
                'ovrhe',
            ],
            StatuteArea::Criminal->value => [
                'kaznen',
                'prekršaj',
            ],
            StatuteArea::Labor->value => [
                'zakon o radu',
                'službenik',
                'službenic',
                'zaštiti na radu',
                'minimalnoj plaći',
                'radnom vremenu',
            ],
            StatuteArea::Tax->value => [
                'porez',
                'trošarin',
                'fiskalizac',
                'carinsk',
                'doprinos',
            ],
            StatuteArea::Commercial->value => [
                'trgovačkim društv',
                'trgovačkih praksi',
                'stečaj',
                'tržištu kapitala',
                'sudskom registru',
            ],
            StatuteArea::Administrative->value => [
                'upravn',
                'državne uprave',
                'samouprav',
            ],
            StatuteArea::Civil->value => [
                'parničn',
                'izvanparničn',
                'obveznim odnos',
                'vlasništvu i drugim stvarnim',
                'obiteljski zakon',
                'u obitelji',
                'nasljeđ',
                'zemljišn',
            ],
        ];
    }
}
