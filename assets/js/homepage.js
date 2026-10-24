/**
 * Phantom – Homepage logic
 * Create tracking link + copy
 */

(function () {
    const createBtn = document.getElementById('createBtn');
    const targetInput = document.getElementById('target');
    const customIdInput = document.getElementById('customId');
    const resultBox = document.getElementById('result');
    const outLink = document.getElementById('outLink');
    const liveLink = document.getElementById('liveLink');
    const copyBtn = document.getElementById('copyBtn');

    function generateId() {
        return Math.random().toString(36).substring(2, 10);
    }

    function sanitizeId(str) {
        return str.replace(/[^a-zA-Z0-9_-]/g, '').substring(0, 32);
    }

    createBtn.addEventListener('click', function () {
        const target = targetInput.value.trim();
        if (!target) {
            targetInput.focus();
            targetInput.style.borderColor = '#f43f5e';
            setTimeout(() => targetInput.style.borderColor = '', 1500);
            return;
        }

        // Validasi sederhana URL
        try {
            new URL(target);
        } catch (e) {
            alert('Please enter a valid URL (include https://)');
            return;
        }

        let id = customIdInput.value.trim();
        id = id ? sanitizeId(id) : generateId();

        // DOMAIN datang dari index.php
        const link = `${DOMAIN}/track.php?id=${encodeURIComponent(id)}&r=${encodeURIComponent(target)}`;
        const live = `${DOMAIN}/liveview.php?id=${encodeURIComponent(id)}`;

        outLink.value = link;
        liveLink.href = live;

        resultBox.classList.remove('hidden');

        // Auto scroll ke result
        resultBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // Copy button
    copyBtn.addEventListener('click', function () {
        outLink.select();
        outLink.setSelectionRange(0, 99999); // mobile

        try {
            document.execCommand('copy');
            copyBtn.textContent = 'Copied!';
            setTimeout(() => {
                copyBtn.textContent = 'Copy';
            }, 1800);
        } catch (err) {
            // Fallback modern
            navigator.clipboard.writeText(outLink.value).then(() => {
                copyBtn.textContent = 'Copied!';
                setTimeout(() => {
                    copyBtn.textContent = 'Copy';
                }, 1800);
            });
        }
    });

    // Enter key di input
    [targetInput, customIdInput].forEach(el => {
        el.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                createBtn.click();
            }
        });
    });
})();
