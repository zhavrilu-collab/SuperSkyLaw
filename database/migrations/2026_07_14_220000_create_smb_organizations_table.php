<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('core_user_id')->nullable()->unique()->after('id');
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('pending');
            $table->string('plan')->default('basic');
            $table->timestamp('status_changed_at')->nullable();
            $table->string('email')->nullable();
            $table->string('oib', 11)->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('core_user_id');
        });
    }
};
