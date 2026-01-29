<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use HasFactory;
  
    protected $fillable = [
        'session_id',
        'whatsapp_group_id',
        'pusher_channel',
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
        'person_id',
        'assigned_user_id',
        'first_response_at',
        'last_response_at',
        'away_sent_at',
        'ended_at',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'last_response_at' => 'datetime',
        'away_sent_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_session_id');
    }
}
