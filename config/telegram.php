<?php

return [
    'enabled' => env('TELEGRAM_ENABLED', false),
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
    'timezone' => env('TELEGRAM_TIMEZONE', 'Asia/Phnom_Penh'),
];
