<?php
$config = require __DIR__ . '/config.php';
$domain = rtrim($config['domain'], '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZENTRIX — Silent Link Tracker</title>
    <meta name="description" content="ZENTRIX creates silent tracking links with precise location and postal data.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>◈</text></svg>">
</head>
<body>
    <div class="bg-grid"></div>

    <header class="topbar">
        <div class="logo">
            <img src="assets/img/logo.png" alt="ZENTRIX Logo">
            <span class="name">ZENTRIX</span>
        </div>
        <nav style="display:flex; align-items:center; gap:16px;">
            <button id="themeToggle" class="theme-toggle" title="Toggle theme">☀️</button>
            <a href="liveview.php">Live View</a>
            <a href="admin.php">Admin</a>
        </nav>
    </header>

    <main class="hero">
        <div class="hero-content">
            <h1>Create a silent link</h1>
            <p class="subtitle">One click. Full picture. Instant Telegram alert.</p>

            <div class="card create-card">
                <div class="field">
                    <label for="target">Destination URL</label>
                    <input type="url" id="target" placeholder="https://example.com" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="customId">Custom ID <span class="optional">(optional)</span></label>
                    <input type="text" id="customId" placeholder="target01" maxlength="32" autocomplete="off">
                </div>

                <button id="createBtn" class="btn-primary">
                    <span>Generate ZENTRIX Link</span>
                </button>
            </div>

            <div id="result" class="card result-card hidden">
                <h3>Your ZENTRIX Link</h3>
                <div class="link-box">
                    <input type="text" id="outLink" readonly>
                    <button id="copyBtn" class="btn-copy">Copy</button>
                </div>
                <div class="result-actions">
                    <a id="liveLink" href="#" target="_blank" class="btn-secondary">Open Live View</a>
                </div>
                <p class="hint">Share the link above. Every click sends a full report to your Telegram.</p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>ZENTRIX · Built for precision</p>
    </footer>

    <script>
        const DOMAIN = <?= json_encode($domain) ?>;
    </script>
    <script src="assets/js/homepage.js"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
