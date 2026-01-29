<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('chat_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
