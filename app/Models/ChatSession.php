<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = [
        'group_id',
        'provider_instance',
        'pusher_channel',
    ];
}
