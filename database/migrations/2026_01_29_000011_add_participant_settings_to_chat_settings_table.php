<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->text('pending_participants')->nullable()->after('bot_number');
            $table->text('stage_new_participants')->nullable()->after('pending_participants');
            $table->text('stage_qualified_participants')->nullable()->after('stage_new_participants');
            $table->text('stage_in_progress_participants')->nullable()->after('stage_qualified_participants');
            $table->text('stage_resolved_participants')->nullable()->after('stage_in_progress_participants');
            $table->text('stage_archived_participants')->nullable()->after('stage_resolved_participants');
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'pending_participants',
                'stage_new_participants',
                'stage_qualified_participants',
                'stage_in_progress_participants',
                'stage_resolved_participants',
                'stage_archived_participants',
            ]);
        });
    }
};
