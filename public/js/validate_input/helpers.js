/**
 * Helper functions for validation error display
 * Các hàm hỗ trợ hiển thị lỗi validation
 */

/**
 * Hiển thị lỗi validation
 * @param {jQuery} $field - jQuery object của field
 * @param {string} message - Thông báo lỗi
 * @param {Object} options - Tùy chọn
 * @param {string} options.errorContainer - Selector của container chứa error (default: '.form-group' hoặc '.card-body')
 * @param {string} options.errorSelector - Selector của element hiển thị error (default: '.error')
 */
function showError($field, message, options = {}) {
    const fieldId = $field.attr('id');
    if (!fieldId) return;
    
    const errorContainer = options.errorContainer || '.form-group';
    const errorSelector = options.errorSelector || '.error';
    
    // Tìm container chứa error
    let $errorContainer = $field.closest(errorContainer);
    
    // Nếu không tìm thấy, thử tìm trong .card-body
    if ($errorContainer.length === 0) {
        $errorContainer = $field.closest('.card-body');
    }
    
    // Nếu vẫn không tìm thấy, thử tìm trong .col-12
    if ($errorContainer.length === 0) {
        $errorContainer = $field.closest('.col-12');
    }
    
    // Nếu vẫn không tìm thấy, sử dụng parent
    if ($errorContainer.length === 0) {
        $errorContainer = $field.parent();
    }
    
    hideError($field, options);
    
    // Tìm element hiển thị error
    let $errorElement = $errorContainer.find(errorSelector);
    
    // Nếu không tìm thấy, tạo mới
    if ($errorElement.length === 0) {
        if (options.errorSelector === '.invalid-feedback') {
            $errorElement = $('<div>').addClass('invalid-feedback d-block').attr('data-error-for', fieldId);
            $errorContainer.append($errorElement);
        } else {
            $errorElement = $('<div>').addClass('error');
            $errorContainer.append($errorElement);
        }
    }
    
    // Hiển thị lỗi
    if (options.errorSelector === '.invalid-feedback') {
        $field.addClass('is-invalid');
        $errorElement.text(message);
    } else {
        $errorElement.text(message);
    }
    
    // Cập nhật formErrors nếu có
    if (typeof formErrors !== 'undefined') {
        formErrors[fieldId] = true;
        if (typeof updateSubmitButtonState === 'function') {
            updateSubmitButtonState();
        } else if (typeof updateButtonState === 'function') {
            updateButtonState();
        }
    }
    
    // Cập nhật validationErrors nếu có
    if (typeof validationErrors !== 'undefined') {
        validationErrors[fieldId] = true;
        if (typeof updateButtonState === 'function') {
            updateButtonState();
        }
    }
}

/**
 * Ẩn lỗi validation
 * @param {jQuery} $field - jQuery object của field
 * @param {Object} options - Tùy chọn
 * @param {string} options.errorContainer - Selector của container chứa error
 * @param {string} options.errorSelector - Selector của element hiển thị error
 */
function hideError($field, options = {}) {
    const fieldId = $field.attr('id');
    if (!fieldId) return;
    
    const errorContainer = options.errorContainer || '.form-group';
    const errorSelector = options.errorSelector || '.error';
    
    // Tìm container chứa error
    let $errorContainer = $field.closest(errorContainer);
    
    // Nếu không tìm thấy, thử tìm trong .card-body
    if ($errorContainer.length === 0) {
        $errorContainer = $field.closest('.card-body');
    }
    
    // Nếu vẫn không tìm thấy, thử tìm trong .col-12
    if ($errorContainer.length === 0) {
        $errorContainer = $field.closest('.col-12');
    }
    
    // Nếu vẫn không tìm thấy, sử dụng parent
    if ($errorContainer.length === 0) {
        $errorContainer = $field.parent();
    }
    
    // Xóa class is-invalid
    $field.removeClass('is-invalid');
    
    // Xóa error element
    if (errorSelector === '.invalid-feedback') {
        $errorContainer.find(`${errorSelector}[data-error-for="${fieldId}"]`).remove();
    } else {
        $errorContainer.find(errorSelector).text('');
    }
    
    // Cập nhật formErrors nếu có
    if (typeof formErrors !== 'undefined') {
        delete formErrors[fieldId];
        if (typeof updateSubmitButtonState === 'function') {
            updateSubmitButtonState();
        } else if (typeof updateButtonState === 'function') {
            updateButtonState();
        }
    }
    
    // Cập nhật validationErrors nếu có
    if (typeof validationErrors !== 'undefined') {
        delete validationErrors[fieldId];
        if (typeof updateButtonState === 'function') {
            updateButtonState();
        }
    }
}

