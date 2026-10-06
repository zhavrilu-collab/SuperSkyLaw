<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_versions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('citation');
            $table->unsignedInteger('point_value_cents');
            $table->date('effective_from');
            $table->timestamps();
        });

        Schema::create('tariff_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('value_from_cents');
            $table->unsignedBigInteger('value_to_cents')->nullable();
            $table->unsignedInteger('base_points');
            $table->unsignedBigInteger('threshold_cents')->nullable();
            $table->unsignedInteger('step_cents')->nullable();
            $table->unsignedSmallInteger('step_points')->nullable();
            $table->unsignedInteger('max_points')->nullable();
            $table->timestamps();
        });

        Schema::create('tariff_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_version_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('label');
            $table->string('kind');
            $table->unsignedSmallInteger('multiplier_percent')->default(100);
            $table->unsignedInteger('fixed_points')->nullable();
            $table->unsignedInteger('max_points')->nullable();
            $table->timestamps();
            $table->unique(['tariff_version_id', 'code']);
        });

        Schema::create('tariff_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tariff_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tariff_action_id')->constrained()->cascadeOnDelete();
            $table->string('audience');
            $table->string('description');
            $table->unsignedInteger('points');
            $table->unsignedInteger('amount_cents');
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('bill_to')->default('client')->after('description');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->foreignId('tariff_charge_id')->nullable()->after('expense_id')->constrained()->nullOnDelete();
        });

        Schema::table('tariff_charges', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });

        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_after_due');
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->unique(['invoice_id', 'days_after_due']);
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('body');
            $table->timestamps();
        });

        Schema::table('matter_documents', function (Blueprint $table) {
            $table->unsignedSmallInteger('version')->default(1)->after('mime');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('original_name');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        app(\App\Services\TariffCatalog::class)->install();
        app(\App\Services\DocumentTemplateLibrary::class)->install();
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
        Schema::table('matter_documents', function (Blueprint $table) {
            $table->dropColumn('version');
        });
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('invoice_reminders');
        Schema::table('tariff_charges', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tariff_charge_id');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('bill_to');
        });
        Schema::dropIfExists('tariff_charges');
        Schema::dropIfExists('tariff_actions');
        Schema::dropIfExists('tariff_bands');
        Schema::dropIfExists('tariff_versions');
    }
};
