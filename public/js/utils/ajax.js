/**
 * AJAX utility functions
 * Các hàm tiện ích cho AJAX requests
 */

/**
 * Xử lý lỗi AJAX chuẩn
 * @param {Object} xhr - XMLHttpRequest object
 * @param {Function} customHandler - Custom error handler (optional)
 */
function handleAjaxError(xhr, customHandler = null) {
    if (typeof CloseWaitBox === 'function') {
        CloseWaitBox();
    }

    if (customHandler && typeof customHandler === 'function') {
        customHandler(xhr);
        return;
    }

    // Default error handling
    const errorMessage = xhr.responseJSON?.message || xhr.responseText || 'Lỗi khi thực hiện yêu cầu. Vui lòng thử lại.';
    showSwalMessage('error', errorMessage, '');
    console.error('AJAX Error:', xhr.responseText);
}

/**
 * Thực hiện AJAX request với xử lý lỗi chuẩn
 * @param {Object} config - Cấu hình AJAX
 * @param {string} config.url - URL endpoint
 * @param {string} config.method - HTTP method (GET, POST, etc.)
 * @param {Object} config.data - Data to send
 * @param {Function} config.onSuccess - Callback khi thành công
 * @param {Function} config.onError - Callback khi lỗi (optional)
 * @param {Object} config.swalSuccess - Cấu hình Swal khi thành công (optional)
 * @param {boolean} config.showLoading - Hiển thị loading (default: false)
 */
function performAjaxRequest(config) {
    const {
        url,
        method = 'GET',
        data = {},
        onSuccess = null,
        onError = null,
        swalSuccess = null,
        showLoading = false
    } = config;

    if (showLoading) {
        if (typeof OpenWaitBox === 'function') {
            OpenWaitBox();
        }
    }

    $.ajax({
        url: url,
        type: method,
        data: data,
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || $('meta[name="csrf-token"]').attr('content') || ''
        },
        success: function (response) {
            if (showLoading && typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            if (swalSuccess) {
                showSwalMessage(
                    swalSuccess.icon || 'success',
                    swalSuccess.title || 'Thành công!',
                    swalSuccess.text || '',
                    swalSuccess.options || {}
                );
            }
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(response);
            }
        },
        error: function (xhr) {
            if (showLoading && typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            if (onError && typeof onError === 'function') {
                onError(xhr);
            } else {
                handleAjaxError(xhr);
            }
        }
    });
}

