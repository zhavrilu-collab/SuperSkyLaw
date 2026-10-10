<?php

use App\Services\StatuteImporter;
use App\Services\StatuteWorkGrouper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statute_works', function (Blueprint $table) {
            $table->string('area', 32)->nullable()->index();
        });

        app(StatuteImporter::class)->fillPublicationDates();
        app(StatuteWorkGrouper::class)->assignAreas();
    }

    public function down(): void
    {
        Schema::table('statute_works', function (Blueprint $table) {
            $table->dropIndex(['area']);
            $table->dropColumn('area');
        });
    }
};
