// validation form kiểm tra bảo hành
let formErrors = {};

function showError($field, message) {
    const fieldId = $field.attr('id');
    if (!fieldId) return;
    hideError($field);
    $field.closest('.card-body').find('.error').text(message);
    formErrors[fieldId] = true;
    updateButtonState();
}

function hideError($field) {
    const fieldId = $field.attr('id');
    if (!fieldId) return;
    $field.closest('.card-body').find('.error').text('');
    delete formErrors[fieldId];
    updateButtonState();
}

function updateButtonState() {
    const hasErrors = Object.keys(formErrors).length > 0;
    $('#btn-check').prop('disabled', hasErrors);
}

function validateSerial() {
    const $input = $('#serial_number');
    const value = $input.val().trim();

    if (!value) {
        showError($input, "Vui lòng nhập mã tem bảo hành.");
        return false;
    }
    if (!/^[a-zA-Z0-9]+$/.test(value)) {
        showError($input, "Chỉ được nhập chữ và số.");
        return false;
    }
    if (value.length > 25) {
        showError($input, "Tối đa 25 ký tự.");
        return false;
    }

    hideError($input);
    return true;
}

$(document).ready(function() {
    $('#serial_number').on('input', validateSerial);

    $('#btn-check').click(function(e) {
        e.preventDefault();
        if (!validateSerial()) return;
        OpenWaitBox();
        $.ajax({
            url: (window.warrantyFindRoute || ''),
            method: 'POST',
            data: {
                serial_number: $('#serial_number').val().trim(),
                _token: (window.csrfToken || '')
            },
            success: function(response) {
                CloseWaitBox();
                if (!response.success) {
                    Swal.fire({
                        icon: 'error',
                        title: response.message,
                        timer: 2000,
                    });
                    $('#customer-info').html('').fadeOut(150);
                }
                $('#customer-info').html(response.view).fadeIn(200);
            },
            error: function(xhr) {
                CloseWaitBox();
                Swal.fire({
                    icon: 'error',
                    title: "Lỗi Server",
                    timer: 2000,
                });
            }
        });
    });
});


