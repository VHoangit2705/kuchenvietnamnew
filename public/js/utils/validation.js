/**
 * Validation utility functions
 * Các hàm tiện ích cho validation
 */

/**
 * Validate phone number (10-11 digits)
 * @param {string|HTMLElement} selector - Phone number string or element selector
 * @returns {Object} { valid: boolean, message?: string }
 */
function validatePhoneNumber(selector) {
    let val;
    if (typeof selector === 'string') {
        val = selector.trim();
    } else {
        const $input = $(selector);
        val = $input.val().trim();
    }
    
    let regex = /^[0-9]{10,11}$/;
    if (!regex.test(val)) {
        return {
            valid: false,
            message: 'Số điện thoại không hợp lệ. Chỉ gồm 10–11 chữ số.'
        };
    }
    return { valid: true };
}

/**
 * Validate date input format (YYYY-MM-DD)
 * @param {string|HTMLElement} selector - Date string or element selector
 * @returns {Object} { valid: boolean, message?: string }
 */
function validateDateInput(selector) {
    let val;
    if (typeof selector === 'string') {
        val = selector;
    } else {
        const $input = $(selector);
        val = $input.val();
    }
    
    // Kiểm tra định dạng yyyy-mm-dd (HTML5 date input chuẩn trả về định dạng này)
    let regex = /^\d{4}-\d{2}-\d{2}$/;
    if (!regex.test(val)) {
        return { valid: false, message: 'Ngày không đúng định dạng.' };
    }

    // Kiểm tra ngày có hợp lệ hay không
    const parts = val.split('-');
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);

    // Tạo đối tượng Date JS
    const dateObj = new Date(year, month - 1, day);

    // Kiểm tra ngày hợp lệ: Date obj phải khớp đúng với giá trị nhập
    if (
        dateObj.getFullYear() !== year ||
        dateObj.getMonth() + 1 !== month ||
        dateObj.getDate() !== day
    ) {
        return { valid: false, message: 'Ngày không hợp lệ.' };
    }

    return { valid: true };
}

