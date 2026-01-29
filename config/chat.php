<?php

return [
    'away_after_minutes' => env('CHAT_AWAY_AFTER_MINUTES', 5),
    'end_after_minutes' => env('CHAT_END_AFTER_MINUTES', 10),
    'away_message' => env('CHAT_AWAY_MESSAGE', 'We are away at the moment. We will reply as soon as we are back.'),
    'end_message' => env('CHAT_END_MESSAGE', 'Chat ended due to inactivity.'),
    'user_end_message' => env('CHAT_USER_END_MESSAGE', 'User Ended Chat.'),
];
