/**
 * Validation functions for collaborator install search form
 */

/**
 * Validate alpha-numeric input (chữ và số)
 * @param {string} inputId - ID của input field
 * @param {number} maxLength - Độ dài tối đa
 * @param {Function} onValidationChange - Callback khi validation thay đổi
 */
function validateAlphaNumeric(inputId, maxLength, onValidationChange = null) {
    const inputField = $('#' + inputId);
    inputField.on('input', function() {
        let value = $(this).val();
        // Xóa ký tự không phải chữ/số
        let sanitizedValue = value.replace(/[^a-zA-Z0-9]/g, '');
        let hasInvalidChars = (value !== sanitizedValue && value !== '');
        let isTooLong = (sanitizedValue.length >= maxLength);

        if (hasInvalidChars) {
            $(this).val(sanitizedValue);
            value = sanitizedValue;
            isTooLong = (value.length >= maxLength);
        } else if (value.length > maxLength) {
            sanitizedValue = value.substring(0, maxLength);
            $(this).val(sanitizedValue);
            isTooLong = true;
        }

        if (hasInvalidChars || isTooLong) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        
        if (onValidationChange && typeof onValidationChange === 'function') {
            onValidationChange();
        }
    });
}

/**
 * Validate product name (chữ, số và khoảng trắng)
 * @param {string} inputId - ID của input field
 * @param {number} maxLength - Độ dài tối đa
 * @param {Function} onValidationChange - Callback khi validation thay đổi
 */
function validateProductsName(inputId, maxLength, onValidationChange = null) {
    const inputField = $('#' + inputId);
    inputField.on('input', function() {
        let value = $(this).val();
        // Xóa ký tự không phải chữ/số/khoảng trắng
        let sanitizedValue = value.replace(/[^\p{L}\p{N}\s]/gu, '');
        let hasInvalidChars = (value !== sanitizedValue && value !== '');
        let isTooLong = (sanitizedValue.length >= maxLength);

        if (hasInvalidChars) {
            $(this).val(sanitizedValue);
            isTooLong = (sanitizedValue.length >= maxLength);
        } else if (value.length > maxLength) {
            sanitizedValue = value.substring(0, maxLength);
            $(this).val(sanitizedValue);
            isTooLong = true;
        }

        if (hasInvalidChars || isTooLong) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        
        if (onValidationChange && typeof onValidationChange === 'function') {
            onValidationChange();
        }
    });
}

/**
 * Validate alpha-space input (chữ và khoảng trắng)
 * @param {string} inputId - ID của input field
 * @param {number} maxLength - Độ dài tối đa
 * @param {Function} onValidationChange - Callback khi validation thay đổi
 */
function validateAlphaSpace(inputId, maxLength, onValidationChange = null) {
    const inputField = $('#' + inputId);
    inputField.on('input', function() {
        let value = $(this).val();
        let sanitizedValue = value.replace(/[^\p{L}\s]/gu, '');

        if (sanitizedValue.length > maxLength) {
            sanitizedValue = sanitizedValue.substring(0, maxLength);
        }

        let hasError = (value !== sanitizedValue && value !== '');
        let isTooLong = (sanitizedValue.length >= maxLength);

        if (value !== sanitizedValue) {
            $(this).val(sanitizedValue);
            value = sanitizedValue;
            isTooLong = (value.length >= maxLength);
        }

        if (hasError || isTooLong) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        
        if (onValidationChange && typeof onValidationChange === 'function') {
            onValidationChange();
        }
    });
}

/**
 * Validate numeric input (chỉ số)
 * @param {string} inputId - ID của input field
 * @param {number} maxLength - Độ dài tối đa
 * @param {Function} onValidationChange - Callback khi validation thay đổi
 */
function validateNumeric(inputId, maxLength, onValidationChange = null) {
    const inputField = $('#' + inputId);
    inputField.on('input', function() {
        let value = $(this).val();
        let sanitizedValue = value.replace(/[^0-9]/g, '');

        if (sanitizedValue.length > maxLength) {
            sanitizedValue = sanitizedValue.substring(0, maxLength);
        }

        let hasError = (value !== sanitizedValue && value !== '');
        let isTooLong = (sanitizedValue.length >= maxLength);

        if (value !== sanitizedValue) {
            $(this).val(sanitizedValue);
            value = sanitizedValue;
            isTooLong = (value.length >= maxLength);
        }

        if (hasError || isTooLong) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        
        if (onValidationChange && typeof onValidationChange === 'function') {
            onValidationChange();
        }
    });
}

/**
 * Validate date range (từ ngày và đến ngày)
 * @param {string} fromDateSelector - Selector của input "Từ ngày"
 * @param {string} toDateSelector - Selector của input "Đến ngày"
 * @param {Function} onValidationChange - Callback khi validation thay đổi
 * @returns {boolean} true nếu hợp lệ, false nếu không hợp lệ
 */
function validateDates(fromDateSelector = '#tungay', toDateSelector = '#denngay', onValidationChange = null) {
    const $tungay = $(fromDateSelector);
    const $denngay = $(toDateSelector);
    const fromDate = $tungay.val();
    const toDate = $denngay.val();
    const today = new Date().toISOString().split('T')[0];

    let isValid = true;

    // Xóa lỗi cũ - xóa cả class và thông báo lỗi
    $tungay.removeClass('is-invalid');
    $denngay.removeClass('is-invalid');
    $tungay.next('.invalid-feedback').remove();
    $denngay.next('.invalid-feedback').remove();

    // Yêu cầu phải nhập cả hai ngày
    if ((fromDate && !toDate) || (!fromDate && toDate)) {
        if (!fromDate) {
            $tungay.addClass('is-invalid').after('<div class="invalid-feedback d-block">Vui lòng nhập cả hai ngày.</div>');
        }
        if (!toDate) {
            $denngay.addClass('is-invalid').after('<div class="invalid-feedback d-block">Vui lòng nhập cả hai ngày.</div>');
        }
        isValid = false;
    }

    // Kiểm tra ngày tương lai cho "Từ ngày"
    if (fromDate && fromDate > today) {
        $tungay.addClass('is-invalid');
        // Chỉ thêm thông báo lỗi nếu chưa có (tránh trùng lặp)
        if ($tungay.next('.invalid-feedback').length === 0) {
            $tungay.after('<div class="invalid-feedback d-block">"Từ ngày" không được ở tương lai.</div>');
        }
        isValid = false;
    }

    // Kiểm tra ngày tương lai cho "Đến ngày"
    if (toDate && toDate > today) {
        $denngay.addClass('is-invalid');
        // Chỉ thêm thông báo lỗi nếu chưa có (tránh trùng lặp)
        if ($denngay.next('.invalid-feedback').length === 0) {
            $denngay.after('<div class="invalid-feedback d-block">"Đến ngày" không được ở tương lai.</div>');
        }
        isValid = false;
    }

    // Kiểm tra logic khi có cả hai ngày
    if (fromDate && toDate) {
        if (fromDate > toDate) {
            $denngay.addClass('is-invalid');
            // Chỉ thêm thông báo lỗi nếu chưa có (tránh trùng lặp)
            if ($denngay.next('.invalid-feedback').length === 0) {
                $denngay.after('<div class="invalid-feedback d-block">"Đến ngày" phải sau hoặc bằng "Từ ngày".</div>');
            }
            isValid = false;
        }
    }

    if (onValidationChange && typeof onValidationChange === 'function') {
        onValidationChange();
    }
    
    return isValid;
}

/**
 * Check form validity tổng thể
 * @param {string} formSelector - Selector của form
 * @param {string} submitButtonSelector - Selector của nút submit
 * @param {string} fromDateSelector - Selector của input "Từ ngày"
 * @param {string} toDateSelector - Selector của input "Đến ngày"
 */
function checkFormValidity(formSelector = '#searchForm', submitButtonSelector = '#btnSearch', fromDateSelector = '#tungay', toDateSelector = '#denngay') {
    // 1. Check tất cả input có class 'is-invalid' bên trong form
    const hasInputErrors = $(formSelector + ' .is-invalid').length > 0;

    // 2. Check logic ngày tháng
    const fromDate = $(fromDateSelector).val();
    const toDate = $(toDateSelector).val();
    const today = new Date().toISOString().split('T')[0];
    let hasDateErrors = false;

    // Yêu cầu phải nhập cả hai ngày
    if ((fromDate && !toDate) || (!fromDate && toDate)) {
        hasDateErrors = true;
    }
    // Kiểm tra logic khi có cả hai ngày
    if (fromDate && toDate && fromDate > toDate) {
        hasDateErrors = true;
    }
    if (toDate && toDate > today) {
        hasDateErrors = true;
    }
    if (fromDate && fromDate > today) {
        hasDateErrors = true;
    }
    // Kiểm tra nếu có class is-invalid trên các input ngày
    if ($(fromDateSelector).hasClass('is-invalid') || $(toDateSelector).hasClass('is-invalid')) {
        hasDateErrors = true;
    }

    // 3. Vô hiệu hóa nút nếu có BẤT KỲ lỗi nào
    $(submitButtonSelector).prop('disabled', hasInputErrors || hasDateErrors);
}

