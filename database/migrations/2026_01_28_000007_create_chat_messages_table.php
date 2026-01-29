<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->string('sender')->default('system');
            $table->string('sender_name')->nullable();
            $table->string('sender_number')->nullable();
            $table->text('text');
            $table->string('source')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['chat_session_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
