/**
 * Validation functions for printwarranty module
 */

/**
 * Validate form for printwarranty
 * @returns {boolean} true nếu hợp lệ, false nếu không
 */
function validateFormPrintWarranty() {
    const brand = window.brand || "";
    let productInput = $("#product").val().trim().toLowerCase();
    let productId = $("#product_id").val();
    let quantityInput = parseInt($("#quantity").val());

    $(".error").text("");

    if (!productInput) {
        $(".product_error").text("Sản phẩm không được để trống.");
        $("#product").focus();
        return false;
    }

    if (!productId) {
        $(".product_error").text("Vui lòng chọn sản phẩm từ danh sách gợi ý.");
        $("#product").focus();
        return false;
    }

    const productList = window.productList || [];
    let isValidProduct = productList.some(
        (p) =>
            p.product_name
                .trim()
                .replace(/\r?\n|\r/g, "")
                .toLowerCase() === productInput.replace(/\r?\n|\r/g, "") &&
            p.id == productId
    );

    if (!isValidProduct) {
        $(".product_error").text(
            "Sản phẩm không hợp lệ. Vui lòng chọn lại từ danh sách gợi ý."
        );
        $("#product").focus();
        return false;
    }

    if ($("#auto_serial").is(":checked")) {
        let quantityInput = parseInt($("#quantity").val());
        if (!quantityInput || quantityInput <= 0) {
            $(".quantity_error").text("Số lượng phải lơn hơn 0.");
            $("#quantity").focus();
            return false;
        }
    }
    if ($("#import_serial").is(":checked")) {
        let serial_range = $("#serial_range").val().trim();
        let error = validateSerialRanges(serial_range);
        let isValid = /^[A-Za-z0-9,\-\s]+$/.test(serial_range);
        if (!isValid) {
            $(".serial_range_error").text(
                "Chỉ được nhập chữ, số, dấu phẩy (,) và dấu gạch ngang (-)."
            );
            $("#serial_range").focus();
            return false;
        }

        if (error) {
            $(".serial_range_error").text(error);
            $("#serial_range").focus();
            return false;
        }
    }
    if ($("#import_excel").is(":checked")) {
        let file = $("#serial_file")[0].files[0];
        if (!file) {
            $(".serial_file_error").text("File không được để trống.");
            return false;
        }
    }

    return true;
}

/**
 * Validate serial ranges
 * @param {string} serialInput - Serial input string
 * @returns {string|null} Error message hoặc null nếu hợp lệ
 */
function validateSerialRanges(serialInput) {
    const cleanedInput = serialInput.toUpperCase().replace(/\n/g, ",").trim();
    const parts = cleanedInput
        .split(",")
        .map((s) => s.trim())
        .filter((s) => s);
    const allSerials = [];
    const duplicates = [];

    for (let range of parts) {
        if (range.includes("-")) {
            const [start, end] = range.split("-").map((s) => s.trim());

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

            if (prefixStart !== prefixEnd && prefixEnd !== "") {
                return `Dải "${range}" không hợp lệ`;
            }

            if (parseInt(numberEnd) < parseInt(numberStart)) {
                return `Dải "${range}" không hợp lệ - số kết thúc nhỏ hơn số bắt đầu`;
            }

            // Tạo tất cả các số sê-ri trong phạm vi và kiểm tra các bản sao
            const length = numberStart.length;
            for (let i = parseInt(numberStart); i <= parseInt(numberEnd); i++) {
                const serial = prefixStart + i.toString().padStart(length, "0");
                if (allSerials.includes(serial)) {
                    duplicates.push(serial);
                } else {
                    allSerials.push(serial);
                }
            }
        } else {
            // Serial đơn
            if (allSerials.includes(range)) {
                duplicates.push(range);
            } else {
                allSerials.push(range);
            }
        }
    }

    // Kiểm tra các serial trùng lặp
    if (duplicates.length > 0) {
        return `Serial trùng lặp: ${duplicates.join(", ")}`;
    }

    return null;
}

/**
 * Validation functions for modal form
 */
let modalValidationErrors = {};

function showModalError($field, message) {
    const fieldId = $field.attr('id');
    if (!fieldId) return;
    
    hideModalError($field);

    const $errorDiv = $field.siblings('.error');
    $errorDiv.text(message);
    
    modalValidationErrors[fieldId] = true;
    updateModalSubmitButtonsState();
}

function hideModalError($field) {
    const fieldId = $field.attr('id');
    if (!fieldId) return;

    const $errorDiv = $field.siblings('.error');
    $errorDiv.text('');

    delete modalValidationErrors[fieldId];
    updateModalSubmitButtonsState();
}

function updateModalSubmitButtonsState() {
    const hasErrors = Object.keys(modalValidationErrors).length > 0;
    $('.submit-btn').prop('disabled', hasErrors);
}

function validateModalProduct() {
    const $input = $('#product');
    const value = $input.val().trim();
    const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(\,+/)]+$/;

    if (!value) {
        showModalError($input, "Tên sản phẩm không được để trống.");
        return false;
    }
    if (!validRegex.test(value)) {
        showModalError($input, "Tên sản phẩm chứa ký tự không hợp lệ.");
        return false;
    }
    if (value.length > 100) {
        showModalError($input, "Tên sản phẩm không được vượt quá 100 ký tự.");
        return false;
    }
    hideModalError($input);
    return true;
}

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

function validateModalSerialRange() {
    const $input = $('#serial_range');
    const value = $input.val().trim();
    const validRegex = /^[A-Za-z0-9,\-]+$/;
    if (!value) {
        showModalError($input, "Dải serial không được để trống.");
        return false;
    }
    if (value.length > 50) {
        showModalError($input, "Dải serial không được vượt quá 50 ký tự.");
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

function validateModalForm() {
    // Luôn chạy validate sản phẩm
    validateModalProduct();

    if ($('#auto_serial').is(':checked')) {
        // Chỉ validate số lượng
        validateModalQuantity();
        // Xóa lỗi của các trường khác nếu có
        hideModalError($('#serial_range'));
        hideModalError($('#serial_file'));
    }
    if ($('#import_serial').is(':checked')) {
        // Chỉ validate dải serial
        validateModalSerialRange();
        // Xóa lỗi của các trường khác nếu có
        hideModalError($('#quantity'));
        hideModalError($('#serial_file'));
    }
    if ($('#import_excel').is(':checked')) {
        // Chỉ validate file
        if (!$('#serial_file')[0].files[0]) {
            showModalError($('#serial_file'), 'Vui lòng chọn file Excel.');
        } else {
            hideModalError($('#serial_file'));
        }
        // Xóa lỗi của các trường khác nếu có
        hideModalError($('#quantity'));
        hideModalError($('#serial_range'));
    }
    // Trả về kết quả dựa trên cờ lỗi
    return Object.keys(modalValidationErrors).length === 0;
}

function setupModalValidation() {
    // Khi thay đổi lựa chọn radio, xóa lỗi của các trường không liên quan
    $('input[name="serial_option"]').on('change', function() {
        hideModalError($('#quantity'));
        hideModalError($('#serial_range'));
        hideModalError($('#serial_file'));
    });

    $('#product').on('input change', validateModalProduct);
    $('#quantity').on('input', validateModalQuantity);
    $('#serial_range').on('input', validateModalSerialRange);
}

/**
 * Validation functions for search form
 */
let validationErrors = {};

function showError($field, message) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;

    hideError($field); // Xóa lỗi cũ trước khi hiển thị lỗi mới

    // Thêm class is-invalid của Bootstrap và hiển thị thông báo
    $field.addClass('is-invalid');
    $field.closest('.col-md-4').append(`<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`);

    validationErrors[fieldId] = true; // Gắn cờ lỗi
    updateButtonState();
}

function hideError($field) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;

    $field.removeClass('is-invalid');
    $field.closest('.col-md-4').find(`.invalid-feedback[data-error-for="${fieldId}"]`).remove();

    delete validationErrors[fieldId]; // Bỏ cờ lỗi
    updateButtonState();
}

function updateButtonState() {
    let hasErrors = Object.keys(validationErrors).length > 0;
    $('#searchCard').prop('disabled', hasErrors);
}

// Validate số phiếu: chỉ số, max 10 ký tự
function validateSophieu() {
    const $input = $('#sophieu');
    const value = $input.val().trim();
    hideError($input); // Luôn xóa lỗi cũ khi validate lại
    if (value && !/^\d+$/.test(value)) {
        showError($input, "Số phiếu chỉ được nhập số.");
    } else if (value && value.length > 10) {
        showError($input, "Số phiếu không vượt quá 10 ký tự.");
    }
}

// Validate tên sản phẩm: chữ, số, và các ký tự ()-
function validateTensp() {
    const $input = $('#tensp');
    const value = $input.val().trim();
    const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(,+/)]+$/;
    hideError($input);
    if (value && !validRegex.test(value)) {
        showError($input, "Tên sản phẩm chỉ nhập chữ và số và các ký tự (,+/)");
    }else if (value.length > 100) {
        showError($input, "Tên sản phẩm không vượt quá 100 ký tự.");
    }
}

// Validate ngày: ngày sau không được nhỏ hơn ngày trước
function validateDates() {
    const $fromDate = $('#tungay');
    const $toDate = $('#denngay');
    const fromDate = $fromDate.val();
    const toDate = $toDate.val();

    // Xóa lỗi cũ của cả 2 trường date
    hideError($fromDate);
    hideError($toDate);

    if (fromDate && toDate && fromDate > toDate) {
        showError($toDate, "'Đến ngày' phải lớn hơn hoặc bằng 'Từ ngày'.");
    }
}

