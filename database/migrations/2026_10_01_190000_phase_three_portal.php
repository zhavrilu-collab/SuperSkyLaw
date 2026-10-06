<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('calendar_feed_token', 64)->nullable()->unique();
            $table->string('mail_intake_token', 64)->nullable()->unique();
        });

        Schema::table('timeline_entries', function (Blueprint $table) {
            $table->boolean('visible_to_client')->default(false);
        });

        Schema::table('matter_documents', function (Blueprint $table) {
            $table->boolean('shared_with_client')->default(false);
        });

        Schema::table('court_events', function (Blueprint $table) {
            $table->string('source')->default('office');
            $table->string('external_uid')->nullable();
            $table->unique(['organization_id', 'external_uid']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('e_invoice_status')->default('none');
            $table->string('e_invoice_path')->nullable();
            $table->text('e_invoice_error')->nullable();
            $table->timestamp('e_invoice_sent_at')->nullable();
        });

        Schema::create('client_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->unique(['organization_id', 'email']);
            $table->unique(['organization_id', 'party_id']);
        });

        Schema::create('calendar_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('feed_url');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_subscriptions');
        Schema::dropIfExists('client_accounts');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['e_invoice_status', 'e_invoice_path', 'e_invoice_error', 'e_invoice_sent_at']);
        });
        Schema::table('court_events', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'external_uid']);
            $table->dropColumn(['source', 'external_uid']);
        });
        Schema::table('matter_documents', function (Blueprint $table) {
            $table->dropColumn('shared_with_client');
        });
        Schema::table('timeline_entries', function (Blueprint $table) {
            $table->dropColumn('visible_to_client');
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['calendar_feed_token', 'mail_intake_token']);
        });
    }
};
