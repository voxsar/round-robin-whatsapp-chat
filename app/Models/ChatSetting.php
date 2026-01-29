<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_number',
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
