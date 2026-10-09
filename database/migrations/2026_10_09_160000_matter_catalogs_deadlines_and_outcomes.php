<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type');
            $table->string('city');
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('dispute_categories', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('name');
            $table->string('hint')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['kind', 'name']);
        });

        Schema::table('matters', function (Blueprint $table) {
            $table->string('office_position')->nullable()->after('status');
            $table->string('outcome')->nullable()->after('office_position');
            $table->foreignId('court_id')->nullable()->after('spnft_required')->constrained()->nullOnDelete();
            $table->foreignId('dispute_category_id')->nullable()->after('court_id')->constrained()->nullOnDelete();
            $table->string('court_case_number')->nullable()->after('court_name');
        });

        Schema::table('court_events', function (Blueprint $table) {
            $table->string('origin')->nullable()->after('is_preclusive');
            $table->string('statutory_rule')->nullable()->after('origin');
            $table->date('receipt_on')->nullable()->after('statutory_rule');
        });

        Schema::table('tariff_charges', function (Blueprint $table) {
            $table->foreignId('court_event_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
        });

        $rows = DB::table('matters')
            ->whereNull('court_case_number')
            ->whereNotNull('case_mark')
            ->whereNotNull('case_number')
            ->whereNotNull('case_year')
            ->get();

        foreach ($rows as $row) {
            DB::table('matters')->where('id', $row->id)->update([
                'court_case_number' => $row->case_mark.'-'.$row->case_number.'/'.$row->case_year,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tariff_charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('court_event_id');
        });

        Schema::table('court_events', function (Blueprint $table) {
            $table->dropColumn(['origin', 'statutory_rule', 'receipt_on']);
        });

        Schema::table('matters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('court_id');
            $table->dropConstrainedForeignId('dispute_category_id');
            $table->dropColumn(['office_position', 'outcome', 'court_case_number']);
        });

        Schema::dropIfExists('dispute_categories');
        Schema::dropIfExists('courts');
    }
};
