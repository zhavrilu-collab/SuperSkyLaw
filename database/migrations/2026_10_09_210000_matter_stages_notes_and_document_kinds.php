<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->date('filed_on')->nullable();
        });

        Schema::create('matter_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20);
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->text('body')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });

        Schema::create('matter_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::table('matter_documents', function (Blueprint $table) {
            $table->string('kind', 20)->default('other');
            $table->foreignId('stage_id')->nullable()->constrained('matter_stages')->nullOnDelete();
        });

        DB::table('matter_documents')->where('folder', 'Podnesci')->update(['kind' => 'brief']);
        DB::table('matter_documents')->where('folder', 'Punomoci')->update(['kind' => 'power']);
        DB::table('matter_documents')->where('folder', 'Dokazi')->update(['kind' => 'evidence']);

        $now = now();
        foreach (DB::table('matters')->select('id', 'organization_id', 'created_at')->get() as $matter) {
            DB::table('matter_stages')->insert([
                'organization_id' => $matter->organization_id,
                'matter_id' => $matter->id,
                'name' => 'Priprema',
                'color' => 'lime',
                'started_on' => substr((string) $matter->created_at, 0, 10),
                'position' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('matter_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stage_id');
            $table->dropColumn('kind');
        });

        Schema::dropIfExists('matter_notes');
        Schema::dropIfExists('matter_stages');

        Schema::table('matters', function (Blueprint $table) {
            $table->dropColumn('filed_on');
        });
    }
};
