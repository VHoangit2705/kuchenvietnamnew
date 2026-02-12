/**
 * Validation functions cho printwarranty module
 * Bao gồm: Modal validation, Search form validation, Serial range validation
 */

// Validation modal errors storage
// Sử dụng var hoặc kiểm tra tồn tại để tránh lỗi duplicate declaration
if (typeof modalValidationErrors === 'undefined') {
    var modalValidationErrors = {};
}

// Validation form search errors storage
if (typeof validationErrors === 'undefined') {
    var validationErrors = {};
}

// Hiển thị lỗi validation cho modal
function showModalError($field, message) {
    const fieldId = $field.attr('id');
    if (!fieldId) return;
    
    hideModalError($field);

    const $errorDiv = $field.siblings('.error');
    $errorDiv.text(message);
    
    modalValidationErrors[fieldId] = true;
    updateModalSubmitButtonsState();
}

// Ẩn lỗi validation cho modal
function hideModalError($field) {
    const fieldId = $field.attr('id');
    if (!fieldId) return;

    const $errorDiv = $field.siblings('.error');
    $errorDiv.text('');

    delete modalValidationErrors[fieldId];
    updateModalSubmitButtonsState();
}

// Cập nhật trạng thái nút submit modal
function updateModalSubmitButtonsState() {
    const hasErrors = Object.keys(modalValidationErrors).length > 0;
    $('.submit-btn').prop('disabled', hasErrors);
}

// Validate tên sản phẩm trong modal
function validateModalProduct() {
    const $input = $('#product');
    const value = $input.val().trim();

    if (!value) {
        showModalError($input, "Tên sản phẩm không được để trống.");
        return false;
    }
    
    if (value.length > 100) {
        showModalError($input, "Tên sản phẩm không được vượt quá 100 ký tự.");
        return false;
    }
    hideModalError($input);
    return true;
}

// Validate số lượng trong modal
function validateModalQuantity() {
    const $input = $('#quantity');
    const value = $input.val().trim();
    if (!value) {
        showModalError($input, "Số lượng không được để trống.");
        return false;
    }
    if (!/^\d+$/.test(value)) {
        showModalError($input, "Số lượng phải là số.");
        return false;
    }
    if (parseInt(value) <= 0) {
        showModalError($input, "Số lượng phải lớn hơn 0.");
        return false;
    }
    if (value.length > 10) {
        showModalError($input, "Số lượng không được vượt quá 10 chữ số.");
        return false;
    }
    hideModalError($input);
    return true;
}

// Validate dải serial trong modal
function validateModalSerialRange() {
    const $input = $('#serial_range');
    const value = $input.val().trim();
    const validRegex = /^[A-Za-z0-9,\-]+$/;
    if (!value) {
        showModalError($input, "Dải serial không được để trống.");
        return false;
    }
    if (value.length > 1000) {
        showModalError($input, "Dải serial không được vượt quá 1000 ký tự.");
        return false;
    }
    if (!validRegex.test(value.replace(/\s/g, ''))) {
        showModalError($input, "Chỉ cho phép nhập chữ, số, dấu phẩy (,) và gạch ngang (-).");
        return false;
    }
    const rangeError = validateSerialRanges(value);
    if (rangeError) {
        showModalError($input, rangeError);
        return false;
    }
    hideModalError($input);
    return true;
}

// Validate toàn bộ form modal
function validateModalForm() {
    validateModalProduct();

    if ($('#auto_serial').is(':checked')) {
        validateModalQuantity();
        hideModalError($('#serial_range'));
        hideModalError($('#serial_file'));
    }
    if ($('#import_serial').is(':checked')) {
        validateModalSerialRange();
        hideModalError($('#quantity'));
        hideModalError($('#serial_file'));
    }
    if ($('#import_excel').is(':checked')) {
        if (!$('#serial_file')[0].files[0]) {
            showModalError($('#serial_file'), 'Vui lòng chọn file Excel.');
        } else {
            hideModalError($('#serial_file'));
        }
        hideModalError($('#quantity'));
        hideModalError($('#serial_range'));
    }
    return Object.keys(modalValidationErrors).length === 0;
}

// Setup validation cho modal
function setupModalValidation() {
    $('input[name="serial_option"]').on('change', function() {
        hideModalError($('#quantity'));
        hideModalError($('#serial_range'));
        hideModalError($('#serial_file'));
    });

    $('#product').on('input change', validateModalProduct);
    $('#quantity').on('input', validateModalQuantity);
    $('#serial_range').on('input', validateModalSerialRange);
}

// Hiển thị lỗi validation cho search form
function showError($field, message) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;

    hideError($field);

    $field.addClass('is-invalid');
    $field.closest('.col-md-4').append(`<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`);

    validationErrors[fieldId] = true;
    updateButtonState();
}

// Ẩn lỗi validation cho search form
function hideError($field) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;

    $field.removeClass('is-invalid');
    $field.closest('.col-md-4').find(`.invalid-feedback[data-error-for="${fieldId}"]`).remove();

    delete validationErrors[fieldId];
    updateButtonState();
}

// Cập nhật trạng thái nút tìm kiếm
function updateButtonState() {
    let hasErrors = Object.keys(validationErrors).length > 0;
    $('#searchCard').prop('disabled', hasErrors);
}

// Validate số phiếu
function validateSophieu() {
    const $input = $('#sophieu');
    const value = $input.val().trim();
    hideError($input);
    if (value && !/^\d+$/.test(value)) {
        showError($input, "Số phiếu chỉ được nhập số.");
    } else if (value && value.length > 10) {
        showError($input, "Số phiếu không vượt quá 10 ký tự.");
    }
}

//Validate tên sản phẩm trong search form
function validateTensp() {
    const $input = $('#tensp');
    const value = $input.val().trim();
    const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(,+/)]+$/;
    hideError($input);
    if (value && !validRegex.test(value)) {
        showError($input, "Tên sản phẩm chỉ nhập chữ và số và các ký tự (,+/)"); 
    } else if (value.length > 100) {
        showError($input, "Tên sản phẩm không vượt quá 100 ký tự.");
    }
}

// Validate ngày tháng
function validateDates() {
    const $fromDate = $('#tungay');
    const $toDate = $('#denngay');
    const fromDate = $fromDate.val();
    const toDate = $toDate.val();

    hideError($fromDate);
    hideError($toDate);

    if (fromDate && toDate && fromDate > toDate) {
        showError($toDate, "'Đến ngày' phải lớn hơn hoặc bằng 'Từ ngày'.");
    }
}

// Setup validation cho search form
function setupSearchValidation() {
    $('#sophieu').on('input', validateSophieu);
    $('#tungay, #denngay').on('change', validateDates);

    validateSophieu();
    validateTensp();
    validateDates();
}

// Validate search form trước khi submit
function validateSearchForm() {
    validateSophieu();
    validateTensp();
    validateDates();
    return Object.keys(validationErrors).length === 0;
}


// Validate dải serial
function validateSerialRanges(serialInput) {
    const cleanedInput = serialInput.toUpperCase().replace(/\n/g, ',').trim();
    const parts = cleanedInput.split(',').map(s => s.trim()).filter(s => s);

    for (let range of parts) {
        if (range.includes('-')) {
            const [start, end] = range.split('-').map(s => s.trim());

            if (start.length !== end.length) {
                return `Dải "${range}" không hợp lệ`;
            }

            const matchStart = start.match(/^([A-Z]*)(\d+)$/);
            const matchEnd = end.match(/^([A-Z]*)(\d+)$/);

            if (!matchStart || !matchEnd) {
                return `Dải "${range}" không hợp lệ`;
            }

            const prefixStart = matchStart[1];
            const numberStart = matchStart[2];
            const prefixEnd = matchEnd[1];
            const numberEnd = matchEnd[2];

            if (prefixStart !== prefixEnd && prefixEnd !== '') {
                return `Dải "${range}" không hợp lệ`;
            }

            if (parseInt(numberEnd) < parseInt(numberStart)) {
                return `Dải "${range}" không hợp lệ - số kết thúc nhỏ hơn số bắt đầu`;
            }
        }
    }
    return null;
}

