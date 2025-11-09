/**
 * Helper functions for formwarranty
 */

// NOTE: Các hàm formatDateToDMY và parseDate đã được di chuyển sang /js/common.js để tái sử dụng.
// Vui lòng đảm bảo common.js được load trước file này.

// Validate input date format
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

// Select product if only one product in list
function SelectProduct() {
    const productList = window.productList || [];
    if (Array.isArray(productList) && productList.length === 1) {
        const $select = $('#product');
        $select.find('option:eq(1)').prop('selected', true).trigger('change');
        $('#serial_number').val(productList[0].warranty_code);
    }
}

// Handle checkbox for serial
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

// Show/hide CTV fields based on type
function ShowCTVFiles() {
    let type = $('#type').val();
    if (type === 'agent_component') {
        $('.ctv-fields').show();
        $('.ctv-fields input').attr('required', true);
        $('.description_error').hide();
        $('.description_error textarea').val('');
        $('.description_error textarea').removeAttr('required');
    } else {
        $('.ctv-fields').hide();
        $('.ctv-fields input').removeAttr('required');
        $('.ctv-fields input').val('');
        $('.description_error').show();
        $('.description_error').attr('required', true);
    }
    
    // Lắng nghe sự kiện thay đổi của select
    $('#type').on('change', function() {
        let selected = $(this).val();
        if (selected === 'agent_component') {
            $('.ctv-fields').show();
            $('.ctv-fields input').attr('required', true);
            $('.description_error').hide();
            $('.description_error textarea').val('');
            $('.description_error textarea').removeAttr('required');
            $('label[for="serial_number"]').html('Mã seri tem bảo hành');
            $('.addressprovince').addClass('d-none');
        } else if(selected === 'agent_home') {
            $('.addressprovince').removeClass('d-none');
            $('.ctv-fields').hide();
            $('.ctv-fields input').removeAttr('required');
            $('.ctv-fields input').val('');
            $('#collaborator_id').val('');
            $('.description_error').show();
            $('.description_error').attr('required', true);
            $('label[for="serial_number"]').html('Mã seri tem bảo hành (<span style="color: red;">*</span>)');
        } else {
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
}

// Setup province/district/ward cascading selects
function setupAddressSelects() {
    $('#province').on('change', function() {
        let provinceId = $(this).val();
        $('#district').empty().append('<option value="" selected>-- Chọn Huyện --</option>');
        $('#ward').empty().append('<option value="" selected>-- Chọn Xã --</option>');
        let url = window.getDistrictRoute.replace(':province_id', provinceId);
        if (provinceId) {
            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    let $district = $('#district');
                    $district.empty();
                    $district.append('<option value="" disabled selected>-- Chọn Huyện --</option>');
                    data.forEach(function(item) {
                        $district.append('<option value="' + item.district_id + '">' + item.name + '</option>');
                    });
                },
            });
        }
    });

    $('#district').on('change', function() {
        let districtId = $(this).val();
        let url = window.getWardRoute.replace(':district_id', districtId);
        if (districtId) {
            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    let $ward = $('#ward');
                    $ward.empty();
                    $ward.append('<option value="" disabled selected>-- Chọn Xã --</option>');
                    data.forEach(function(item) {
                        $ward.append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                    });
                },
            });
        }
    });
}

// Handle serial number lookup
function setupSerialLookup() {
    $('#serial_number').on('blur', function() {
        let serial = $(this).val();
        if (serial && serial != '') {
            if (typeof OpenWaitBox === 'function') {
                OpenWaitBox();
            }
            $.ajax({
                url: window.warrantyFindOldRoute || '',
                method: 'POST',
                data: {
                    serial: serial,
                    _token: window.csrfToken || ''
                },
                success: function(response) {
                    if (typeof CloseWaitBox === 'function') {
                        CloseWaitBox();
                    }
                    if (!response.success) {
                        if(response.type == 0) {
                            $('#text_title').addClass('d-none');
                        } else {
                            $('#text_title').removeClass('d-none');
                            $('#text_title').removeClass('text-success');
                            $('#text_title').addClass('text-danger');
                            $('#text_title').html('Mã serial không đúng hoặc chưa được kích hoạt');
                        }
                    } else {
                        if(!response.tem) {
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
                error: function(xhr) {
                    if (typeof CloseWaitBox === 'function') {
                        CloseWaitBox();
                    }
                    console.error('Đã xảy ra lỗi:', xhr.responseText);
                }
            });
        }
    });
}

// Handle CTV phone lookup
function setupCTVPhoneLookup() {
    $('#ctv_phone').on('blur', function () {
        let phone = $(this).val();
        if(phone) {
            $.ajax({
                url: window.getCollaboratorRoute || '',
                method: 'POST',
                data: {
                    phone: phone,
                    _token: window.csrfToken || ''
                },
                success: function (response) {
                    if(!response.success) {
                        showSwalMessage('warning', response.message, '', {
                            timer: 1500
                        });
                    } else {
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

