/**
 * Xử lý download Excel với cooldown
 */

// Download with cooldown inside preview (works when embedded or standalone)
(function() {
    const COOLDOWN_KEY = 'export_excel_cooldown_until';
    const DOWNLOAD_SECONDS = 10;
    const $btn = document.getElementById('btnDownloadExcelPreview');
    if (!$btn) return;

    function setDisabledUI(remainingMs) {
        const s = Math.ceil(remainingMs / 1000);
        $btn.classList.add('disabled');
        $btn.setAttribute('aria-disabled', 'true');
        if (!$btn.dataset.originalText) $btn.dataset.originalText = $btn.innerHTML;
        $btn.innerHTML = '<i class="fas fa-hourglass-half me-1"></i>Chờ ' + s + 's';
    }
    function clearDisabledUI() {
        $btn.classList.remove('disabled');
        $btn.removeAttribute('aria-disabled');
        if ($btn.dataset.originalText) $btn.innerHTML = $btn.dataset.originalText;
    }
    function startCooldown(seconds) {
        const until = Date.now() + seconds * 1000;
        localStorage.setItem(COOLDOWN_KEY, String(until));
        let t = setInterval(function() {
            const remain = until - Date.now();
            if (remain <= 0) {
                clearInterval(t);
                localStorage.removeItem(COOLDOWN_KEY);
                clearDisabledUI();
                return;
            }
            setDisabledUI(remain);
        }, 1000);
        setDisabledUI(seconds * 1000);
    }
    function ensureCooldownUI() {
        const until = parseInt(localStorage.getItem(COOLDOWN_KEY) || '0', 10);
        if (until > Date.now()) {
            const timer = setInterval(function() {
                const remain = until - Date.now();
                if (remain <= 0) {
                    clearInterval(timer);
                    localStorage.removeItem(COOLDOWN_KEY);
                    clearDisabledUI();
                } else {
                    setDisabledUI(remain);
                }
            }, 1000);
            setDisabledUI(until - Date.now());
        }
    }

    ensureCooldownUI();

    $btn.addEventListener('click', function(e) {
        e.preventDefault();
        const until = parseInt(localStorage.getItem(COOLDOWN_KEY) || '0', 10);
        if (until > Date.now()) {
            if (window.Swal) {
                Swal.fire({ icon: 'info', text: 'Bạn vừa tải báo cáo. Vui lòng thử lại sau khi hết thời gian chờ.' });
            }
            return;
        }
        // Build URL and fetch to capture JSON throttle from server
        const params = new URLSearchParams({
            start_date: window.START_DATE || '',
            end_date: window.END_DATE || ''
        });
        if (window.Swal) {
            Swal.fire({
                title: 'Đang xuất file...',
                text: 'Vui lòng chờ trong giây lát',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }
        startCooldown(DOWNLOAD_SECONDS);
        const exportUrl = window.ROUTES.collaborator_export || '/collaborator/export';
        fetch(`${exportUrl}?${params.toString()}`)
            .then(response => {
                if (window.Swal) Swal.close();
                const ct = response.headers.get('Content-Type') || '';
                if (ct.includes('application/json')) {
                    return response.json().then(json => {
                        if (window.Swal) Swal.fire({ icon: 'error', text: json.message || 'Lỗi máy chủ' });
                    });
                }
                return response.blob().then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'THỐNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                });
            })
            .catch(() => {
                if (window.Swal) Swal.close();
                if (window.Swal) Swal.fire({ icon: 'error', text: 'Lỗi server.' });
            });
    });
})();

