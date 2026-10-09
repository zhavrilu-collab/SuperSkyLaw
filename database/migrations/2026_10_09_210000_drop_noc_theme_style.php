<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organizations') || ! Schema::hasColumn('organizations', 'theme_style')) {
            return;
        }

        DB::table('organizations')
            ->where('theme_style', 'noc')
            ->update(['theme_style' => 'pruga']);
    }

    public function down(): void
    {
        // Tema Noć više nije u izboru, pa se odabir ne vraća.
    }
};
