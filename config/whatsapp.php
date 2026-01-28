<?php

return [
    'bot_number' => env('WHATSAPP_BOT_NUMBER', ''),
    'fixed_participants' => array_filter(explode(',', env('WHATSAPP_FIXED_PARTICIPANTS', ''))),
    'participant_pool' => array_filter(explode(',', env('WHATSAPP_PARTICIPANT_POOL', ''))),
    'round_robin' => env('WHATSAPP_ROUND_ROBIN', false),
    'group_create_endpoint' => env('WHATSAPP_GROUP_CREATE_ENDPOINT', ''),
    'api_token' => env('WHATSAPP_API_TOKEN', ''),
    'group_add_participant_endpoint' => env('WHATSAPP_GROUP_ADD_PARTICIPANT_ENDPOINT', '/group/participants/add/{instance}'),
    'group_remove_participant_endpoint' => env('WHATSAPP_GROUP_REMOVE_PARTICIPANT_ENDPOINT', '/group/participants/remove/{instance}'),
    'stage_participants' => [
        'new' => array_filter(explode(',', env('WHATSAPP_STAGE_NEW_PARTICIPANTS', ''))),
        'qualified' => array_filter(explode(',', env('WHATSAPP_STAGE_QUALIFIED_PARTICIPANTS', ''))),
        'in_progress' => array_filter(explode(',', env('WHATSAPP_STAGE_IN_PROGRESS_PARTICIPANTS', ''))),
        'resolved' => array_filter(explode(',', env('WHATSAPP_STAGE_RESOLVED_PARTICIPANTS', ''))),
        'archived' => array_filter(explode(',', env('WHATSAPP_STAGE_ARCHIVED_PARTICIPANTS', ''))),
    ],
    'stage_remove_participants' => [
        'new' => array_filter(explode(',', env('WHATSAPP_STAGE_NEW_REMOVE_PARTICIPANTS', ''))),
        'qualified' => array_filter(explode(',', env('WHATSAPP_STAGE_QUALIFIED_REMOVE_PARTICIPANTS', ''))),
        'in_progress' => array_filter(explode(',', env('WHATSAPP_STAGE_IN_PROGRESS_REMOVE_PARTICIPANTS', ''))),
        'resolved' => array_filter(explode(',', env('WHATSAPP_STAGE_RESOLVED_REMOVE_PARTICIPANTS', ''))),
        'archived' => array_filter(explode(',', env('WHATSAPP_STAGE_ARCHIVED_REMOVE_PARTICIPANTS', ''))),
    ],
];
