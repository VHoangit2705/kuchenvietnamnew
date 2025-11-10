/**
 * Validation functions for collaborator install details page
 */

// Global validation errors object
let validationErrors = {};

/**
 * Get field ID from jQuery element (consistent across all validation functions)
 * @param {jQuery} $field - jQuery object of the field
 * @returns {string} Field ID
 */
function getFieldId($field) {
    return $field.attr('id') || $field.attr('name') || $field.closest('td').data('field') || $field.closest('td').data('agency') || '';
}

/**
 * Show validation error
 * @param {jQuery} $field - jQuery object of the field
 * @param {string} message - Error message
 */
function showError($field, message) {
    // Xác định ID định danh duy nhất cho trường
    let fieldId = getFieldId($field);
    
    if (!fieldId) {
        return; // Không thể hiển thị lỗi nếu không có fieldId
    }

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
 * Hide validation error
 * @param {jQuery} $field - jQuery object of the field
 */
function hideError($field) {
    // Xác định fieldId
    let fieldId = getFieldId($field);
    
    // Xóa lỗi hiển thị (tìm trong td chứa input)
    if ($field.hasClass('form-control')) {
        // Tìm trong td chứa input
        $field.closest('td').find('.validation-error').remove();
        // Cũng tìm trong parent nếu không tìm thấy
        $field.parent().find('.validation-error').remove();
    } else {
        $field.parent().find('.validation-error').remove();
    }
    
    // Xóa cờ lỗi nếu có fieldId - xóa tất cả các biến thể có thể có
    if (fieldId) {
        delete validationErrors[fieldId];
        // Xóa cả các biến thể khác có thể có
        const fieldIdAttr = $field.attr('id');
        const fieldNameAttr = $field.attr('name');
        if (fieldIdAttr && fieldIdAttr !== fieldId) {
            delete validationErrors[fieldIdAttr];
        }
        if (fieldNameAttr && fieldNameAttr !== fieldId && fieldNameAttr !== fieldIdAttr) {
            delete validationErrors[fieldNameAttr];
        }
    }
    
    // Cập nhật trạng thái nút
    updateSubmitButtons();
}

/**
 * Clear all validation errors for a specific field (helper function)
 * @param {jQuery} $field - jQuery object of the field
 */
function clearAllFieldErrors($field) {
    const fieldId = getFieldId($field);
    if (fieldId) {
        delete validationErrors[fieldId];
        const fieldIdAttr = $field.attr('id');
        const fieldNameAttr = $field.attr('name');
        if (fieldIdAttr) delete validationErrors[fieldIdAttr];
        if (fieldNameAttr) delete validationErrors[fieldNameAttr];
    }
    // Xóa lỗi hiển thị
    $field.closest('td').find('.validation-error').remove();
    $field.parent().find('.validation-error').remove();
    $field.siblings('.validation-error').remove();
}


/**
 * Clean up invalid validation errors (remove errors for fields that are now valid)
 */
function cleanupInvalidErrors() {
    // Kiểm tra tất cả các cờ lỗi và xóa những cờ không còn hợp lệ
    let keysToRemove = [];
    
    for (let fieldId in validationErrors) {
        let foundField = false;
        let hasVisibleError = false;
        
        // Tìm input tương ứng với fieldId - thử nhiều cách
        let $field = null;
        
        // Thử tìm theo ID
        if ($('#' + fieldId).length) {
            $field = $('#' + fieldId);
        }
        // Thử tìm theo name
        else if ($('[name="' + fieldId + '"]').length) {
            $field = $('[name="' + fieldId + '"]');
        }
        // Thử tìm theo data-field
        else if ($('[data-field="' + fieldId + '"]').length) {
            $field = $('[data-field="' + fieldId + '"]');
        }
        // Thử tìm theo data-agency
        else if ($('[data-agency="' + fieldId + '"]').length) {
            $field = $('[data-agency="' + fieldId + '"]');
        }
        // Thử tìm trong td có data-field hoặc data-agency
        else if ($('td[data-field="' + fieldId + '"]').length) {
            $field = $('td[data-field="' + fieldId + '"]').find('input, textarea');
        }
        else if ($('td[data-agency="' + fieldId + '"]').length) {
            $field = $('td[data-agency="' + fieldId + '"]').find('input, textarea');
        }
        
        if ($field && $field.length) {
            foundField = true;
            // Kiểm tra xem có lỗi hiển thị không
            hasVisibleError = $field.closest('td').find('.validation-error').length > 0 ||
                              $field.parent().find('.validation-error').length > 0 ||
                              $field.siblings('.validation-error').length > 0;
        }
        
        // Nếu không tìm thấy field hoặc không có lỗi hiển thị, xóa cờ lỗi
        if (!foundField || !hasVisibleError) {
            keysToRemove.push(fieldId);
        }
    }
    
    // Xóa các cờ lỗi không hợp lệ
    keysToRemove.forEach(key => {
        delete validationErrors[key];
    });
}

/**
 * Update submit buttons state based on validation errors
 */
function updateSubmitButtons() {
    // Dọn dẹp các cờ lỗi không hợp lệ trước
    cleanupInvalidErrors();
    
    // Kiểm tra xem có lỗi hiển thị trên giao diện không
    let visibleErrors = $('.validation-error:visible').length;
    
    // Kiểm tra xem có lỗi nào trong validationErrors không
    let hasErrors = Object.keys(validationErrors).length > 0;
    
    // Nếu có lỗi hiển thị nhưng không có trong validationErrors, xóa lỗi hiển thị
    if (visibleErrors > 0 && !hasErrors) {
        $('.validation-error').remove();
        visibleErrors = 0;
    }
    
    // Nếu có lỗi trong validationErrors nhưng không có lỗi hiển thị, xóa cờ lỗi
    if (hasErrors && visibleErrors === 0) {
        // Kiểm tra lại xem có lỗi thực sự không
        let hasRealErrors = false;
        for (let key in validationErrors) {
            let $field = $('#' + key).length ? $('#' + key) : 
                        $('[name="' + key + '"]').length ? $('[name="' + key + '"]') :
                        $('[data-field="' + key + '"]').length ? $('[data-field="' + key + '"]') :
                        $('[data-agency="' + key + '"]').length ? $('[data-agency="' + key + '"]') : null;
            
            if ($field && $field.length) {
                let hasError = $field.closest('td').find('.validation-error:visible').length > 0 ||
                              $field.parent().find('.validation-error:visible').length > 0;
                if (hasError) {
                    hasRealErrors = true;
                    break;
                }
            }
        }
        
        if (!hasRealErrors) {
            validationErrors = {};
            hasErrors = false;
        }
    }
    
    let $buttons = $("#btnUpdate, #btnComplete, #btnPay");
    
    // Nếu không có button nào, không làm gì
    if ($buttons.length === 0) {
        return;
    }

    // Quyết định trạng thái button dựa trên lỗi
    if (hasErrors || visibleErrors > 0) {
        $buttons.prop('disabled', true)
                .addClass('disabled')
                .attr('aria-disabled', 'true')
                .css('opacity', '0.65')
                .css('cursor', 'not-allowed');
    } else {
        // Đảm bảo bật button - xóa tất cả ràng buộc disabled
        $buttons.prop('disabled', false)
                .removeClass('disabled')
                .removeAttr('aria-disabled')
                .css('opacity', '1')
                .css('cursor', 'pointer')
                .css('pointer-events', 'auto');
    }
}

/**
 * Validate dynamic field (sotaikhoan, cccd, etc.)
 * @param {jQuery} $input - jQuery object of the input
 * @param {string} fieldName - Name of the field
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
            // Validate chi nhánh nếu có
            let $chinhanhInput = $td.closest('tbody').find('input[data-field="chinhanh"], input[data-agency="agency_branch"]');
            if($chinhanhInput.length) validateDynamicField($chinhanhInput, $chinhanhInput.data('field') || $chinhanhInput.data('agency'));
            break;

        case 'chinhanh':
        case 'agency_branch':
            // Tìm sotaikhoanCell trong toàn bộ <tbody>
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
            // Cho phép chữ tiếng Việt, số, dấu cách, và các ký tự .,-/
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
            let $ngaycapInput = $td.closest('tbody').find('input[data-field="ngaycap"], input[data-agency="agency_release_date"]');
            if($ngaycapInput.length) validateDynamicField($ngaycapInput, $ngaycapInput.data('field') || $ngaycapInput.data('agency'));
            break;

        case 'ngaycap':
        case 'agency_release_date':
            // Tìm cccdCell trong toàn bộ <tbody>
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
 * Validate install cost
 * @param {jQuery} $input - jQuery object of the input
 */
function validateInstallCost($input) {
    // Luôn xóa lỗi cũ trước khi validate
    hideError($input);
    
    let valueStr = $input.val().trim();
    
    // Nếu rỗng, không validate (sẽ kiểm tra khi submit)
    if (!valueStr) {
        // Đảm bảo xóa cờ lỗi nếu có
        clearAllFieldErrors($input);
        updateSubmitButtons();
        return; // Cho phép để trống, validation sẽ được kiểm tra khi submit
    }
    
    // Lấy số thô từ giá trị đã format (loại bỏ tất cả ký tự không phải số)
    // Hàm getCurrencyValue sẽ loại bỏ cả dấu chấm (.) và dấu phẩy (,)
    let rawValue = getCurrencyValue($input);
    let numValue = parseInt(rawValue, 10);

    // Kiểm tra nếu không phải số hoặc <= 0
    if (isNaN(numValue) || numValue <= 0 || rawValue === '0' || rawValue === '') {
        showError($input, "Chi phí phải là số nguyên dương.");
        return;
    }
    
    // Nếu đến đây, giá trị hợp lệ
    // Đảm bảo lỗi đã được xóa và nút được bật lại
    // Sử dụng hàm helper để xóa tất cả lỗi
    clearAllFieldErrors($input);
    
    // Đảm bảo xóa tất cả cờ lỗi liên quan (bao gồm cả các biến thể)
    const fieldId = getFieldId($input);
    const inputId = $input.attr('id');
    const inputName = $input.attr('name');
    
    // Xóa tất cả các biến thể có thể có của fieldId
    if (fieldId) {
        delete validationErrors[fieldId];
    }
    if (inputId && inputId !== fieldId) {
        delete validationErrors[inputId];
    }
    if (inputName && inputName !== fieldId && inputName !== inputId) {
        delete validationErrors[inputName];
    }
    
    // Đảm bảo xóa tất cả lỗi hiển thị
    $input.closest('td').find('.validation-error').remove();
    $input.parent().find('.validation-error').remove();
    $input.siblings('.validation-error').remove();
    
    // Cập nhật trạng thái nút ngay lập tức với delay nhỏ để đảm bảo DOM đã cập nhật
    setTimeout(function() {
        updateSubmitButtons();
    }, 10);
}

/**
 * Validate completion date
 * @param {jQuery} $input - jQuery object of the input
 * @param {string} creationDate - Creation date string (optional, will use global CREATION_DATE if not provided)
 */
function validateCompletionDate($input, creationDate) {
    hideError($input);
    let completionDateStr = $input.val();
    // Sử dụng creationDate từ tham số hoặc từ global CREATION_DATE
    const creationDateToUse = creationDate || (typeof CREATION_DATE !== 'undefined' ? CREATION_DATE : null);
    if (!completionDateStr || !creationDateToUse) {
        // Nếu không có giá trị, đảm bảo xóa lỗi và cập nhật nút
        clearAllFieldErrors($input);
        const fieldId = getFieldId($input);
        const inputId = $input.attr('id');
        const inputName = $input.attr('name');
        if (fieldId) delete validationErrors[fieldId];
        if (inputId) delete validationErrors[inputId];
        if (inputName) delete validationErrors[inputName];
        setTimeout(function() {
            updateSubmitButtons();
        }, 10);
        return; // Không có gì để so sánh
    }

    try {
        let completionDate = new Date(completionDateStr);
        let creationDateObj = new Date(creationDateToUse);
        
        // Đặt về 0 giờ để so sánh ngày
        completionDate.setHours(0, 0, 0, 0);
        creationDateObj.setHours(0, 0, 0, 0);

        if (completionDate < creationDateObj) {
            let creationDateFormatted = new Date(creationDateToUse).toLocaleDateString('vi-VN');
            showError($input, `Ngày hoàn thành không được sớm hơn ngày tạo đơn (${creationDateFormatted}).`);
        } else {
            // Nếu hợp lệ, đảm bảo xóa lỗi
            clearAllFieldErrors($input);
            const fieldId = getFieldId($input);
            const inputId = $input.attr('id');
            const inputName = $input.attr('name');
            if (fieldId) delete validationErrors[fieldId];
            if (inputId) delete validationErrors[inputId];
            if (inputName) delete validationErrors[inputName];
            $input.closest('td').find('.validation-error').remove();
            $input.parent().find('.validation-error').remove();
            setTimeout(function() {
                updateSubmitButtons();
            }, 10);
        }
    } catch (e) {
        showError($input, "Ngày không hợp lệ.");
    }
}

/**
 * Run all initial validations
 * @param {string} creationDate - Creation date string
 */
function runAllInitialValidations(creationDate) {
    if ($("#install_cost_ctv").is(":visible")) validateInstallCost($('#install_cost_ctv'));
    if ($("#successed_at_ctv").is(":visible")) validateCompletionDate($('#successed_at_ctv'), creationDate);
    if ($("#install_cost_agency").is(":visible")) validateInstallCost($('#install_cost_agency'));
    if ($("#successed_at").is(":visible")) validateCompletionDate($('#successed_at'), creationDate);
    
    updateSubmitButtons(); // Cập nhật nút bấm dựa trên cờ lỗi
}

