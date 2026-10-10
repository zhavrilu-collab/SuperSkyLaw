<?php

use App\Services\StatuteWorkGrouper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statute_works', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->char('title_key', 40)->unique();
            $table->string('base_external_id', 500)->nullable()->unique();
            $table->timestamps();
        });

        Schema::table('statutes', function (Blueprint $table) {
            $table->foreignId('work_id')->nullable()->constrained('statute_works')->nullOnDelete();
            $table->string('amends_external_id', 500)->nullable();
        });

        app(StatuteWorkGrouper::class)->attachMissing();
    }

    public function down(): void
    {
        Schema::table('statutes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_id');
            $table->dropColumn('amends_external_id');
        });

        Schema::dropIfExists('statute_works');
    }
};
