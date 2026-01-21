<?php

return [
    'bot_number' => env('WHATSAPP_BOT_NUMBER', ''),
    'fixed_participants' => array_filter(explode(',', env('WHATSAPP_FIXED_PARTICIPANTS', ''))),
    'participant_pool' => array_filter(explode(',', env('WHATSAPP_PARTICIPANT_POOL', ''))),
    'round_robin' => env('WHATSAPP_ROUND_ROBIN', false),
    'group_create_endpoint' => env('WHATSAPP_GROUP_CREATE_ENDPOINT', ''),
    'api_token' => env('WHATSAPP_API_TOKEN', ''),
];
