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
            ->whereNotNull('theme_style')
            ->whereNotIn('theme_style', ['kreda', 'obrub', 'pruga'])
            ->update(['theme_style' => null]);
    }

    public function down(): void
    {
    }
};
