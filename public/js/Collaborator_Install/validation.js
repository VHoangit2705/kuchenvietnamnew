/**
 * File validation chung cho các hàm validation dùng chung
 */

// Lưu trữ giá trị ban đầu của các trường CTV và Đại lý
let originalCtvData = {};
let originalAgencyData = {};

// "Cờ" validation: Lưu trạng thái lỗi của các trường
let validationErrors = {};

/**
 * Hàm định dạng tiền VNĐ (1,000,000)
 */
function formatCurrency(input) {
    let value = input.val().replace(/[^0-9]/g, ''); // Chỉ giữ lại số
    if (!value) {
        input.val('');
        return;
    }
    let num = parseInt(value, 10);
    if (isNaN(num)) {
        input.val('');
        return;
    }
    input.val(num.toLocaleString('vi-VN')); // Định dạng kiểu VN
}

/**
 * Hàm lấy giá trị số thô từ trường tiền tệ (1000000)
 */
function getCurrencyValue($input) { 
    let value = $input.val() || ''; // Lấy giá trị trực tiếp từ $input
    return value.replace(/[^0-9]/g, '') || '0';
}

/**
 * Hàm hiển thị lỗi
 */
function showError($field, message) {
    // Xác định ID định danh duy nhất cho trường
    let fieldId = $field.attr('id') || $field.closest('td').data('field') || $field.closest('td').data('agency');
    if (!fieldId) fieldId = $field.attr('name'); // Dự phòng

    hideError($field); // Xóa lỗi cũ trước
    let $error = $(`<div class="text-danger mt-1 validation-error" data-error-for="${fieldId}">${message}</div>`);
    
    // Thêm lỗi vào đúng vị trí
    if ($field.hasClass('form-control')) {
        $field.closest('td').append($error);
    } else {
        $field.parent().append($error);
    }
    
    validationErrors[fieldId] = true; // Gắn cờ lỗi
    updateSubmitButtons(); // Cập nhật trạng thái nút
}

/**
 * Hàm ẩn lỗi
 */
function hideError($field) {
    let fieldId = $field.attr('id') || $field.closest('td').data('field') || $field.closest('td').data('agency');
    if (!fieldId) fieldId = $field.attr('name');

    if ($field.hasClass('form-control')) {
        $field.closest('td').find('.validation-error').remove();
    } else {
         $field.parent().find('.validation-error').remove();
    }

    delete validationErrors[fieldId]; // Bỏ cờ lỗi
    updateSubmitButtons(); // Cập nhật trạng thái nút
}

/**
 * Hàm cập nhật trạng thái các nút Submit
 */
function updateSubmitButtons() {
    // Kiểm tra xem có lỗi nào không
    let hasErrors = Object.keys(validationErrors).length > 0;
    let $buttons = $("#btnUpdate, #btnComplete, #btnPay");

    if (hasErrors) {
        $buttons.prop('disabled', true).css('opacity', '0.65').css('cursor', 'not-allowed');
    } else {
        $buttons.prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');
    }
}

/**
 * Hàm xác thực cho các trường động (sotaikhoan, cccd, v.v.)
 */
function validateDynamicField($input, fieldName) {
    if (!$input || $input.length === 0) return; // Trường không tồn tại
    
    let value = $input.val().trim();
    let $td = $input.closest('td');
    hideError($input); // Xóa lỗi cũ

    switch (fieldName) {
        case 'sotaikhoan':
        case 'agency_paynumber':
            if (value && !/^[0-9]+$/.test(value)) {
                showError($input, "Chỉ được nhập số.");
            } else if (value.length > 20) {
                showError($input, "Tối đa 20 ký tự.");
            }
            // THAY ĐỔI: Sửa logic tìm kiếm để đảm bảo tìm đúng input (nếu nó đang được edit)
            let $chinhanhInput = $td.closest('tbody').find('input[data-field="chinhanh"], input[data-agency="agency_branch"]');
            if($chinhanhInput.length) validateDynamicField($chinhanhInput, $chinhanhInput.data('field') || $chinhanhInput.data('agency'));
            break;

        case 'chinhanh':
        case 'agency_branch':
            // THAY ĐỔI: Tìm sotaikhoanCell trong toàn bộ <tbody>, không phải <tr>
            let $sotaikhoanCell = $td.closest('tbody').find('td[data-field="sotaikhoan"], td[data-agency="agency_paynumber"]');
            let sotaikhoanValue = $sotaikhoanCell.find('input').length ? $sotaikhoanCell.find('input').val().trim() : $sotaikhoanCell.find('.text-value').text().trim();

            if (!sotaikhoanValue) {
                showError($input, "Vui lòng nhập Số tài khoản trước.");
            } else if (value && !/^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY]+$/.test(value)) { 
                showError($input, "Chỉ nhập chữ tiếng Việt và dấu cách, không nhập số hoặc ký tự đặc biệt.");
            } else if (value.length > 80) {
                showError($input, "Tối đa 80 ký tự.");
            }
            break;
        case 'nganhang':
        case 'agency_bank':
            // Cho phép chữ, số, dấu cách và các ký tự (.,-/&)
            if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY.,\-\/&]+$/.test(value)) {
                showError($input, "Tên ngân hàng chỉ được chứa chữ, số, dấu cách và (.,-/&).");
            } else if (value.length > 80) {
                showError($input, "Tối đa 80 ký tự.");
            }
            break;
        case 'agency_name':
        case 'agency_address':
            // Lưu ý: Cho phép chữ tiếng Việt, số, dấu cách, và các ký tự .,-/
            if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY.,-/]+$/.test(value)) { 
                showError($input, "Chỉ nhập chữ, số, dấu cách và ký tự (.,-/).");
            } else if (value.length > 80) {
                showError($input, "Tối đa 80 ký tự.");
            }
            break;
        case 'customer_address':
            // Validation cho địa chỉ khách hàng
            // Cho phép chữ, số, dấu cách và các ký tự .,-/
            if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY.,\-\/]+$/.test(value)) {
                showError($input, "Chỉ nhập chữ, số, dấu cách và các ký tự (.,-/).");
            } else if (value.length > 150) {
                showError($input, "Tối đa 150 ký tự.");
            }
            break;

        case 'cccd':
        case 'agency_cccd':
            if (value && !/^[0-9]+$/.test(value)) {
                showError($input, "Chỉ được nhập số.");
            } else if (value && value.length !== 12) {
                showError($input, "Bắt buộc đủ 12 số.");
            }
             // Xác thực lại trường 'ngày cấp' phụ thuộc
             // THAY ĐỔI: Sửa logic tìm kiếm để đảm bảo tìm đúng input (nếu nó đang được edit)
            let $ngaycapInput = $td.closest('tbody').find('input[data-field="ngaycap"], input[data-agency="agency_release_date"]');
            if($ngaycapInput.length) validateDynamicField($ngaycapInput, $ngaycapInput.data('field') || $ngaycapInput.data('agency'));
            break;

        case 'ngaycap':
        case 'agency_release_date':
            // THAY ĐỔI: Tìm cccdCell trong toàn bộ <tbody>, không phải <tr>
            let $cccdCell = $td.closest('tbody').find('td[data-field="cccd"], td[data-agency="agency_cccd"]');
            let cccdValue = $cccdCell.find('input').length ? $cccdCell.find('input').val().trim() : $cccdCell.find('.text-value').text().trim();
            
            if (!cccdValue || cccdValue.length !== 12 || !/^[0-9]+$/.test(cccdValue)) {
                showError($input, "Vui lòng nhập CCCD (12 số) hợp lệ trước.");
            } else if (value) {
                try {
                    let today = new Date();
                    today.setHours(0, 0, 0, 0); // Đặt về nửa đêm
                    let selectedDate = new Date(value);
                    
                    if (selectedDate > today) {
                        showError($input, "Ngày cấp không được quá ngày hiện tại.");
                    }
                } catch(e) {
                    showError($input, "Ngày không hợp lệ.");
                }
            }
            break;
    }
}

/**
 * Hàm xác thực cho Chi phí lắp đặt
 */
function validateInstallCost($input) {
    hideError($input);
    let valueStr = $input.val().trim();
    
    // THAY ĐỔI: Chuyển từ getCurrencyValue($input.selector) sang getCurrencyValue($input)
    let numValue = parseInt(getCurrencyValue($input), 10); // Lấy số thô

    if (!valueStr) {
        showError($input, "Chi phí không được để trống.");
    } else if (isNaN(numValue)) { 
        // Trường hợp này gần như không xảy ra vì getCurrencyValue luôn trả về chuỗi số hoặc '0'
        showError($input, "Vui lòng nhập số hợp lệ.");
    } else if (numValue <= 0) {
        showError($input, "Chi phí phải là số nguyên dương.");
    }
}

/**
 * Hàm xác thực cho Ngày hoàn thành
 */
function validateCompletionDate($input) {
    hideError($input);
    let completionDateStr = $input.val();
    if (!completionDateStr || typeof CREATION_DATE === 'undefined' || !CREATION_DATE) return; // Không có gì để so sánh

    try {
        let completionDate = new Date(completionDateStr);
        let creationDate = new Date(CREATION_DATE);
        
        // Đặt về 0 giờ để so sánh ngày
        completionDate.setHours(0, 0, 0, 0);
        creationDate.setHours(0, 0, 0, 0);

        if (completionDate < creationDate) {
            let creationDateFormatted = new Date(CREATION_DATE).toLocaleDateString('vi-VN');
            showError($input, `Ngày hoàn thành không được sớm hơn ngày tạo đơn (${creationDateFormatted}).`);
        }
    } catch (e) {
        showError($input, "Ngày không hợp lệ.");
    }
}

/**
 * Chạy tất cả validation cho các trường input tĩnh khi tải trang
 */
function runAllInitialValidations() {
    if ($("#install_cost_ctv").is(":visible")) validateInstallCost($('#install_cost_ctv'));
    if ($("#successed_at_ctv").is(":visible")) validateCompletionDate($('#successed_at_ctv'));
    if ($("#install_cost_agency").is(":visible")) validateInstallCost($('#install_cost_agency'));
    if ($("#successed_at").is(":visible")) validateCompletionDate($('#successed_at'));
    
    updateSubmitButtons(); // Cập nhật nút bấm dựa trên cờ lỗi
}

