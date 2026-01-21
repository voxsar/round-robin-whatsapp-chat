<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use HasFactory;
  
    protected $fillable = [
        'group_id',
        'provider_instance',
        'pusher_channel',
        'name',
        'email',
        'mobile',
        'status',
        'group_id',
        'instance',
        'group_jid',
        'group_subject',
    ];
}
