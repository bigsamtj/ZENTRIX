/**
 * Phantom – utils.js
 * Small collection of reusable helper functions
 */

const PhantomUtils = {

    /**
     * Simple debounce
     * @param {Function} fn 
     * @param {number} delay 
     */
    debounce(fn, delay = 300) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    },

    /**
     * Copy text to clipboard (with fallback)
     * @param {string} text 
     * @returns {Promise<boolean>}
     */
    async copyToClipboard(text) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            }
            // Fallback
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            const successful = document.execCommand('copy');
            document.body.removeChild(textarea);
            return successful;
        } catch (err) {
            console.warn('Copy failed', err);
            return false;
        }
    },

    /**
     * Format date to readable string
     * @param {string|Date} date 
     * @returns {string}
     */
    formatDate(date) {
        const d = new Date(date);
        if (isNaN(d.getTime())) return '-';
        return d.toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    },

    /**
     * Detect if current device is mobile
     * @returns {boolean}
     */
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) 
            || (navigator.maxTouchPoints && navigator.maxTouchPoints > 2);
    },

    /**
     * Simple sleep / delay
     * @param {number} ms 
     * @returns {Promise}
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Generate a short random ID
     * @param {number} length 
     * @returns {string}
     */
    randomId(length = 8) {
        const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },

    /**
     * Safely parse JSON
     * @param {string} str 
     * @param {*} fallback 
     * @returns {*}
     */
    safeJSONParse(str, fallback = null) {
        try {
            return JSON.parse(str);
        } catch {
            return fallback;
        }
    }
};

// Make it available globally if needed
window.PhantomUtils = PhantomUtils;
