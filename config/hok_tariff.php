<?php

return [
    'version' => [
        'code' => 'nn-138-2023',
        'name' => 'Tarifa NN 138/2023',
        'citation' => 'NN 138/2023, Tbr. 54.: vrijednost boda 2,00 EUR',
        'point_value_cents' => 200,
        'effective_from' => '2023-11-25',
    ],
    'bands' => [
        ['value_from_cents' => 0, 'value_to_cents' => 33200, 'base_points' => 25],
        ['value_from_cents' => 33201, 'value_to_cents' => 66400, 'base_points' => 50],
        ['value_from_cents' => 66401, 'value_to_cents' => 132700, 'base_points' => 75],
        ['value_from_cents' => 132701, 'value_to_cents' => 1327200, 'base_points' => 100],
        ['value_from_cents' => 1327201, 'value_to_cents' => 3318100, 'base_points' => 250],
        ['value_from_cents' => 3318101, 'value_to_cents' => 6636100, 'base_points' => 500],
        ['value_from_cents' => 6636101, 'value_to_cents' => 66361400, 'base_points' => 500, 'threshold_cents' => 6636100, 'step_cents' => 13300, 'step_points' => 1],
        ['value_from_cents' => 66361401, 'value_to_cents' => 132722800, 'base_points' => 4991, 'threshold_cents' => 66361400, 'step_cents' => 26500, 'step_points' => 1],
        ['value_from_cents' => 132722801, 'value_to_cents' => null, 'base_points' => 7496, 'threshold_cents' => 132722800, 'step_cents' => 66400, 'step_points' => 1, 'max_points' => 10000],
    ],
    'actions' => [
        ['code' => 'tuzba', 'label' => 'Sastavljanje tužbe (Tbr. 7. t. 1.)', 'kind' => 'band', 'multiplier_percent' => 100],
        ['code' => 'odgovor', 'label' => 'Odgovor na tužbu (Tbr. 8. t. 1.)', 'kind' => 'band', 'multiplier_percent' => 100],
        ['code' => 'rociste', 'label' => 'Ročište o glavnoj stvari (Tbr. 9. t. 1.)', 'kind' => 'band', 'multiplier_percent' => 100],
        ['code' => 'rociste_procesno', 'label' => 'Ročište o procesnim pitanjima (Tbr. 9. t. 2.)', 'kind' => 'band', 'multiplier_percent' => 50],
        ['code' => 'zalba', 'label' => 'Žalba protiv presude (Tbr. 10. t. 1.)', 'kind' => 'band', 'multiplier_percent' => 125],
        ['code' => 'zalba_rjesenje', 'label' => 'Žalba protiv rješenja (Tbr. 10. t. 4.)', 'kind' => 'band', 'multiplier_percent' => 50],
        ['code' => 'podnesak', 'label' => 'Obrazloženi podnesak (Tbr. 8. t. 3.)', 'kind' => 'band', 'multiplier_percent' => 50, 'max_points' => 100],
        ['code' => 'punomoc', 'label' => 'Punomoć, jednostavna izjava (Tbr. 33. t. 3.)', 'kind' => 'fixed', 'fixed_points' => 50],
        ['code' => 'opomena', 'label' => 'Opomena protustranci (Tbr. 37. t. 2.)', 'kind' => 'fixed', 'fixed_points' => 10],
        ['code' => 'opomena_obrazlozena', 'label' => 'Obrazložena opomena, jedna stranica (Tbr. 37. t. 1.)', 'kind' => 'fixed', 'fixed_points' => 25],
    ],
];
