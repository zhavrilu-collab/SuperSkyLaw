<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statutes', function (Blueprint $table) {
            $table->text('title')->change();
        });
    }

    public function down(): void
    {
        Schema::table('statutes', function (Blueprint $table) {
            $table->string('title')->change();
        });
    }
};
