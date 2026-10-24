<?php
/**
 * Phantom - Configuration
 * Ganti nilai di bawah sesuai milikmu
 */

return [

    // Domain utama (tanpa trailing slash)
    // Contoh: https://phantom.yourdomain.com
    'domain' => 'https://phantom.yourdomain.com',

    // Telegram Bot
    'telegram' => [
        'enabled'   => true,
        'bot_token' => 'ISI_BOT_TOKEN_DARI_BOTFATHER',   // contoh: 7123456789:AAHxxxx
        'chat_id'   => 'ISI_CHAT_ID_KAMU',               // contoh: 123456789 atau -100xxxxxxxxxx
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
