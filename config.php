<?php
/**
 * Phantom - Configuration
 * Ready for deployment
 */

return [

    // Domain utama (tanpa trailing slash)
    // Ganti nanti setelah kamu dapat domain
    'domain' => 'https://YOUR-DOMAIN-HERE.com',

    // Telegram Bot
    'telegram' => [
        'enabled'   => true,
        'bot_token' => '8703136899:AAH8Uc6QlJqGoC_e18POb1NG4-SuSXmBc3M',
        'chat_id'   => '5301735906',
    ],

    // Password untuk admin.php
    'admin_password' => 'sable_phantom_2026',

    // Redirect default kalau user tidak set tujuan
    'default_redirect' => 'https://www.google.com',

    // Timeout request ke postcode.gov.ng (detik)
    'postcode_timeout' => 8,

    // Apakah coba ambil HTML dari postcode.gov.ng/find
    'enable_postcode_scrape' => true,

];
