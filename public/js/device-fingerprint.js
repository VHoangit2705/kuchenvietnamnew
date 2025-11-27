/**
 * MACHINE ID HELPER – Fixed Version
 * Sinh mã MACHINE_ID ổn định để whitelist thiết bị.
 */

(function() {
    "use strict";

    const STORAGE_KEY = "machine_id";

    function collectMachineTraits() {
        const parts = [];

        // Basic info
        parts.push(navigator.userAgent || "");
        parts.push(navigator.platform || "");
        parts.push(navigator.language || "");

        // Screen info
        if (typeof screen !== "undefined") {
            parts.push(`${screen.width || 0}x${screen.height || 0}`);
            parts.push(screen.colorDepth || 0);
        }

        // Timezone
        try {
            parts.push(
                Intl.DateTimeFormat().resolvedOptions().timeZone || ""
            );
        } catch (e) {
            parts.push("");
        }

        // Hardware info
        parts.push(navigator.hardwareConcurrency || 0);
        parts.push(navigator.deviceMemory || 0);

        // Extra fingerprints
        parts.push(getPluginsSignature());
        parts.push(getCanvasFingerprint());
        parts.push(getAudioFingerprint());

        return parts.join("|");
    }

    function getPluginsSignature() {
        try {
            if (!navigator.plugins) return "";
            const names = [];
            for (let i = 0; i < navigator.plugins.length; i++) {
                names.push(navigator.plugins[i].name);
            }
            return names.join(",");
        } catch {
            return "";
        }
    }

    function getCanvasFingerprint() {
        try {
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");
            if (!ctx) return "";

            ctx.textBaseline = "top";
            ctx.font = "16px Arial";
            ctx.fillText("machine-id", 2, 2);
            ctx.strokeRect(0, 0, 50, 50);

            return canvas.toDataURL().substring(0, 80);
        } catch {
            return "";
        }
    }

    function getAudioFingerprint() {
        try {
            const Ctx = window.OfflineAudioContext || window.webkitOfflineAudioContext;
            if (!Ctx) return "";

            const ctx = new Ctx(1, 1000, 44100);
            const oscillator = ctx.createOscillator();
            oscillator.type = "triangle";
            oscillator.frequency.value = 10000;

            const compressor = ctx.createDynamicsCompressor();
            oscillator.connect(compressor);
            compressor.connect(ctx.destination);
            oscillator.start(0);

            return "audio:" + oscillator.type + "," + compressor.threshold.value;
        } catch {
            return "";
        }
    }

    function hashString(str) {
        let hash = 0;
        if (!str) return "0";

        for (let i = 0; i < str.length; i++) {
            const chr = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + chr;
            hash |= 0;
        }
        return Math.abs(hash).toString(16);
    }

    function generateMachineId() {
        return hashString(collectMachineTraits());
    }

    function persist(id) {
        try {
            localStorage.setItem(STORAGE_KEY, id);
        } catch {}
    }

    function getStored() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch {
            return null;
        }
    }

    function getMachineId() {
        let id = getStored();
        if (!id) {
            id = generateMachineId();
            persist(id);
        }
        return id;
    }

    function getBrowserInfo() {
        return {
            userAgent: navigator.userAgent || "",
            language: navigator.language || "",
            platform: navigator.platform || "",
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || "",
            screen: {
                width: screen.width || 0,
                height: screen.height || 0,
                colorDepth: screen.colorDepth || 0
            }
        };
    }

    // Gán vào window SỚM nhất
    window.MachineIdentity = {
        get: getMachineId,
        getBrowserInfo: getBrowserInfo,
        regenerate: function() {
            const id = generateMachineId();
            persist(id);
            return id;
        }
    };

    // Giữ tương thích tên cũ
    window.DeviceFingerprint = {
        get: getMachineId,
        getBrowserInfo: getBrowserInfo,
        generate: generateMachineId
    };
})();
