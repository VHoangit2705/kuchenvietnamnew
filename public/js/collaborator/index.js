// Tạo cờ chung để theo dõi lỗi validation
let searchValidationErrors = {};

// Hàm hiển thị lỗi
function showError($field, message) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;

    hideError($field);

    // Tạo thẻ div lỗi
    let $error = $(`<div class="text-danger mt-1 validation-error" data-error-for="${fieldId}">${message}</div>`);

    $field.closest('.col-md-4').append($error);
    $field.css('border-color', 'red');

    searchValidationErrors[fieldId] = true;
    updateSearchButtonState();
}

// Hàm ẩn lỗi
function hideError($field) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;

    // Tìm và xóa thẻ lỗi tương ứng
    $field.closest('.col-md-4').find(`.validation-error[data-error-for="${fieldId}"]`).remove();
    $field.css('border-color', '');

    delete searchValidationErrors[fieldId];
    updateSearchButtonState();
}

// Hàm cập nhật trạng thái nút Tìm kiếm
function updateSearchButtonState() {
    // Kiểm tra xem có bất kỳ lỗi nào trong cờ chung không
    let hasErrors = Object.keys(searchValidationErrors).length > 0;
    
    // Vô hiệu hóa nút nếu có lỗi
    $("#searchBtn").prop('disabled', hasErrors);
}

// Hàm logic validate cho Họ Tên (sử dụng hàm chung từ common.js)
function validateFullName() {
    let $input = $('#full_name');
    let name = $input.val();
    const result = validateName(name, false); // Không bắt buộc cho search form
    
    hideError($input);
    
    if (!result.isValid) {
        showError($input, result.message);
    }
}

// Hàm logic validate cho Số Điện Thoại (sử dụng hàm chung từ common.js)
function validatePhoneSearch() {
    let $input = $('#phone');
    let phone = $input.val();
    // Gọi hàm validatePhone từ common.js
    const result = validatePhone(phone, false); // Không bắt buộc cho search form
    
    hideError($input);
    
    if (!result.isValid) {
        showError($input, result.message);
    }
}

// Khai báo biến routes và requestParams ở ngoài scope để có thể sử dụng ở nhiều nơi
let routes = {};
let requestParams = {};

$(document).ready(function() {
    // Thêm CSRF token vào tất cả các header của request AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Lấy các route URLs từ data attributes
    routes = {
        getDistrict: $('#searchCollaborator').data('route-getdistrict') || '',
        getWard: $('#searchCollaborator').data('route-getward') || '',
        getList: $('#searchCollaborator').data('route-getlist') || ''
    };

    // Lấy các giá trị request từ data attributes
    requestParams = {
        province: $('#searchCollaborator').data('request-province') || '',
        district: $('#searchCollaborator').data('request-district') || '',
        ward: $('#searchCollaborator').data('request-ward') || ''
    };

    $('#openModalBtn').on('click', function(e) {
        e.preventDefault();
        $('#tieude').text("Thêm mới cộng tác viên");
        $('#hoantat').text('Thêm mới');
        $('#addCollaboratorModal').modal('show');
    });

    $('#full_name').on('keyup input', validateFullName);
    $('#phone').on('keyup input', validatePhoneSearch);
});

// Cập nhật lại hàm submit
$('#searchCollaborator').on('submit', function(e) {
    e.preventDefault();
    
    // Kiểm tra cờ lỗi chung
    if (Object.keys(searchValidationErrors).length > 0) {
        return; // Dừng lại nếu form không hợp lệ
    }

    let formData = $(this).serialize();
    let url = routes.getList + "?" + formData;

    // Vô hiệu hóa nút tạm thời khi đang gửi request
    $('#searchBtn').prop('disabled', true).text('Đang tìm...');

        $.get(url, function(response) {
            $('#tabContent').html(response); // chỉ render HTML
            // Cập nhật logo ngân hàng sau khi load lại bảng
            if (typeof updateBankLogosInTable === 'function') {
                updateBankLogosInTable();
            } else if (typeof loadBanks === 'function' && window.bankNameToLogo && Object.keys(window.bankNameToLogo).length > 0) {
                // Nếu đã có logo mapping, chỉ cần cập nhật
                updateBankLogosInTable();
            }
        }).fail(function() {
            alert("Không thể tải dữ liệu");
        }).always(function() {
            // Dù thành công hay thất bại, bật lại nút
            // và chạy lại validate để set trạng thái disable chính xác
            $('#searchBtn').text('Tìm kiếm');
        });
});

$('#province').on('change', function() {
    let provinceId = $(this).val();
    $('#district').empty().append('<option value="" selected>Quận/Huyện</option>');
    $('#ward').empty().append('<option value="" selected>Phường/Xã</option>');
    
    // Sử dụng biến routes đã khai báo ở ngoài và hàm chung từ common.js
    if (provinceId && routes.getDistrict) {
        loadDistricts(provinceId, routes.getDistrict, 'district', 'Quận/Huyện');
    }
});

$('#district').on('change', function() {
    let districtId = $(this).val();
    
    // Sử dụng biến routes đã khai báo ở ngoài và hàm chung từ common.js
    if (districtId && routes.getWard) {
        loadWards(districtId, routes.getWard, 'ward', 'Xã/Phường');
    }
});

