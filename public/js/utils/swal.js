/**
 * SweetAlert utility functions
 * Các hàm tiện ích cho SweetAlert2
 */

/**
 * Hiển thị thông báo Swal với cấu hình chuẩn
 * @param {string} icon - Icon type (success, error, warning, info)
 * @param {string} title - Tiêu đề
 * @param {string} text - Nội dung (optional)
 * @param {Object} options - Các tùy chọn bổ sung
 * @returns {Promise} Swal instance
 */
function showSwalMessage(icon, title, text = '', options = {}) {
    const config = {
        icon: icon,
        title: title,
        timer: options.timer || 3000,
        showConfirmButton: options.showConfirmButton !== undefined ? options.showConfirmButton : true,
        confirmButtonText: options.confirmButtonText || 'OK',
        ...options
    };

    if (text) {
        config.text = text;
    }

    return Swal.fire(config);
}

/**
 * Hiển thị dialog xác nhận với Swal
 * @param {string} title - Tiêu đề
 * @param {string} text - Nội dung
 * @param {string} confirmText - Text nút xác nhận (default: 'Xác nhận')
 * @param {string} cancelText - Text nút hủy (default: 'Hủy bỏ')
 * @returns {Promise} Swal result
 */
function showSwalConfirm(title, text, confirmText = 'Xác nhận', cancelText = 'Hủy bỏ') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
}

/**
 * Hiển thị loading với Swal
 * @param {string} title - Tiêu đề loading
 * @param {string} text - Nội dung phụ (optional)
 * @returns {Promise} Swal instance
 */
function showSwalLoading(title = 'Đang tải...', text = '') {
    const config = {
        title: title,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    };
    if (text) {
        config.text = text;
    }
    return Swal.fire(config);
}

