/**
 * Collaborator Install Preview Page JavaScript
 * Xử lý logic cho trang xem trước báo cáo Excel
 */

const CollaboratorInstallPreview = {
    /**
     * Show sheet by number
     * @param {number} sheetNumber - Sheet number (1-4)
     */
    showSheet: function(sheetNumber) {
        // Hide all sheets
        for (let i = 1; i <= 4; i++) {
            document.getElementById('sheet' + i).classList.remove('active');
            document.querySelectorAll('.sheet-tab button')[i - 1].classList.remove('active');
        }
        
        // Show selected sheet
        document.getElementById('sheet' + sheetNumber).classList.add('active');
        document.querySelectorAll('.sheet-tab button')[sheetNumber - 1].classList.add('active');
    },
    
    /**
     * Setup download button with cooldown
     */
    setupDownload: function(exportUrl, startDate, endDate) {
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
                    showSwalMessage('info', 'Vui lòng chờ', 'Bạn vừa tải báo cáo. Vui lòng thử lại sau khi hết thời gian chờ.');
                }
                return;
            }
            
            // Build URL and fetch to capture JSON throttle from server
            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate
            });
            
            if (window.Swal) {
                showSwalLoading('Đang xuất file...', 'Vui lòng chờ trong giây lát');
            }
            
            startCooldown(DOWNLOAD_SECONDS);
            
            fetch(exportUrl + '?' + params.toString())
                .then(response => {
                    if (window.Swal) Swal.close();
                    const ct = response.headers.get('Content-Type') || '';
                    if (ct.includes('application/json')) {
                        return response.json().then(json => {
                            if (window.Swal) {
                                showSwalMessage('error', 'Lỗi', json.message || 'Lỗi máy chủ');
                            }
                        });
                    }
                    return response.blob().then(blob => {
                        downloadBlob(blob, 'THỐNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT.xlsx');
                    });
                })
                .catch(() => {
                    if (window.Swal) Swal.close();
                    if (window.Swal) {
                        showSwalMessage('error', 'Lỗi', 'Lỗi server.');
                    }
                });
        });
    },
    
    /**
     * Initialize preview page
     */
    init: function(exportUrl, startDate, endDate) {
        // Setup download button
        this.setupDownload(exportUrl, startDate, endDate);
    }
};

// Export to global scope
window.showSheet = function(sheetNumber) {
    CollaboratorInstallPreview.showSheet(sheetNumber);
};

