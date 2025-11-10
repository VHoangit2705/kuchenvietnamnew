/**
 * Common validation functions
 * Các hàm validate chung được sử dụng trong nhiều module
 */

/**
 * Validate các trường bắt buộc trong form
 * @param {string} formSelector - Selector của form
 * @returns {boolean} true nếu hợp lệ, false nếu không
 */
function validateRequired(formSelector) {
    let isValid = true;
    $(formSelector + ' .error').text(''); // Xóa lỗi cũ

    $(formSelector + ' [required]').each(function () {
        let $field = $(this);
        let value = $field.val();

        if (value === undefined || value === null) value = '';
        if (typeof value !== 'string') value = value.toString();
        let isEmpty = false;
        if ($field.is('select')) {
            isEmpty = (value === '');
        } else {
            isEmpty = (value.trim() === '');
        }

        if (isEmpty) {
            isValid = false;
            let $error = $field.siblings('.error');
            $error.text('Trường này là bắt buộc.');
        }
    });

    // Nếu là số điện thoại
    $(formSelector + ' input[type=text]#phone').each(function () {
        let result = validatePhoneNumber(this);
        if (!result.valid) {
            isValid = false;
            let $error = $(this).siblings('.error');
            $error.text(result.message);
        }
    })

    // Validate các input type=date có required
    $(formSelector + ' input[type=date]').each(function () {
        let result = validateDateInput(this);
        if (!result.valid) {
            isValid = false;
            let $error = $(this).siblings('.error');
            $error.text(result.message);
        }
    });

    return isValid;
}

/**
 * Validate các trường bắt buộc trong form (version với error container tùy chỉnh)
 * @param {string} formSelector - Selector của form
 * @param {string} errorContainerSelector - Selector của container chứa error (default: '.form-group')
 * @returns {boolean} true nếu hợp lệ, false nếu không
 */
function validateRequiredFields(formSelector, errorContainerSelector = '.form-group') {
    let isValid = true;
    $(formSelector + ' [required]').each(function() {
        const $field = $(this);
        // Chỉ kiểm tra các trường đang hiển thị
        if ($field.is(':visible') && !$field.val()) {
            const fieldId = $field.attr('id');
            if (fieldId) {
                const $errorContainer = $field.closest(errorContainerSelector);
                $errorContainer.find('.error').text('Trường này là bắt buộc.');
            }
            isValid = false;
        } else if ($field.is(':visible') && $field.val()) {
            // Xóa lỗi "bắt buộc" nếu đã điền, nhưng giữ lại các lỗi khác
            const $errorContainer = $field.closest(errorContainerSelector);
            if ($errorContainer.find('.error').text() === 'Trường này là bắt buộc.') {
                $errorContainer.find('.error').text('');
            }
        }
    });
    return isValid;
}

