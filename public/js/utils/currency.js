/**
 * Currency utility functions
 * Các hàm tiện ích cho xử lý tiền tệ
 */

/**
 * Format currency input (VNĐ format)
 * @param {jQuery|string} input - jQuery object hoặc selector của input
 */
function formatCurrency(input) {
    const $input = $(input);
    let value = $input.val().replace(/[^0-9]/g, '');
    if (!value) {
        $input.val('');
        return;
    }
    let num = parseInt(value, 10);
    if (isNaN(num)) {
        $input.val('');
        return;
    }
    $input.val(num.toLocaleString('vi-VN'));
}

/**
 * Lấy giá trị số thô từ trường tiền tệ
 * @param {jQuery|string} input - jQuery object hoặc selector của input
 * @returns {string} Giá trị số thô (ví dụ: "1000000")
 */
function getCurrencyValue(input) {
    const $input = $(input);
    let value = $input.val() || '';
    return value.replace(/[^0-9]/g, '') || '0';
}

