<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('office_kind', 32)->nullable()->after('name');
            $table->string('mbs', 12)->nullable()->after('oib');
        });

        Schema::create('lawyer_directory_entries', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 40)->unique();
            $table->string('name');
            $table->string('office_kind', 32);
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('search_normalized');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('office_kind');
            $table->index('search_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawyer_directory_entries');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['office_kind', 'mbs']);
        });
    }
};
