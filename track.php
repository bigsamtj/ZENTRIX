function sendTelegram($message) {
    $cfg = require __DIR__ . '/config.php';
    if (!$cfg['telegram']['enabled']) return;

    $token = $cfg['telegram']['bot_token'];
    $chat  = $cfg['telegram']['chat_id'];
    if (!$token || !$chat) return;

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    curl_exec($ch);
    curl_close($ch);
}
