/**
 * Validation functions for report/listwarranty module
 */

// Options cho report module (sử dụng Bootstrap invalid-feedback)
const reportErrorOptions = { errorContainer: '.col-12', errorSelector: '.invalid-feedback' };

function validateProductReport() {
    const $input = $('#product');
    const value = $input.val();
    hideError($input, reportErrorOptions);
    if (value && !/^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(\,/)]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ và số.", reportErrorOptions);
    } else if (value.length > 80) {
        showError($input, "Tối đa 80 ký tự.", reportErrorOptions);
    }
}

function validateReplacement() {
    const $input = $('#replacement');
    const value = $input.val();
    hideError($input, reportErrorOptions);
    // Regex cho phép chữ, số và các ký tự đặc biệt được yêu cầu
    const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ:,.;/()_+\-=%*]*$/;
    if (value && !validRegex.test(value)) {
        showError($input, "Chứa ký tự không hợp lệ.", reportErrorOptions);
    } else if (value.length > 80) {
        showError($input, "Tối đa 80 ký tự.", reportErrorOptions);
    }
}

function validateStaff() {
    const $input = $('#staff_received');
    const value = $input.val();
    hideError($input, reportErrorOptions);
    // Regex cho phép chữ cái (bao gồm tiếng Việt) và khoảng trắng
    const nameRegex = /^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ]+$/;
    if (value && !nameRegex.test(value)) {
        showError($input, "Chỉ được nhập chữ.", reportErrorOptions);
    } else if (value.length > 50) {
        showError($input, "Tối đa 50 ký tự.", reportErrorOptions);
    }
}

function validateDatesReport() {
    const $fromDate = $('#fromDate');
    const $toDate = $('#toDate');
    const fromDate = $fromDate.val();
    const toDate = $toDate.val();
    const today = new Date().toISOString().split('T')[0];
    // Xóa lỗi cũ của cả 2 trường date trước khi kiểm tra lại
    hideError($fromDate, reportErrorOptions);
    hideError($toDate, reportErrorOptions);
    if (fromDate && toDate) {
        if (fromDate > toDate) {
            showError($toDate, "'Đến ngày' phải lớn hơn hoặc bằng 'Từ ngày'.", reportErrorOptions);
            return false; // Có lỗi
        }
        if (toDate > today) {
            showError($toDate, "'Đến ngày' không được lớn hơn ngày hiện tại.", reportErrorOptions);
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

