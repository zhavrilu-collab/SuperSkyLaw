<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutes', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('title');
            $table->string('citation', 40);
            $table->string('document_type', 40);
            $table->date('published_on')->nullable();
            $table->string('source_url', 500);
            $table->longText('text_html')->nullable();
            $table->longText('text_plain')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('statute_sync_states', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20)->unique();
            $table->json('cursor');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('matter_statute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('statute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['matter_id', 'statute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matter_statute');
        Schema::dropIfExists('statute_sync_states');
        Schema::dropIfExists('statutes');
    }
};
