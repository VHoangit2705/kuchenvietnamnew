/**
 * Validation functions for checkwarranty module
 */

/**
 * Validate serial number for warranty check
 * @returns {boolean} true nếu hợp lệ, false nếu không
 */
function validateSerial() {
    const $input = $('#serial_number');
    const value = $input.val().trim();

    if (!value) {
        showError($input, "Vui lòng nhập mã tem bảo hành.", { errorContainer: '.card-body', errorSelector: '.error' });
        return false;
    }
    if (!/^[a-zA-Z0-9]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ và số.", { errorContainer: '.card-body', errorSelector: '.error' });
        return false;
    }
    if (value.length > 25) {
        showError($input, "Tối đa 25 ký tự.", { errorContainer: '.card-body', errorSelector: '.error' });
        return false;
    }

    hideError($input, { errorContainer: '.card-body', errorSelector: '.error' });
    return true;
}

