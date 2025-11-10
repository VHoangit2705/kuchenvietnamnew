/**
 * Validation functions for formwarranty module
 */

// Tên sản phẩm: chữ và số, max 80
function validateProduct() {
    const $input = $('#product');
    const value = $input.val();
    if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ,.\-()/]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ, số và các ký tự .,-()/");
    } else if (value.length > 80) {
        showError($input, "Tối đa 80 ký tự.");
    } else {
        hideError($input);
    }
}

// Mã seri: chữ và số, max 25
function validateSerialNumber() {
    const $input = $('#serial_number');
    const value = $input.val();
    if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ và số.");
    } else if (value.length > 25) {
        showError($input, "Tối đa 25 ký tự.");
    } else {
        hideError($input);
    }
}

// Mã seri thân máy: chữ và số, max 25
function validateSerialNumberThanMay() {
    const $input = $('#serial_thanmay');
    const value = $input.val().trim();
    if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ và số.");
    } else if (value.length > 25) {
        showError($input, "Tối đa 25 ký tự.");
    } else {
        hideError($input);
    }
}

// Họ tên: chỉ chữ, max 50
function validateFullName() {
    const $input = $('#full_name');
    const value = $input.val();
    if (value && !/^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ.");
    } else if (value.length > 50) {
        showError($input, "Tối đa 50 ký tự.");
    } else {
        hideError($input);
    }
}

// Số điện thoại: đúng 10 số
function validatePhoneNumberFormWarranty() {
    const $input = $('#phone_number');
    const value = $input.val();
    if (value && !/^\d{10}$/.test(value)) {
        showError($input, "Số điện thoại phải có đúng 10 chữ số.");
    } else {
        hideError($input);
    }
}

// Địa chỉ: chữ, số, .,- và max 150
function validateAddress() {
    const $input = $('#address');
    const value = $input.val();
    if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ,.\-\/]+$/.test(value)) {
        showError($input, "Chỉ nhập chữ, số và các ký tự .,-/");
    } else if (value.length > 150) {
        showError($input, "Tối đa 150 ký tự.");
    } else {
        hideError($input);
    }
}

// Ngày: Ngày xuất kho < Ngày tiếp nhận, Ngày hẹn trả >= Ngày tiếp nhận
function validateDatesFormWarranty() {
    const $shipmentDate = $('#shipment_date');
    const $returnDate = $('#return_date');
    const receivedDateStr = $('#received_date').val();

    const receivedDate = parseDate(receivedDateStr);
    const shipmentDate = parseDate($shipmentDate.val());
    const returnDate = parseDate($returnDate.val());

    // Validate ngày xuất kho
    if (shipmentDate && receivedDate && shipmentDate >= receivedDate) {
        showError($shipmentDate, "Ngày xuất kho phải nhỏ hơn ngày tiếp nhận.");
    } else {
        hideError($shipmentDate);
    }

    // Validate ngày hẹn trả
    if (returnDate && receivedDate && returnDate < receivedDate) {
        showError($returnDate, "Ngày hẹn trả phải lớn hơn hoặc bằng ngày tiếp nhận.");
    } else {
        hideError($returnDate);
    }
}

// Hàm Mô tả sài chung
function validateTextarea(selector) {
    const $input = $(selector);
    const value = $input.val();
    
    if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ,.\-)(\;/]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ, số và các ký tự .,-()/;");
    } else if (value.length > 150) {
        showError($input, "Tối đa 150 ký tự.");
    } else {
        hideError($input);
    }
}

// Hàm kiểm tra tổng thể khi submit
function validateAllFieldsFormWarranty() {
    validateRequiredFields('#warrantyCard', '.form-group');
    validateProduct();
    validateSerialNumber();
    validateFullName();
    validatePhoneNumberFormWarranty();
    validateAddress();
    validateDatesFormWarranty();
    validateSerialNumberThanMay();
    validateTextarea('#initial_fault_condition');
    validateTextarea('#product_fault_condition');
    validateTextarea('#product_quantity_description');

    // Nếu còn lỗi, focus vào trường lỗi đầu tiên
    if (typeof formErrors !== 'undefined' && Object.keys(formErrors).length > 0) {
        const firstErrorId = Object.keys(formErrors)[0];
        $('#' + firstErrorId).focus();
        return false;
    }
    return true;
}

