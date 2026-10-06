<?php

use App\Support\OfficeThemes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('theme_color')->default(OfficeThemes::DEFAULT);
        });

        DB::table('organizations')
            ->whereNotIn('theme_color', OfficeThemes::keys())
            ->orWhereNull('theme_color')
            ->update(['theme_color' => OfficeThemes::DEFAULT]);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('theme_color');
        });
    }
};
