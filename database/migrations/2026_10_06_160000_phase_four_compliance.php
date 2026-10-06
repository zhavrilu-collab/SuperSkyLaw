<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('trust_iban', 34)->nullable()->after('iban');
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->timestamp('sms_consent_at')->nullable()->after('phone');
        });

        Schema::table('matters', function (Blueprint $table) {
            $table->boolean('spnft_required')->default(false)->after('status');
        });

        Schema::table('court_events', function (Blueprint $table) {
            $table->string('e_oglasna_url', 500)->nullable()->after('notes');
        });

        Schema::create('ethical_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 500);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['matter_id', 'user_id']);
        });

        Schema::create('spnft_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('item', 40);
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->unique(['matter_id', 'item']);
        });

        Schema::create('limitation_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('basis', 40);
            $table->date('starts_on');
            $table->date('suggested_on');
            $table->foreignId('court_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('matter_id');
        });

        Schema::create('trust_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 8);
            $table->unsignedInteger('amount_cents');
            $table->date('occurred_on');
            $table->string('counterparty')->nullable();
            $table->string('purpose');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('signature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_document_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('prepared');
            $table->string('provider_reference')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('matter_document_id');
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('court_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 50)->nullable();
            $table->text('body');
            $table->unsignedInteger('cost_cents')->default(0);
            $table->string('status', 32);
            $table->timestamps();
        });

        DB::table('matters')->where('kind', 'commercial')->update(['spnft_required' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('signature_requests');
        Schema::dropIfExists('trust_movements');
        Schema::dropIfExists('limitation_estimates');
        Schema::dropIfExists('spnft_checks');
        Schema::dropIfExists('ethical_walls');
        Schema::table('court_events', function (Blueprint $table) {
            $table->dropColumn('e_oglasna_url');
        });
        Schema::table('matters', function (Blueprint $table) {
            $table->dropColumn('spnft_required');
        });
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn('sms_consent_at');
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('trust_iban');
        });
    }
};
