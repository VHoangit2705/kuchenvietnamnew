/**
 * Validation cho form tìm kiếm
 */

window.checkFormValidity = function() {
    // 1. Check tất cả input có class 'is-invalid' bên trong form
    const hasInputErrors = $('#searchForm .is-invalid').length > 0;

    // 2. Check logic ngày tháng (vì nó phức tạp hơn)
    const fromDate = $('#tungay').val();
    const toDate = $('#denngay').val();
    const today = new Date().toISOString().split('T')[0];
    let hasDateErrors = false;

    // Yêu cầu phải nhập cả hai ngày
    if ((fromDate && !toDate) || (!fromDate && toDate)) {
        hasDateErrors = true; // Lỗi thiếu một trong hai ngày
    }
    // Kiểm tra logic khi có cả hai ngày
    if (fromDate && toDate && fromDate > toDate) {
        hasDateErrors = true; // Lỗi ngược ngày
    }
    if (toDate && toDate > today) {
        hasDateErrors = true; // Lỗi ngày tương lai
    }
    if (fromDate && fromDate > today) {
        hasDateErrors = true; // Lỗi ngày tương lai
    }
    // Kiểm tra nếu có class is-invalid trên các input ngày
    if ($('#tungay').hasClass('is-invalid') || $('#denngay').hasClass('is-invalid')) {
        hasDateErrors = true;
    }

    // 3. Vô hiệu hóa nút nếu có BẤT KỲ lỗi nào
    $('#btnSearch').prop('disabled', hasInputErrors || hasDateErrors);
};

// Xử lý validation mã đơn hàng
function initMadonValidation() {
    const madonInput = $('#madon');
    const maxLength = 25;

    madonInput.on('input', function() {
        let value = $(this).val();
        let sanitizedValue = value.replace(/[^a-zA-Z0-9]/g, '');

        let hasInvalidChars = (value !== sanitizedValue);
        let isTooLong = (sanitizedValue.length >= maxLength);

        if (hasInvalidChars) {
            $(this).val(sanitizedValue);
            value = sanitizedValue;
            isTooLong = (value.length >= maxLength);
        }
        if (hasInvalidChars || isTooLong) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        checkFormValidity();
    });
}

// Xử lý valiation ngày tháng
function validateDates() {
    const $tungay = $('#tungay');
    const $denngay = $('#denngay');
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

    // GỌI HÀM CHECK TỔNG THỂ
    checkFormValidity();
    return isValid;
}

// Hàm Validate nhập tên sản phẩm
function validateProductsName(inputId, maxLength) {
    const inputField = $('#' + inputId);
    inputField.on('input', function() {
        let value = $(this).val();
        // Xóa ký tự không phải chữ/số
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
        checkFormValidity();
    });
}

// Hàm Validate chỉ cho phép Chữ cái & Số (Và giới hạn độ dài)
function validateAlphaNumeric(inputId, maxLength) {
    const inputField = $('#' + inputId);
    inputField.on('input', function() {
        let value = $(this).val();
        // Xóa ký tự không phải chữ/số
        let sanitizedValue = value.replace(/[^a-zA-Z0-9]/g, '');
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
        checkFormValidity();
    });
}

// Hàm Validate chỉ cho phép Chữ cái & Khoảng trắng (Và giới hạn độ dài)
function validateAlphaSpace(inputId, maxLength) {
    const inputField = $('#' + inputId);
    inputField.on('input', function() {
        let value = $(this).val();
        let sanitizedValue = value.replace(/[^\p{L}\s]/gu, '');

        if (sanitizedValue.length > maxLength) {
            sanitizedValue = sanitizedValue.substring(0, maxLength);
        }

        let hasError = (value !== sanitizedValue && value !== '');
        // --- SỬA ĐIỀU KIỆN NÀY ---
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
        checkFormValidity();
    });
}

// Hàm Validate chỉ cho phép Số (Và giới hạn độ dài)
function validateNumeric(inputId, maxLength) {
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
        checkFormValidity();
    });
}

// Khởi tạo tất cả validation
function initFormValidations() {
    initMadonValidation();
    validateAlphaNumeric('madon', 25);
    validateProductsName('sanpham', 50);
    validateAlphaSpace('customer_name', 80);
    validateNumeric('customer_phone', 11);
    validateAlphaSpace('agency_name', 100);
    validateNumeric('agency_phone', 11);

    // Gắn event listener cho input date để validate khi thay đổi
    $('#tungay, #denngay').on('change', function() {
        validateDates();
    });
}

// Hàm kiêm tra và xử lý khi submit form
function validateFormFields() {
    let isValid = true;

    // Helper function to check a single field
    function checkField(inputId, regex, maxLength, allowOnlyDigits = false) {
        const inputField = $('#' + inputId);
        let value = inputField.val();
        let sanitizedValue;
        let currentValid = true;

        if (allowOnlyDigits) {
            sanitizedValue = value.replace(/[^0-9]/g, '');
        } else {
            if (inputId === 'madon') {
                sanitizedValue = value.replace(/[^a-zA-Z0-9]/g, '');
            } else {
                sanitizedValue = value.replace(/[^\p{L}\s]/gu, '');
            }
        }

        // Kiểm tra ký tự không hợp lệ
        if (value !== sanitizedValue && value !== '') {
            // Không set isValid = false ngay, chỉ đánh dấu để thêm class
            currentValid = false;
            // Cập nhật giá trị ngay lập tức để kiểm tra độ dài chính xác
            inputField.val(sanitizedValue);
            value = sanitizedValue;
        }

        // Kiểm tra độ dài
        if (value.length > maxLength) {
            currentValid = false;
            // Cắt bớt nếu cần (dù maxlength đã làm)
            inputField.val(value.substring(0, maxLength));
        }

        // Thêm/xóa class is-invalid dựa trên currentValid
        if (!currentValid) {
            inputField.addClass('is-invalid');
            isValid = false; // Nếu có BẤT KỲ lỗi nào, toàn bộ form là không hợp lệ
        } else {
            inputField.removeClass('is-invalid');
        }
    }

    // Kiểm tra từng trường khi submit
    checkField('madon', /[^a-zA-Z0-9]/g, 25);
    checkField('customer_name', /[^\p{L}\s]/gu, 80);
    checkField('customer_phone', /[^0-9]/g, 11, true);
    checkField('agency_name', /[^\p{L}\s]/gu, 80);
    checkField('agency_phone', /[^0-9]/g, 11, true);

    return isValid; // Trả về true nếu tất cả hợp lệ, false nếu có lỗi
}

