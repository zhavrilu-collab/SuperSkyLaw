<?php

use App\Support\IconStyles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organizations') || Schema::hasColumn('organizations', 'icon_style')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('icon_style', 32)->default(IconStyles::DEFAULT);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('organizations') || ! Schema::hasColumn('organizations', 'icon_style')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('icon_style');
        });
    }
};
