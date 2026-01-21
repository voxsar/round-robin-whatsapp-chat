<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'instance',
        'group_jid',
        'group_subject',
        'status',
    ];
}
