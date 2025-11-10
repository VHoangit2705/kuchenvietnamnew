/**
 * Validation functions for report/listwarranty module
 */

// Options cho report module (sử dụng Bootstrap invalid-feedback)
const reportErrorOptions = { errorContainer: '.col-12', errorSelector: '.invalid-feedback' };

// Validation errors object
let validationErrors = {};

// Hàm hiển thị lỗi cho report module
function showError($field, message) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;
    hideError($field); // Xóa lỗi cũ trước khi hiển thị lỗi mới
    // Thêm class is-invalid của Bootstrap và hiển thị thông báo
    $field.addClass('is-invalid');
    $field.closest('.col-12').append(`<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`);
    validationErrors[fieldId] = true; // Gắn cờ lỗi
    updateButtonState();
}

// Hàm ẩn lỗi cho report module
function hideError($field) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;
    $field.removeClass('is-invalid');
    $field.closest('.col-12').find(`.invalid-feedback[data-error-for="${fieldId}"]`).remove();
    delete validationErrors[fieldId]; // Bỏ cờ lỗi
    updateButtonState();
}

// Hàm cập nhật trạng thái nút "Lọc" VÀ "Xuất Excel"
function updateButtonState() {
    let hasErrors = Object.keys(validationErrors).length > 0;
    $('#btnSearch').prop('disabled', hasErrors);
    $('#exportExcel').toggleClass('disabled', hasErrors);
}

function validateProductReport() {
    const $input = $('#product');
    const value = $input.val().trim();
    hideError($input);
    if (value && !/^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(\,/)]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ và số.");
    } else if (value.length > 100) {
        showError($input, "Tối đa 100 ký tự.");
    }
}

function validateReplacement() {
    const $input = $('#replacement');
    const value = $input.val().trim();
    hideError($input);
    // Regex cho phép chữ, số và các ký tự đặc biệt được yêu cầu
    const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ:,.;/()_+\-=%*]*$/;
    if (value && !validRegex.test(value)) {
        showError($input, "Chứa ký tự không hợp lệ.");
    } else if (value.length > 100) {
        showError($input, "Tối đa 100 ký tự.");
    }
}

function validateStaff() {
    const $input = $('#staff_received');
    const value = $input.val().trim();
    hideError($input);
    // Regex cho phép chữ cái (bao gồm tiếng Việt) và khoảng trắng
    const nameRegex = /^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ]+$/;
    if (value && !nameRegex.test(value)) {
        showError($input, "Chỉ được nhập chữ.");
    } else if (value.length > 50) {
        showError($input, "Tối đa 50 ký tự.");
    }
}

function validateDatesReport() {
    const $fromDate = $('#fromDate');
    const $toDate = $('#toDate');
    const fromDate = $fromDate.val();
    const toDate = $toDate.val();
    const today = new Date().toISOString().split('T')[0];
    // Xóa lỗi cũ của cả 2 trường date trước khi kiểm tra lại
    hideError($fromDate);
    hideError($toDate);
    if (fromDate && toDate) {
        if (fromDate > toDate) {
            showError($toDate, "'Đến ngày' phải lớn hơn hoặc bằng 'Từ ngày'.");
            return false; // Có lỗi
        }
        if (toDate > today) {
            showError($toDate, "'Đến ngày' không được lớn hơn ngày hiện tại.");
            return false; // Có lỗi
        }
    }
    return true; // Không có lỗi
}

// Hàm chạy tất cả các validation
function runAllValidationsReport() {
    validateProductReport();
    validateReplacement();
    validateStaff();
    validateDatesReport();
}

