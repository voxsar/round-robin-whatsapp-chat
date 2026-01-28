<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('group_id')->nullable()->index();
            $table->string('provider_instance')->nullable()->index();
            $table->string('pusher_channel')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('instance')->nullable();
            $table->string('group_jid')->nullable();
            $table->string('group_subject')->nullable();
            $table->string('status')->default('active')->nullable();
            $table->string('session_id')->unique()->nullable();
            $table->string('whatsapp_group_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
