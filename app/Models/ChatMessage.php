<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_session_id',
        'user_id',
        'sender',
        'sender_name',
        'sender_number',
        'text',
        'source',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
