<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_events', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->after('matter_id')->constrained('matter_stages')->nullOnDelete();
        });

        Schema::table('timeline_entries', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->after('matter_id')->constrained('matter_stages')->nullOnDelete();
        });

        Schema::table('matter_notes', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->after('matter_id')->constrained('matter_stages')->nullOnDelete();
            $table->date('noted_on')->nullable()->after('body');
        });

        Schema::create('office_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('matter_stages')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_tasks');

        Schema::table('matter_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stage_id');
            $table->dropColumn('noted_on');
        });

        Schema::table('timeline_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stage_id');
        });

        Schema::table('court_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stage_id');
        });
    }
};
