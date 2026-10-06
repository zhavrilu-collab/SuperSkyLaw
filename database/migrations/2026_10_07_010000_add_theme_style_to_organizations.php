<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organizations') || Schema::hasColumn('organizations', 'theme_style')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('theme_style', 32)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('organizations') || ! Schema::hasColumn('organizations', 'theme_style')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('theme_style');
        });
    }
};
