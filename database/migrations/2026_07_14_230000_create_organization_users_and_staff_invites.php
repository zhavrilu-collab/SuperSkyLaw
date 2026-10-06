<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('city')->nullable()->after('phone');
        });

        Schema::create('organization_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('staff_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 32);
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['organization_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invites');
        Schema::dropIfExists('organization_users');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['phone', 'city']);
        });
    }
};
