<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('bot_number')->nullable();
            $table->unsignedInteger('away_after_minutes')->default(5);
            $table->unsignedInteger('end_after_minutes')->default(10);
            $table->text('away_message')->nullable();
            $table->text('end_message')->nullable();
            $table->text('user_end_message')->nullable();
            $table->timestamps();
        });

        DB::table('chat_settings')->insert([
            'bot_number' => null,
            'away_after_minutes' => 5,
            'end_after_minutes' => 10,
            'away_message' => 'We are away at the moment. We will reply as soon as we are back.',
            'end_message' => 'Chat ended due to inactivity.',
            'user_end_message' => 'User Ended Chat.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_settings');
    }
};
