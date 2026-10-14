<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Javni imenik Hrvatske odvjetničke komore
    |--------------------------------------------------------------------------
    |
    | Excel nema OIB. Služi za naziv, oblik, adresu, mjesto i telefon.
    |
    */

    'xls_url' => env('HOK_DIRECTORY_XLS_URL', 'https://www.hok-cba.hr/downloads/imenik.xls'),

    'local_path' => env('HOK_DIRECTORY_LOCAL_PATH'),

];
