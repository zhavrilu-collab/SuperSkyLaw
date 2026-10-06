<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('address')->nullable()->after('city');
            $table->string('iban')->nullable()->after('address');
            $table->timestamp('trial_ends_at')->nullable()->after('plan');
        });

        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('name');
            $table->string('oib', 11)->nullable();
            $table->string('mbs')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('iban')->nullable();
            $table->string('contact_person')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
            $table->unique(['organization_id', 'oib']);
        });

        Schema::create('matters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('internal_number');
            $table->string('kind');
            $table->string('status')->default('active');
            $table->string('court_name')->nullable();
            $table->string('case_mark')->nullable();
            $table->string('case_number')->nullable();
            $table->unsignedSmallInteger('case_year')->nullable();
            $table->unsignedBigInteger('dispute_value_cents')->nullable();
            $table->string('billing_method')->default('hourly');
            $table->unsignedInteger('hourly_rate_cents')->nullable();
            $table->unsignedInteger('flat_fee_cents')->nullable();
            $table->text('success_fee_note')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'internal_number']);
        });

        Schema::create('matter_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['matter_id', 'user_id']);
        });

        Schema::create('matter_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->string('side');
            $table->timestamps();
            $table->unique(['matter_id', 'party_id']);
        });

        Schema::create('conflict_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('result');
            $table->json('matches');
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('acknowledged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('timeline_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('body');
            $table->timestamp('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('court_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->string('court_name')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_preclusive')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('deadline_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('offset_minutes');
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('in_app_sent_at')->nullable();
            $table->timestamps();
            $table->unique(['court_event_id', 'offset_minutes']);
        });

        Schema::create('office_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->foreignId('court_event_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('minutes')->default(0);
            $table->unsignedInteger('hourly_rate_cents')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('invoice_id')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('description');
            $table->unsignedInteger('amount_cents');
            $table->foreignId('invoice_id')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status')->default('unpaid');
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('vat_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->unsignedInteger('paid_cents')->default(0);
            $table->unsignedSmallInteger('vat_rate')->default(25);
            $table->string('buyer_name');
            $table->string('buyer_oib', 11)->nullable();
            $table->string('buyer_address')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('line_total_cents');
            $table->unsignedSmallInteger('vat_rate')->default(25);
            $table->foreignId('time_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->date('paid_on');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('matter_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('folder')->default('Podnesci');
            $table->string('original_name');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('description');
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('matter_documents');
        Schema::dropIfExists('payments');
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('office_notifications');
        Schema::dropIfExists('deadline_reminders');
        Schema::dropIfExists('court_events');
        Schema::dropIfExists('timeline_entries');
        Schema::dropIfExists('conflict_checks');
        Schema::dropIfExists('matter_parties');
        Schema::dropIfExists('matter_user');
        Schema::dropIfExists('matters');
        Schema::dropIfExists('parties');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['address', 'iban', 'trial_ends_at']);
        });
    }
};
