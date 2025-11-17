/**
 * Machine Identity Helper
 * Sinh mã MACHINE_ID ổn định để whitelist thiết bị.
 */
(function() {
    'use strict';

    const STORAGE_KEY = 'machine_id';

    function collectMachineTraits() {
        const parts = [];

        parts.push(navigator.userAgent || '');
        parts.push(navigator.platform || '');
        parts.push(navigator.language || '');

        if (screen) {
            parts.push(`${screen.width || 0}x${screen.height || 0}`);
            parts.push(screen.colorDepth || 0);
        }

        parts.push(Intl && Intl.DateTimeFormat ? (Intl.DateTimeFormat().resolvedOptions().timeZone || '') : '');
        parts.push(navigator.hardwareConcurrency || 0);
        parts.push(navigator.deviceMemory || 0);
        parts.push(getPluginsSignature());
        parts.push(getCanvasFingerprint());
        parts.push(getAudioFingerprint());

        return parts.join('|');
    }

    function getPluginsSignature() {
        try {
            if (!navigator.plugins) {
                return '';
            }

            const names = [];
            for (let i = 0; i < navigator.plugins.length; i++) {
                names.push(navigator.plugins[i].name);
            }
            return names.join(',');
        } catch (error) {
            return '';
        }
    }

    function getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return '';
            }

            ctx.textBaseline = 'top';
            ctx.font = '16px Arial';
            ctx.fillText('machine-id', 2, 2);
            ctx.strokeRect(0, 0, 50, 50);
            return canvas.toDataURL().substring(0, 80);
        } catch (error) {
            return '';
        }
    }

    function getAudioFingerprint() {
        try {
            const ctx = new (window.OfflineAudioContext || window.webkitOfflineAudioContext)(1, 1000, 44100);
            const oscillator = ctx.createOscillator();
            oscillator.type = 'triangle';
            oscillator.frequency.value = 10000;

            const compressor = ctx.createDynamicsCompressor();
            oscillator.connect(compressor);
            compressor.connect(ctx.destination);
            oscillator.start(0);
            return 'audio:' + (oscillator.type || '') + ',' + compressor.threshold.value;
        } catch (error) {
            return '';
        }
    }

    function hashString(value) {
        let hash = 0;

        if (!value) {
            return '0';
        }

        for (let i = 0; i < value.length; i++) {
            const char = value.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash |= 0; // Convert to 32bit integer
        }

        return Math.abs(hash).toString(16);
    }

    function generateMachineId() {
        return hashString(collectMachineTraits());
    }

    function persistMachineId(id) {
        try {
            localStorage.setItem(STORAGE_KEY, id);
        } catch (error) {
            // ignore storage errors
        }
    }

    function getStoredMachineId() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            return null;
        }
    }

    function getMachineId() {
        let machineId = getStoredMachineId();

        if (!machineId) {
            machineId = generateMachineId();
            persistMachineId(machineId);
        }

        return machineId;
    }

    function getBrowserInfo() {
        return {
            userAgent: navigator.userAgent || '',
            language: navigator.language || '',
            platform: navigator.platform || '',
            timezone: Intl && Intl.DateTimeFormat ? (Intl.DateTimeFormat().resolvedOptions().timeZone || '') : '',
            screen: {
                width: screen.width || 0,
                height: screen.height || 0,
                colorDepth: screen.colorDepth || 0,
            }
        };
    }

    window.MachineIdentity = {
        get: getMachineId,
        getBrowserInfo,
        regenerate: () => {
            const id = generateMachineId();
            persistMachineId(id);
            return id;
        }
    };

    // Giữ tương thích ngược với tên gọi cũ
    window.DeviceFingerprint = {
        get: getMachineId,
        getBrowserInfo,
        generate: generateMachineId
    };
})();

