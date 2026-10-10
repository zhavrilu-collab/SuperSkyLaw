<?php

use App\Services\StatuteWorkGrouper;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(StatuteWorkGrouper::class)->regroup();
    }

    public function down(): void
    {
        //
    }
};
