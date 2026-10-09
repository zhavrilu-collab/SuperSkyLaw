<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_stage_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 40);
            $table->string('name', 80);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['organization_id', 'kind', 'position']);
            $table->unique(['organization_id', 'kind', 'name']);
        });

        Schema::table('dispute_categories', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('dispute_categories', function (Blueprint $table) {
            $table->dropUnique(['kind', 'name']);
            $table->unique(['organization_id', 'kind', 'name']);
        });

        $now = now();
        $templates = config('matter_stages.templates');

        foreach (DB::table('organizations')->pluck('id') as $organizationId) {
            foreach (\App\Enums\MatterKind::cases() as $kind) {
                $rows = $templates[$kind->value] ?? $templates['default'];

                foreach ($rows as $index => $row) {
                    DB::table('office_stage_templates')->insert([
                        'organization_id' => $organizationId,
                        'kind' => $kind->value,
                        'name' => $row['name'],
                        'position' => $index + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach (DB::table('dispute_categories')->whereNull('organization_id')->orderBy('id')->get() as $row) {
                $copyId = DB::table('dispute_categories')->insertGetId([
                    'organization_id' => $organizationId,
                    'kind' => $row->kind,
                    'name' => $row->name,
                    'hint' => $row->hint,
                    'sort' => $row->sort,
                    'active' => $row->active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('matters')
                    ->where('organization_id', $organizationId)
                    ->where('dispute_category_id', $row->id)
                    ->update(['dispute_category_id' => $copyId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('office_stage_templates');

        Schema::table('dispute_categories', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'kind', 'name']);
            $table->dropConstrainedForeignId('organization_id');
            $table->unique(['kind', 'name']);
        });
    }
};
