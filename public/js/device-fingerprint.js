/**
 * Device Fingerprint Generator
 * Tạo fingerprint duy nhất cho mỗi thiết bị/trình duyệt
 */
(function() {
    'use strict';

    /**
     * Tạo device fingerprint từ các thông tin trình duyệt
     */
    function generateDeviceFingerprint() {
        const components = [];

        // User Agent
        if (navigator.userAgent) {
            components.push(navigator.userAgent);
        }

        // Screen resolution
        if (screen.width && screen.height) {
            components.push(`${screen.width}x${screen.height}`);
        }

        // Timezone
        if (Intl && Intl.DateTimeFormat) {
            try {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                components.push(timezone);
            } catch (e) {
                // Fallback
                components.push(new Date().getTimezoneOffset().toString());
            }
        }

        // Language
        if (navigator.language) {
            components.push(navigator.language);
        }

        // Platform
        if (navigator.platform) {
            components.push(navigator.platform);
        }

        // Canvas fingerprint (nếu có thể)
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            if (ctx) {
                ctx.textBaseline = 'top';
                ctx.font = '14px Arial';
                ctx.fillText('Device fingerprint', 2, 2);
                const canvasFingerprint = canvas.toDataURL();
                components.push(canvasFingerprint.substring(0, 50)); // Lấy một phần để tránh quá dài
            }
        } catch (e) {
            // Ignore
        }

        // Kết hợp và hash
        const combined = components.join('|');
        return hashString(combined);
    }

    /**
     * Hash string thành hash code
     */
    function hashString(str) {
        let hash = 0;
        if (str.length === 0) return hash.toString();
        
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        
        return Math.abs(hash).toString(16);
    }

    /**
     * Lấy device fingerprint (lưu vào localStorage để tái sử dụng)
     */
    function getDeviceFingerprint() {
        const storageKey = 'device_fingerprint';
        let fingerprint = localStorage.getItem(storageKey);
        
        if (!fingerprint) {
            fingerprint = generateDeviceFingerprint();
            localStorage.setItem(storageKey, fingerprint);
        }
        
        return fingerprint;
    }

    /**
     * Lấy browser info để gửi lên server
     */
    function getBrowserInfo() {
        return {
            userAgent: navigator.userAgent || '',
            screen: {
                width: screen.width || 0,
                height: screen.height || 0
            },
            timezone: Intl ? (Intl.DateTimeFormat().resolvedOptions().timeZone || '') : '',
            language: navigator.language || '',
            platform: navigator.platform || ''
        };
    }

    // Export functions to window
    window.DeviceFingerprint = {
        get: getDeviceFingerprint,
        getBrowserInfo: getBrowserInfo,
        generate: generateDeviceFingerprint
    };
})();

