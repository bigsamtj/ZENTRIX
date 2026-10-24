<?php
/**
 * Phantom – Admin Panel
 * Simple password-protected log viewer
 */

session_start();
$config = require __DIR__ . '/config.php';

// Login handling
if (isset($_POST['password'])) {
    if ($_POST['password'] === $config['admin_password']) {
        $_SESSION['phantom_admin'] = true;
    } else {
        $error = 'Wrong password';
    }
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['phantom_admin']);
    header('Location: admin.php');
    exit;
}

// Not logged in → show login form
if (empty($_SESSION['phantom_admin'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phantom Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="bg-grid"></div>
    <div class="hero" style="min-height:100vh;align-items:center;">
        <div class="hero-content">
            <div class="card" style="max-width:360px;margin:0 auto;">
                <h2 style="text-align:center;margin-bottom:24px;">👻 Admin</h2>
                <?php if (!empty($error)): ?>
                    <p style="color:#f43f5e;text-align:center;margin-bottom:16px;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form method="post">
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" required autofocus>
                    </div>
                    <button type="submit" class="btn-primary">Login</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// ===== LOGGED IN =====
$logDir = __DIR__ . '/logs';
$files = is_dir($logDir) ? glob($logDir . '/*.json') : [];
rsort($files); // newest first
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phantom Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="bg-grid"></div>

    <header class="topbar">
        <div class="logo">
            <span class="ghost">👻</span>
            <span class="name">Phantom Admin</span>
        </div>
        <nav>
            <a href="index.php">Home</a>
            <a href="?logout=1">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h1 style="margin-bottom:24px;">All Tracking Logs</h1>

        <?php if (empty($files)): ?>
            <div class="card">
                <p style="color:var(--text-muted);">No logs yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($files as $file): 
                $id = basename($file, '.json');
                $logs = json_decode(file_get_contents($file), true) ?: [];
                $count = count($logs);
            ?>
                <div class="log-card">
                    <div class="meta">
                        <strong>ID: <?= htmlspecialchars($id) ?></strong>
                        <span><?= $count ?> hit<?= $count !== 1 ? 's' : '' ?></span>
                    </div>
                    <p style="margin:8px 0;">
                        <a href="liveview.php?id=<?= urlencode($id) ?>" class="btn-secondary" style="font-size:0.85rem;">Open Live View</a>
                    </p>

                    <?php if ($count > 0): 
                        $latest = end($logs);
                    ?>
                        <div style="margin-top:12px;font-size:0.9rem;color:var(--text-muted);">
                            Last hit: <?= htmlspecialchars($latest['time'] ?? '-') ?><br>
                            IP: <span class="ip" style="font-size:0.95rem;"><?= htmlspecialchars($latest['ip'] ?? '-') ?></span><br>
                            Loc: <?= htmlspecialchars(($latest['geo']['city'] ?? '') . ', ' . ($latest['geo']['region'] ?? '') . ', ' . ($latest['geo']['country'] ?? '')) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
