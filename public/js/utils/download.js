/**
 * File download utility functions
 * Các hàm tiện ích cho download file
 */

/**
 * Download file từ blob
 * @param {Blob} blob - Blob object
 * @param {string} filename - Tên file để download
 */
function downloadBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

/**
 * Lấy tên file từ Content-Disposition header
 * @param {string} disposition - Content-Disposition header value
 * @param {string} defaultFilename - Tên file mặc định
 * @returns {string} Tên file
 */
function extractFilenameFromDisposition(disposition, defaultFilename = 'file') {
    if (!disposition || disposition.indexOf('filename=') === -1) {
        return defaultFilename;
    }
    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
    if (match && match[1]) {
        return decodeURIComponent(match[1].replace(/['"]/g, ''));
    }
    return defaultFilename;
}

/**
 * Download file từ URL với loading indicator
 * @param {string} url - URL để download
 * @param {Object} options - Options
 * @param {string} options.defaultFilename - Tên file mặc định
 * @param {string} options.acceptHeader - Accept header (default: 'application/pdf')
 * @param {Function} options.onSuccess - Callback khi thành công
 * @param {Function} options.onError - Callback khi lỗi
 * @param {boolean} options.useSwalLoading - Sử dụng Swal loading thay vì OpenWaitBox (default: false)
 */
function downloadFile(url, options = {}) {
    const {
        defaultFilename = 'file',
        acceptHeader = 'application/pdf',
        onSuccess = null,
        onError = null,
        useSwalLoading = false
    } = options;

    // Hiển thị loading
    if (useSwalLoading) {
        showSwalLoading('Đang tải file...');
    } else {
        if (typeof OpenWaitBox === 'function') {
            OpenWaitBox();
        }
    }

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': acceptHeader
        }
    })
        .then(response => {
            if (!response.ok) throw new Error('Lỗi tải file');

            // Kiểm tra nếu response là JSON (lỗi)
            const contentType = response.headers.get('Content-Type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(json => {
                    throw new Error(json.message || 'Lỗi tải file');
                });
            }

            // Lấy tên file từ header
            const disposition = response.headers.get('Content-Disposition');
            const filename = extractFilenameFromDisposition(disposition, defaultFilename);

            // Chuyển response thành blob
            return response.blob().then(blob => ({
                blob,
                filename
            }));
        })
        .then(({ blob, filename }) => {
            // Đóng loading
            if (useSwalLoading) {
                Swal.close();
            } else {
                if (typeof CloseWaitBox === 'function') {
                    CloseWaitBox();
                }
            }

            // Download file
            downloadBlob(blob, filename);

            // Hiển thị thông báo thành công
            if (useSwalLoading) {
                showSwalMessage('success', 'Tải file thành công!', '', {
                    showConfirmButton: false,
                    timer: 1500
                });
            }

            // Callback success
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(blob, filename);
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải file:', error);

            // Đóng loading
            if (useSwalLoading) {
                Swal.close();
            } else {
                if (typeof CloseWaitBox === 'function') {
                    CloseWaitBox();
                }
            }

            // Hiển thị lỗi
            showSwalMessage('error', 'Lỗi khi tải file!', error.message || 'Lỗi không xác định');

            // Callback error
            if (onError && typeof onError === 'function') {
                onError(error);
            }
        });
}

/**
 * Download file từ URL với cooldown period
 * @param {string} url - URL để download
 * @param {Object} options - Options
 * @param {string} options.defaultFilename - Tên file mặc định
 * @param {string} options.acceptHeader - Accept header
 * @param {string} options.cooldownKey - Key để lưu timestamp trong localStorage
 * @param {number} options.cooldownPeriodMs - Thời gian cooldown (ms)
 * @param {Function} options.onSuccess - Callback khi thành công
 * @param {Function} options.onError - Callback khi lỗi
 */
function downloadFileWithCooldown(url, options = {}) {
    const {
        defaultFilename = 'file.xlsx',
        acceptHeader = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        cooldownKey = 'lastExportTimestamp',
        cooldownPeriodMs = 2 * 60 * 1000, // 2 phút mặc định
        onSuccess = null,
        onError = null
    } = options;

    // Kiểm tra cooldown
    const lastExportTime = localStorage.getItem(cooldownKey);
    const currentTime = Date.now();
    
    if (lastExportTime) {
        const timeDiff = currentTime - parseInt(lastExportTime, 10);
        if (timeDiff < cooldownPeriodMs) {
            const timeLeftSeconds = Math.ceil((cooldownPeriodMs - timeDiff) / 1000);
            const minutes = Math.floor(timeLeftSeconds / 60);
            const seconds = timeLeftSeconds % 60;
            showSwalMessage('warning', 'Vui lòng chờ', `Bạn vừa xuất file. Vui lòng chờ ${minutes} phút ${seconds} giây trước khi xuất tiếp.`);
            return;
        }
    }

    // Hiển thị loading
    showSwalLoading('Đang xuất file...', 'Vui lòng chờ trong giây lát');

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': acceptHeader
        }
    })
        .then(response => {
            Swal.close();
            
            if (!response.ok) throw new Error('Lỗi tải file');

            // Kiểm tra nếu response là JSON (lỗi)
            const contentType = response.headers.get('Content-Type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(json => {
                    throw new Error(json.message || 'Lỗi tải file');
                });
            }

            // Lấy tên file từ header
            const disposition = response.headers.get('Content-Disposition');
            const filename = extractFilenameFromDisposition(disposition, defaultFilename);

            return response.blob().then(blob => ({
                blob,
                filename
            }));
        })
        .then(({ blob, filename }) => {
            // Lưu timestamp khi tải file thành công
            localStorage.setItem(cooldownKey, currentTime.toString());
            
            // Download file
            downloadBlob(blob, filename);

            // Callback success
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(blob, filename);
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải file:', error);
            showSwalMessage('error', 'Lỗi server', error.message || 'Lỗi không xác định');

            // Callback error
            if (onError && typeof onError === 'function') {
                onError(error);
            }
        });
}

