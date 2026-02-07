// JS cho formwarranty.blade.php
// Cấu hình được inject từ Blade: window.FORM_WARRANTY_CONFIG

$(document).ready(function () {
    const config = window.FORM_WARRANTY_CONFIG || {};

    SelectProduct();
    ClickCheckBox();
    ValidateInputDate();
    ShowCTVFiles();
    SetDefaultReturnDate();
    
    // Tự động tính lại ngày hẹn trả nếu người dùng xóa trường
    $('#return_date').on('blur', function() {
        if (!$(this).val() || $(this).val().trim() === '') {
            SetDefaultReturnDate();
        }
    });

    $('#hoantat').on('click', function (e) {
        e.preventDefault();
        if (!ValidateForm()) return;
        createWarrantyRequest();
    });

    $('#province').on('change', function () {
        let provinceId = $(this).val();
        $('#district').empty().append('<option value="" selected>-- Chọn Huyện --</option>');
        $('#ward').empty().append('<option value="" selected>-- Chọn Xã --</option>');

        let urlTemplate = config.routes?.getDistrict || '';
        let url = urlTemplate.replace(':province_id', provinceId);

        if (provinceId && url) {
            $.ajax({
                url: url,
                type: 'GET',
                success: function (data) {
                    let $district = $('#district');
                    $district.empty();
                    $district.append('<option value="" disabled selected>-- Chọn Huyện --</option>');
                    data.forEach(function (item) {
                        $district.append('<option value="' + item.district_id + '">' + item.name + '</option>');
                    });
                },
            });
        }
    });

    $('#district').on('change', function () {
        let districtId = $(this).val();
        let urlTemplate = config.routes?.getWard || '';
        let url = urlTemplate.replace(':district_id', districtId);

        if (districtId && url) {
            $.ajax({
                url: url,
                type: 'GET',
                success: function (data) {
                    let $ward = $('#ward');
                    $ward.empty();
                    $ward.append('<option value="" disabled selected>-- Chọn Xã --</option>');
                    data.forEach(function (item) {
                        $ward.append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                    });
                },
            });
        }
    });

    // Gợi ý sản phẩm (sử dụng danh sách products)
    const productList = config.products || [];
    $('#product').on('input', function () {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#product-suggestions');
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        const matchedProducts = productList.filter(p =>
            (p.product_name || '').toLowerCase().includes(keyword)
        );

        if (matchedProducts.length > 0) {
            matchedProducts.slice(0, 10).forEach(p => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    // Khi người dùng chọn sản phẩm gợi ý
    $(document).on('mousedown', '#product-suggestions button', function () {
        $('#product').val($(this).text());
        if ($(this).data('product-id') == 1) {
            $('#serialthanmayGroup').remove('d-none');
        }
        $('#serialthanmayGroup').addClass('d-none');
        $('#product-suggestions').addClass('d-none');
    });

    $('#product').on('blur', function () {
        const inputVal = $(this).val().trim().replace(/\r?\n|\r/g, '');
        const matchedProduct = productList.find(product =>
            (product.product_name || '').trim().replace(/\r?\n|\r/g, '') === inputVal
        );
        if (!matchedProduct && inputVal !== '') {
            $('#product').val('');
            Swal.fire({
                icon: 'warning',
                title: "Sản phẩm cũ không có trong hệ thống .Vui lòng liên hệ quản trị viên CNTT để được hỗ trợt.",
                timer: 1500,
            });
        }
        if (matchedProduct && matchedProduct.check_seri == 1) {
            $('#serialthanmayGroup').removeClass('d-none');
        }
        else { $('#serialthanmayGroup').addClass('d-none'); }
    });

    // Ẩn gợi ý khi click ra ngoài
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#product, #product-suggestions').length) {
            $('#product-suggestions').addClass('d-none');
        }
    });

    // auto fill vào serial (khi là select)
    $('#product').on('change', function () {
        var serial = $(this).find(':selected').data('serial') || '';
        $('#serial_number').val(serial);
    });
});

function ClickCheckBox() {
    $('#chkseri').on('change', function () {
        if ($(this).is(':checked')) {
            $('#serialGroup').hide();
            $('#serial_number').val('HÀNG KHÔNG CÓ MÃ SERI');
        } else {
            $('#serialGroup').show();
            $('#serial_number').val('');
        }
    });
}

function SelectProduct() {
    const config = window.FORM_WARRANTY_CONFIG || {};
    const productList = config.lstproduct || [];
    if (Array.isArray(productList) && productList.length === 1) {
        const $select = $('#product');
        $select.find('option:eq(1)').prop('selected', true).trigger('change');
        $('#serial_number').val(productList[0].warranty_code);
    }
}

function createWarrantyRequest() {
    const config = window.FORM_WARRANTY_CONFIG || {};
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
    OpenWaitBox();
    $.ajax({
        url: config.routes?.createWarranty || '',
        type: "POST",
        data: formData,
        headers: {
            'X-CSRF-TOKEN': config.csrfToken || $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            CloseWaitBox();
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Tạo phiếu thành công!',
                    timer: 2000,
                }).then(() => {
                    const takePhotoRoute = config.routes?.takePhoto || '';
                    if (takePhotoRoute) {
                        window.location.href = takePhotoRoute + "?sophieu=" + res.id;
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: res.message,
                    timer: 2000,
                });
            }
        },
        error: function (xhr) {
            CloseWaitBox();
            let msg = "Đã xảy ra lỗi!";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: msg
            });
        }
    });
}

// Các biến dùng chung cho validateForm / validatePhone
let __formIsValid;
let __firstErrorField;

function ValidateForm() {
    __formIsValid = true;
    __firstErrorField = null;
    // Xóa thông báo lỗi cũ
    $('#warrantyCard .error').text('');
    let requiredFields = [];
    let selected = $('#type').val();
    if (selected === 'agent_component') {
        requiredFields.push(
            '#product',
            '#type',
            '#full_name',
            '#phone_number',
            '#address',
            '#shipment_date',
            '#return_date'
        );
    } else {
        requiredFields.push(
            '#product',
            '#serial_number',
            '#type',
            '#full_name',
            '#phone_number',
            '#address',
            '#shipment_date',
            '#return_date',
            '#initial_fault_condition',
            '#product_fault_condition',
            '#product_quantity_description',
        );
    }

    // Duyệt các trường required
    $.each(requiredFields, function (i, selector) {
        const $field = $(selector);
        const val = $field.val()?.trim();

        if (!val) {
            $field.closest('.form-group').find('.error').text('Trường này là bắt buộc.');
            if (!__firstErrorField) __firstErrorField = $field;
            __formIsValid = false;
        }
    });

    // Kiểm tra số điện thoại
    validatePhone('#phone_number');
    validatePhone('#ctv_phone');

    // Kiểm tra định dạng ngày
    ['#shipment_date', '#return_date'].forEach(function (selector) {
        const dateVal = $(selector).val()?.trim();
        if (dateVal && !isValidDate(dateVal)) {
            $(selector).closest('.form-group').find('.error').text('Ngày không hợp lệ (dd/mm/yyyy).');
            if (!__firstErrorField) __firstErrorField = $(selector);
            __formIsValid = false;
        }
    });

    // Kiểm tra ngày hẹn trả >= hôm nay
    const returnDateStr = $('#return_date').val()?.trim();
    if (returnDateStr && isValidDate(returnDateStr)) {
        const returnDate = parseDate(returnDateStr);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Đặt thời gian về 0 để so sánh chính xác
        if (returnDate < today) {
            $('#return_date').closest('.form-group').find('.error').text('Ngày hẹn trả phải lớn hơn hoặc bằng ngày tiếp nhận.');
            if (!__firstErrorField) __firstErrorField = $('#return_date');
            __formIsValid = false;
        }
    }
    if (__firstErrorField) {
        __firstErrorField.focus();
    }
    return __formIsValid;
}

// Kiểm tra số điện thoại
function validatePhone(selector) {
    const value = $(selector).val()?.trim();
    if (value && !/^\d{10,12}$/.test(value)) {
        $(selector).closest('.form-group').find('.error').text('SĐT phải có từ 10 đến 12 chữ số.');
        if (!__firstErrorField) __firstErrorField = $(selector);
        __formIsValid = false;
    }
}

// Hàm convert dd/mm/yyyy thành đối tượng Date
function parseDate(dateStr) {
    const [day, month, year] = dateStr.split('/');
    return new Date(`${year}-${month}-${day}`);
}

// Kiểm tra định dạng ngày hợp lệ
function isValidDate(dateStr) {
    const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = dateStr.match(regex);
    if (!match) return false;

    const day = parseInt(match[1]);
    const month = parseInt(match[2]);
    const year = parseInt(match[3]);

    if (year < 1900 || year > 2200 || month < 1 || month > 12 || day < 1 || day > 31) return false;

    const d = new Date(year, month - 1, day);
    return d && d.getFullYear() === year && d.getMonth() + 1 === month && d.getDate() === day;
}

function ValidateInputDate() {
    $('.date-input').on('input', function () {
        let val = $(this).val();

        // Chỉ giữ lại số và dấu "/"
        val = val.replace(/[^\d\/]/g, '');

        // Tự động thêm dấu '/' sau ngày
        if (val.length > 2 && val[2] !== '/') {
            val = val.slice(0, 2) + '/' + val.slice(2);
        }

        // Tự động thêm dấu '/' sau tháng
        if (val.length > 5 && val[5] !== '/') {
            val = val.slice(0, 5) + '/' + val.slice(5);
        }

        // Giới hạn độ dài 10 ký tự
        if (val.length > 10) {
            val = val.slice(0, 10);
        }

        $(this).val(val);
    });
}

// Tự động set ngày hẹn trả = ngày tiếp nhận + 3 ngày
function SetDefaultReturnDate() {
    const receivedDateStr = $('#received_date').val()?.trim();
    
    if (receivedDateStr && isValidDate(receivedDateStr)) {
        const receivedDate = parseDate(receivedDateStr);
        // Cộng thêm 3 ngày
        receivedDate.setDate(receivedDate.getDate() + 3);
        
        // Format lại thành dd/mm/yyyy
        const day = ('0' + receivedDate.getDate()).slice(-2);
        const month = ('0' + (receivedDate.getMonth() + 1)).slice(-2);
        const year = receivedDate.getFullYear();
        const returnDateStr = `${day}/${month}/${year}`;
        
        // Chỉ set nếu trường return_date đang trống
        if (!$('#return_date').val() || $('#return_date').val().trim() === '') {
            $('#return_date').val(returnDateStr);
        }
    }
}

function ShowCTVFiles() {
    const config = window.FORM_WARRANTY_CONFIG || {};

    let type = $('#type').val();
    if (type === 'agent_component') {
        $('.ctv-fields').show();
        $('.ctv-fields input').attr('required', false);
        $('.description_error').hide();
        $('.description_error textarea').val('');
        $('.description_error textarea').removeAttr('required');
    }
    else {
        $('.ctv-fields').hide();
        $('.ctv-fields input').removeAttr('required');
        $('.ctv-fields input').val('');
        $('.description_error').show();
        $('.description_error').attr('required', true);
    }
    // Lắng nghe sự kiện thay đổi của select
    $('#type').on('change', function () {
        let selected = $(this).val();
        if (selected === 'agent_component') {
            $('.ctv-fields').show();
            $('.ctv-fields input').attr('required', false);
            $('.description_error').hide();
            $('.description_error textarea').val('');
            $('.description_error textarea').removeAttr('required');
            $('label[for="serial_number"]').html('Mã seri tem bảo hành');
            $('.addressprovince').addClass('d-none');
        }
        else if (selected === 'agent_home') {
            $('.addressprovince').removeClass('d-none');
            $('.ctv-fields').hide();
            $('.ctv-fields input').removeAttr('required');
            $('.ctv-fields input').val('');
            $('#collaborator_id').val('');
            $('.description_error').show();
            $('.description_error').attr('required', true);
            $('label[for="serial_number"]').html('Mã seri tem bảo hành (<span style="color: red;">*</span>)');
        }
        else {
            $('.addressprovince').addClass('d-none');
            $('.ctv-fields').hide();
            $('.ctv-fields input').removeAttr('required');
            $('.ctv-fields input').val('');
            $('#collaborator_id').val('');
            $('.description_error').show();
            $('.description_error').attr('required', true);
            $('label[for="serial_number"]').html('Mã seri tem bảo hành (<span style="color: red;">*</span>)');
        }
    });

    //sau khi nhập xong mã seri
    $('#serial_number').on('blur', function () {
        const config = window.FORM_WARRANTY_CONFIG || {};
        let serial = $(this).val();
        if (serial && serial != '') {
            OpenWaitBox();
            $.ajax({
                url: config.routes?.findOld || '',
                method: 'POST',
                data: {
                    serial: serial,
                    _token: config.csrfToken || $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    CloseWaitBox();
                    if (!response.success) {
                        if (response.type == 0) {
                            $('#text_title').addClass('d-none');
                        } else {
                            $('#text_title').removeClass('d-none');
                            $('#text_title').removeClass('text-success');
                            $('#text_title').addClass('text-danger');
                            $('#text_title').html('Mã serial không đúng hoặc chưa được kích hoạt');
                        }
                    }
                    else {
                        if (!response.tem) {
                            $('#text_title').addClass('d-none');
                            $('#shipment_date').val('');
                            $('#full_name').val('');
                            $('#phone_number').val('');
                            $('#address').val('');
                            return;
                        }
                        const today = new Date();
                        const warrantyend = new Date(response.tem.han_bao_hanh);
                        let baohanh = '';
                        if (today <= warrantyend) {
                            baohanh = 'Còn hạn bảo hành';
                            $('#text_title').removeClass('text-danger');
                            $('#text_title').addClass('text-success');
                        } else {
                            baohanh = 'Hết hạn bảo hành';
                            $('#text_title').removeClass('text-success');
                            $('#text_title').addClass('text-danger');
                        }
                        const NgayNhap = response.tem.ngay_nhap_kho ? formatDateToDMY(response.tem.ngay_nhap_kho) : '';
                        const NgayKichHoat = response.tem.ngay_kich_hoat ? formatDateToDMY(response.tem.ngay_kich_hoat) : '';
                        $('#text_title').html(`Loại sản phẩm: ${response.tem.ten_san_pham}, ngày xuất kho: ${NgayNhap}, ngày kích hoạt: ${NgayKichHoat}, ${baohanh}`);
                        $('#text_title').removeClass('d-none');
                        $('#shipment_date').val(NgayNhap);
                        if (response.khach_hang) {
                            $('#full_name').val(response.khach_hang.ho_ten ?? '');
                            $('#phone_number').val(response.khach_hang.so_dien_thoai ?? '');
                            $('#address').val(response.khach_hang.dia_chi ?? '');
                        }
                    }
                },
                error: function (xhr) {
                    CloseWaitBox();
                    console.error('Đã xảy ra lỗi:', xhr.responseText);
                }
            });
        }
    });

    function formatDateToDMY(dateString) {
        const date = new Date(dateString);
        if (isNaN(date)) return '';

        const day = ('0' + date.getDate()).slice(-2);
        const month = ('0' + (date.getMonth() + 1)).slice(-2);
        const year = date.getFullYear();

        return `${day}/${month}/${year}`;
    }

    // sau khi nhập số điện thoại
    $('#ctv_phone').on('blur', function () {
        const config = window.FORM_WARRANTY_CONFIG || {};
        let phone = $(this).val();
        if (phone) {
            $.ajax({
                url: config.routes?.getCollaborator || '',
                method: 'POST',
                data: {
                    phone: phone,
                    _token: config.csrfToken || $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (!response.success) {
                        Swal.fire({
                            icon: 'warning',
                            title: response.message,
                            timer: 1500,
                        });
                    }
                    else {
                        $('#collaborator_id').val(response.data.id ?? '');
                        $('#ctv_name').val(response.data.full_name ?? '');
                        $('#ctv_address').val(response.data.address ?? '');
                    }
                },
                error: function (xhr) {
                    console.error('Đã xảy ra lỗi:', xhr.responseText);
                }
            });
        }
    });
}

