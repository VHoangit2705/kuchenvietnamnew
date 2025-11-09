/**
 * Main form validation and submission logic for formwarranty
 * 
 * NOTE: Các hàm validate đã được di chuyển sang /js/validate_input/formwarranty.js
 * Các hàm showError, hideError đã được di chuyển sang /js/validate_input/helpers.js
 * Vui lòng đảm bảo các file sau được load trước file này:
 * - /js/common.js
 * - /js/validate_input/helpers.js
 * - /js/validate_input/common.js
 * - /js/validate_input/formwarranty.js
 */

// Validation form tạo phiếu bảo hành
let formErrors = {};

// Cập nhật trạng thái nút Hoàn tất
function updateSubmitButtonState() {
    const hasErrors = Object.keys(formErrors).length > 0;
    $('#hoantat').prop('disabled', hasErrors);
}

// Create warranty request
function createWarrantyRequest() {
    let formData = {
        product: $('#product').val(),
        serial_number: $('#serial_number').val(),
        serial_thanmay: $('#serial_thanmay').val(),
        type: $('#type').val(),
        full_name: $('#full_name').val(),
        phone_number: $('#phone_number').val(),
        province_id: $('#province').val(),
        district_id: $('#district').val(),
        ward_id: $('#ward').val(),
        address: $('#address').val(),
        branch: $('#branch').val(),
        shipment_date: $('#shipment_date').val(),
        return_date: $('#return_date').val(),
        collaborator_id: $('#collaborator_id').val(),
        collaborator_name: $('#ctv_name').val(),
        collaborator_phone: $('#ctv_phone').val(),
        collaborator_address: $('#ctv_address').val(),
        initial_fault_condition: $('#initial_fault_condition').val(),
        product_fault_condition: $('#product_fault_condition').val(),
        product_quantity_description: $('#product_quantity_description').val()
    };
    
    if (typeof OpenWaitBox === 'function') {
        OpenWaitBox();
    }
    
    $.ajax({
        url: window.warrantyCreateRoute || '',
        type: "POST",
        data: formData,
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || ''
        },
        success: function (res) {
            if (typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            if (res.success) {
                showSwalMessage('success', 'Tạo phiếu thành công!', '', {
                    timer: 2000
                }).then(() => {
                    window.location.href = `${window.warrantyTakePhotoRoute || ''}?sophieu=${res.id}`;
                });
            } else {
                showSwalMessage('error', res.message, '', {
                    timer: 2000
                });
            }
        },
        error: function (xhr) {
            if (typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            let msg = "Đã xảy ra lỗi!";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showSwalMessage('error', 'Lỗi', msg);
        }
    });
}

// Initialize form
$(document).ready(function() {
    SelectProduct();
    ClickCheckBox();
    ValidateInputDate();
    ShowCTVFiles();
    setupAddressSelects();
    setupSerialLookup();
    setupCTVPhoneLookup();
    
    // Gắn sự kiện 'input' cho các trường text và textarea
    $('#product').on('input', validateProduct);
    $('#serial_number').on('input', validateSerialNumber);
    $('#serial_thanmay').on('input', validateSerialNumberThanMay);
    $('#full_name').on('input', validateFullName);
    $('#phone_number').on('input', validatePhoneNumberFormWarranty);
    $('#address').on('input', validateAddress);
    $('#initial_fault_condition').on('input', () => validateTextarea('#initial_fault_condition'));
    $('#product_fault_condition').on('input', () => validateTextarea('#product_fault_condition'));
    $('#product_quantity_description').on('input', () => validateTextarea('#product_quantity_description'));

    // Gắn sự kiện 'change' cho các trường date và select
    $('#shipment_date, #return_date, #received_date').on('change', validateDatesFormWarranty);
    $('#type').on('change', function() {
        // Khi chọn, xóa lỗi và kiểm tra lại các trường bắt buộc
        hideError($(this));
        validateRequiredFields('#warrantyCard', '.form-group');
    });

    // Gắn sự kiện submit form
    $('#hoantat').on('click', function (e) {
        e.preventDefault();
        if (validateAllFieldsFormWarranty()) {
            createWarrantyRequest();
        }
    });
});

