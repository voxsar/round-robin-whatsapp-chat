<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->timestamp('away_sent_at')->nullable()->after('last_response_at');
            $table->timestamp('ended_at')->nullable()->after('away_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->dropColumn(['away_sent_at', 'ended_at']);
        });
    }
};
