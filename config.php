<?php
// config.php - Phantom config
return [
    // Domain kamu nanti (tanpa trailing slash)
    'domain' => 'https://phantom.yourdomain.com',

    // Telegram Bot
    'telegram' => [
        'enabled'   => true,
        'bot_token' => 'ISI_BOT_TOKEN_KAMU',      // dari @BotFather
        'chat_id'   => 'ISI_CHAT_ID_KAMU',        // ID kamu / group
    ],

    // Security
    'admin_password' => 'sable_phantom_2026',     // ganti

    // Default redirect kalau user tidak set
    'default_redirect' => 'https://www.google.com',
];
