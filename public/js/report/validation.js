let reportValidationErrors = {};

function reportShowError($field, message) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;
    reportHideError($field);
    $field.addClass('is-invalid');
    $field.closest('.col-12').append(`<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`);
    reportValidationErrors[fieldId] = true;
    reportUpdateButtonState();
}

function reportHideError($field) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;
    $field.removeClass('is-invalid');
    $field.closest('.col-12').find(`.invalid-feedback[data-error-for="${fieldId}"]`).remove();
    delete reportValidationErrors[fieldId];
    reportUpdateButtonState();
}

function reportUpdateButtonState() {
    let hasErrors = Object.keys(reportValidationErrors).length > 0;
    $('#btnSearch').prop('disabled', hasErrors);
    $('#exportExcel').toggleClass('disabled', hasErrors);
}

function validateProduct() {
    const $input = $('#product');
    const value = $input.val().trim();
    reportHideError($input);
    if (value && !/^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(\,/)]+$/.test(value)) {
        reportShowError($input, "Chỉ được nhập chữ và số.");
    } else if (value.length > 100) {
        reportShowError($input, "Tối đa 100 ký tự.");
    }
}

function validateReplacement() {
    const $input = $('#replacement');
    const value = $input.val().trim();
    reportHideError($input);
    const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ:,.;/()_+\-=%*]*$/;
    if (value && !validRegex.test(value)) {
        reportShowError($input, "Chứa ký tự không hợp lệ.");
    } else if (value.length > 100) {
        reportShowError($input, "Tối đa 100 ký tự.");
    }
}

function validateStaff() {
    const $input = $('#staff_received');
    const value = $input.val().trim();
    reportHideError($input);
    const nameRegex = /^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụüÜưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ]+$/;
    if (value && !nameRegex.test(value)) {
        reportShowError($input, "Chỉ được nhập chữ.");
    } else if (value.length > 50) {
        reportShowError($input, "Tối đa 50 ký tự.");
    }
}

function validateDates() {
    const $fromDate = $('#fromDate');
    const $toDate = $('#toDate');
    const fromDate = $fromDate.val();
    const toDate = $toDate.val();
    const today = new Date().toISOString().split('T')[0];
    reportHideError($fromDate);
    reportHideError($toDate);
    if (fromDate && toDate) {
        if (fromDate > toDate) {
            reportShowError($toDate, "'Đến ngày' phải lớn hơn hoặc bằng 'Từ ngày'.");
            return false;
        }
        if (toDate > today) {
            reportShowError($toDate, "'Đến ngày' không được lớn hơn ngày hiện tại.");
            return false;
        }
    }
    return true;
}

function runAllValidations() {
    validateProduct();
    validateReplacement();
    validateStaff();
    validateDates();
}

function initReportValidation() {
    $(document).ready(function() {
        $('#product').on('input', validateProduct);
        $('#replacement').on('input', validateReplacement);
        $('#staff_received').on('input', validateStaff);
        $('#fromDate, #toDate').on('change', validateDates);

        // Initial run
        validateDates();
        runAllValidations();

        $('form').on('submit', function(e) {
            runAllValidations();
            if (Object.keys(reportValidationErrors).length > 0) {
                e.preventDefault();
                const firstErrorId = Object.keys(reportValidationErrors)[0];
                $('#' + firstErrorId).focus();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi dữ liệu',
                    text: 'Vui lòng kiểm tra lại các trường nhập liệu.',
                });
            }
        });
    });
}


