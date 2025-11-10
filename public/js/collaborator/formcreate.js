// Biến toàn cục để theo dõi trạng thái lỗi của form
let formValidationErrors = {};

// Hàm hiển thị lỗi
function showFormError($field, message) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;
    hideFormError($field); // Xóa lỗi cũ trước khi hiển thị lỗi mới
    // Tìm thẻ div lỗi tương ứng và hiển thị message
    let $errorDiv = $field.siblings('.error');
    $errorDiv.text(message);
    $field.css('border-color', 'red');
    // Đánh dấu trường này đang có lỗi
    formValidationErrors[fieldId] = true;
    updateSubmitButtonState();
}

// Hàm ẩn lỗi
function hideFormError($field) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;
    // Xóa message lỗi và reset style
    $field.siblings('.error').text('');
    $field.css('border-color', '');
    // Xóa đánh dấu lỗi của trường này
    delete formValidationErrors[fieldId];
    updateSubmitButtonState();
}

// Hàm cập nhật trạng thái nút "Hoàn tất"
function updateSubmitButtonState() {
    let hasErrors = Object.keys(formValidationErrors).length > 0;
    $("#hoantat").prop('disabled', hasErrors);
}

// Hàm validate cho Họ Tên (sử dụng hàm từ validate_input/collaborator.js)
function validateFullNameForm() {
    const $input = $('#full_nameForm');
    const name = $input.val();
    const result = validateName(name, true);
    
    if (result.isValid) {
        hideFormError($input);
    } else {
        showFormError($input, result.message);
    }
}

// Hàm validate cho Số Điện Thoại (sử dụng hàm từ validate_input/collaborator.js)
function validatePhoneForm() {
    const $input = $('#phoneForm');
    const phone = $input.val();
    const result = validatePhone(phone, true);
    
    if (result.isValid) {
        hideFormError($input);
    } else {
        showFormError($input, result.message);
    }
}

// Hàm validate cho Địa chỉ (sử dụng hàm từ validate_input/collaborator.js)
function validateAddressForm() {
    const $input = $('#address');
    const address = $input.val();
    const result = validateAddress(address, true);
    
    if (result.isValid) {
        hideFormError($input);
    } else {
        showFormError($input, result.message);
    }
}

// Hàm validate cho các trường select bắt buộc
function validateSelectFields() {
    $('#formCreateCollaborator select[required]').each(function() {
        const $field = $(this);
        
        // Nếu giá trị là rỗng (chưa chọn)
        if (!$field.val()) {
            showFormError($field, "Trường này là bắt buộc.");
        } else {
            hideFormError($field);
        }
    });
}

$(document).ready(function() {
    // Lấy các route URLs từ data attributes
    const routes = {
        getDistrict: $('#formCreateCollaborator').data('route-getdistrict') || '',
        getWard: $('#formCreateCollaborator').data('route-getward') || '',
        create: $('#formCreateCollaborator').data('route-create') || '',
        getList: $('#formCreateCollaborator').data('route-getlist') || ''
    };

    // Load danh sách ngân hàng khi modal được mở
    $('#addCollaboratorModal').on('shown.bs.modal', function() {
        loadBanks('bank_nameForm', 'Chọn ngân hàng');
    });

    // Cập nhật logo khi người dùng nhập hoặc chọn từ datalist
    $('#bank_nameForm').on('input change', function() {
        updateBankLogoForInput($(this));
    });

    $('#addCollaboratorModal').on('hidden.bs.modal', function() {
        const $form = $('#formCreateCollaborator');
        $form.find('input[type="text"], input[type="date"], input[type="number"], textarea').val('');
        $form.find('select').prop('selectedIndex', 0);
        $form.find('.error').text('');
        // Reset lại border và trạng thái lỗi
        $form.find('.form-control').css('border-color', '');
        formValidationErrors = {};
        updateSubmitButtonState();
    });

    // Load combobox quận huyện (sử dụng hàm chung từ common.js)
    $('#provinceForm').on('change', function() {
        let provinceId = $(this).val();
        if (provinceId && routes.getDistrict) {
            loadDistricts(provinceId, routes.getDistrict, 'districtForm', 'Quận/Huyện', '', function() {
                // Sau khi load xong quận/huyện, validate lại form
                validateSelectFields();
            });
        }
    });

    // Load combobox xã phường (sử dụng hàm chung từ common.js)
    $('#districtForm').on('change', function() {
        let districtId = $(this).val();
        if (districtId && routes.getWard) {
            loadWards(districtId, routes.getWard, 'wardForm', 'Xã/Phường', '', function() {
                // Sau khi load xong xã/phường, validate lại form
                validateSelectFields();
            });
        }
    });

    $('#hoantat').on('click', function(e) {
        e.preventDefault();

        // 1. Chạy tất cả các hàm validation một lần cuối
        validateFullNameForm();
        validatePhoneForm();
        validateAddressForm();
        validateSelectFields(); // Gọi hàm mới

        // 2. Chỉ cần kiểm tra đối tượng lỗi
        if (Object.keys(formValidationErrors).length === 0) {
            // KHÔNG CÒN LỖI -> Gửi AJAX
            let bankName = $('#bank_nameForm').val().trim();
            
            // Tìm shortName của ngân hàng để lưu (ưu tiên shortName)
            if (bankName && banksList.length > 0) {
                const foundBank = banksList.find(function(bank) {
                    const bankNameLower = bankName.toLowerCase();
                    return (bank.shortName && bank.shortName.toLowerCase() === bankNameLower) ||
                           (bank.name && bank.name.toLowerCase() === bankNameLower) ||
                           (bank.code && bank.code.toLowerCase() === bankNameLower);
                });
                if (foundBank) {
                    // Ưu tiên lưu shortName, nếu không có thì lưu name, nếu không có thì lưu code
                    bankName = foundBank.shortName || foundBank.name || foundBank.code || bankName;
                }
            }
            
            // Đảm bảo bankName không vượt quá 255 ký tự và loại bỏ ký tự đặc biệt có thể gây lỗi
            if (bankName) {
                // Giới hạn độ dài
                if (bankName.length > 255) {
                    bankName = bankName.substring(0, 255);
                }
                // Loại bỏ các ký tự null và control characters
                bankName = bankName.replace(/\0/g, '').replace(/[\x00-\x1F\x7F]/g, '');
            }
            
            const data = {
                id: $('#id').val(),
                full_name: $('#full_nameForm').val().trim(),
                phone: $('#phoneForm').val().trim(),
                province_id: $('#provinceForm').val(),
                province: $('#provinceForm option:selected').text(),
                district_id: $('#districtForm').val(),
                district: $('#districtForm option:selected').text(),
                ward_id: $('#wardForm').val(),
                ward: $('#wardForm option:selected').text(),
                address: $('#address').val().trim(),
                bank_name: bankName,
                chinhanh: $('#bank_branchForm').val().trim(),
                sotaikhoan: $('#bank_accountForm').val().trim()
            };
            $.ajax({
                url: routes.create,
                type: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Notification('success', response.message, 1500, false)
                    $('#addCollaboratorModal').modal('hide');
                    if (routes.getList) {
                        $.get(routes.getList, function(html) {
                            $('#tabContent').html(html);
                            // Cập nhật logo ngân hàng sau khi load lại bảng
                            if (typeof updateBankLogosInTable === 'function') {
                                updateBankLogosInTable();
                            } else if (typeof loadBanks === 'function' && window.bankNameToLogo && Object.keys(window.bankNameToLogo).length > 0) {
                                updateBankLogosInTable();
                            }
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Có lỗi xảy ra khi tạo cộng tác viên.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.error('Lỗi parse response:', e);
                        }
                    }
                    Swal.fire('Lỗi', errorMessage, 'error');
                    console.error('Lỗi khi tạo/cập nhật CTV:', xhr.responseText);
                }
            });
        } else {
            // Vẫn còn lỗi, cuộn đến lỗi đầu tiên
            $('html, body').animate({
                scrollTop: $('#formCreateCollaborator .error:visible:first').offset().top - 100
            }, 300);
        }
    });

    // Gắn sự kiện validate khi người dùng nhập liệu
    $('#full_nameForm').on('input', validateFullNameForm);
    $('#phoneForm').on('input', validatePhoneForm);
    $('#address').on('input', validateAddressForm);

    // Gắn sự kiện validate cho các trường select khi giá trị thay đổi
    $('#provinceForm, #districtForm, #wardForm').on('change', function() {
        validateSelectFields();
    });
});

