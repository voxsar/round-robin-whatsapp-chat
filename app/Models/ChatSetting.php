<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_number',
        'pending_participants',
        'stage_new_participants',
        'stage_qualified_participants',
        'stage_in_progress_participants',
        'stage_resolved_participants',
        'stage_archived_participants',
        'away_after_minutes',
        'end_after_minutes',
        'away_message',
        'end_message',
        'user_end_message',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create();
    }
}
