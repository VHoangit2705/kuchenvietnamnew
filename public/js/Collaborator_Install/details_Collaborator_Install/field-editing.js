/**
 * Xử lý chỉnh sửa các trường động (edit icon)
 */

function updateField(fieldId, value) {
    let td = $("#" + fieldId);
    let html = `<span class=\"text-value\">${value ?? ''}</span>`;
    if (fieldId === 'nganhang') {
        html += ` <img class=\"bank-logo ms-2\" alt=\"logo ngân hàng\" style=\"height:50px; display:none;\"/>`;
    }
    if (!value) {
        html += `<i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>`;
    }
    td.html(html);
    if (fieldId === 'nganhang') {
        updateBankLogoForCell(td);
    }
}

// NÂNG CẤP: Gắn validation vào trình xử lý .edit-icon
$(document).on("click", ".edit-icon", function() {
    let $td = $(this).closest("td");
    let $span = $td.find(".text-value");
    let oldValue = $span.text().trim();

    let field = $td.data("field");
    let agency = $td.data("agency");
    let fieldName = field || agency; // Tên định danh của trường

    let $input = $("<input>", {
        // Sửa đổi: Nếu là địa chỉ khách hàng, dùng textarea để có nhiều không gian hơn
        type: (fieldName === 'customer_address') ? 'textarea' : 'text',
        value: oldValue,
        class: "form-control d-inline-block w-100"
    });
    
    // Gắn data-field/data-agency vào input để dễ truy xuất
    if (field) $input.attr('data-field', field);
    if (agency) $input.attr('data-agency', agency);

    if (fieldName === "ngaycap" || fieldName === "agency_release_date") {
        $input.attr("type", "date");
        // Chuyển đổi format từ d/m/Y sang Y-m-d cho input date
        if (oldValue && oldValue.includes('/')) {
            let parts = oldValue.split('/');
            if (parts.length === 3) {
                let day = parts[0].padStart(2, '0');
                let month = parts[1].padStart(2, '0');
                let year = parts[2];
                $input.val(year + '-' + month + '-' + day);
            }
        }
    }
    // Xử lý khi người dùng nhập liệu
    $input.on("input change", function() {
        validateDynamicField($(this), fieldName);
    });
    
    // Gắn data-field/data-agency vào input để dễ truy xuất
    if (field) $input.attr('data-field', field);
    if (agency) $input.attr('data-agency', agency);

    if (fieldName === "ngaycap" || fieldName === "agency_release_date") {
        $input.attr("type", "date");
        // Chuyển đổi format từ d/m/Y sang Y-m-d cho input date
        if (oldValue && oldValue.includes('/')) {
            let parts = oldValue.split('/');
            if (parts.length === 3) {
                let day = parts[0].padStart(2, '0');
                let month = parts[1].padStart(2, '0');
                let year = parts[2];
                $input.val(year + '-' + month + '-' + day);
            }
        }
    }
    if (fieldName === "nganhang" || fieldName === "agency_bank") {
        $input.attr('list', 'bankList');
    }

    // --- BẮT ĐẦU GẮN VALIDATION ---
    $input.on("input change", function() {
        validateDynamicField($(this), fieldName);
    });
    // --- KẾT THÚC GẮN VALIDATION ---

    // Xử lý khi blur (rời input) - ĐÃ NÂNG CẤP
    $input.on("blur", function() {
        validateDynamicField($(this), fieldName); // Chạy validation lần cuối
        let newValue = $(this).val().trim();
        
        // Lưu giá trị hiển thị đầy đủ ban đầu để khôi phục khi lỗi
        let oldDisplayValue = $("#customer_address_full").val() || oldValue;
        if (fieldName === 'customer_address' && !oldDisplayValue) {
            // Fallback: ghép oldValue với fullAddress
            let fullAddress = window.FULL_ADDRESS || '';
            if (oldValue && fullAddress) {
                oldDisplayValue = oldValue + ", " + fullAddress;
            } else if (fullAddress) {
                oldDisplayValue = fullAddress;
            } else {
                oldDisplayValue = oldValue;
            }
        }

        // Trường hợp 1: Người dùng xóa rỗng -> Luôn gỡ lỗi và cập nhật
        if (newValue === '') {
            hideError($(this)); // Gỡ lỗi
            $span.text('').show(); // Cập nhật span thành rỗng
        
        // Trường hợp 2: Người dùng nhập đúng (không rỗng VÀ không có cờ lỗi)
        } else if (!validationErrors[fieldName]) {
            // Xử lý format ngày tháng trước khi hiển thị
            if (fieldName === "ngaycap" || fieldName === "agency_release_date") {
                if (newValue && newValue.includes('-')) {
                    let parts = newValue.split('-');
                    if (parts.length === 3) {
                        let year = parts[0];
                        let month = parts[1];
                        let day = parts[2];
                        newValue = day + '/' + month + '/' + year;
                    }
                }
            }
            
            // Lưu địa chỉ khách hàng vào database nếu là customer_address
            if (fieldName === 'customer_address') {
                let orderCode = window.ORDER_CODE || '';
                if (orderCode) {
                    $.ajax({
                        url: window.ROUTES.dieuphoi_update_address || '/dieuphoi/update/address',
                        method: "POST",
                        data: {
                            _token: $('meta[name="csrf-token"]').attr("content"),
                            order_code: orderCode,
                            address: newValue
                        },
                        success: function(response) {
                            if (response.success) {
                                // Cập nhật lại full address với phần địa chỉ mới
                                let fullAddress = window.FULL_ADDRESS || '';
                                let fullAddressText = newValue;
                                if (newValue && fullAddress) {
                                    fullAddressText = newValue + ", " + fullAddress;
                                } else if (fullAddress) {
                                    fullAddressText = fullAddress;
                                }
                                $span.text(fullAddressText).show();
                                // Cập nhật lại hidden inputs
                                $("#customer_address_full").val(fullAddressText);
                                $("#customer_address_detail").val(newValue);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: response.message || 'Không thể cập nhật địa chỉ',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Quay về giá trị cũ nếu lưu thất bại
                                $span.text(oldDisplayValue).show();
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: 'Có lỗi xảy ra khi cập nhật địa chỉ',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            // Quay về giá trị cũ nếu lưu thất bại
                            $span.text(oldDisplayValue).show();
                        }
                    });
                } else {
                    // Nếu không có order_code, vẫn hiển thị giá trị mới
                    $span.text(newValue).show();
                }
            } else {
                $span.text(newValue).show(); // Cập nhật span với giá trị mới cho các trường khác
            }
        
        // Trường hợp 3: Người dùng nhập sai và rời đi (không rỗng VÀ có cờ lỗi)
        } else {
            hideError($(this)); // Gỡ lỗi (vì chúng ta không lưu giá trị sai)
            // Quay về giá trị cũ (dùng oldDisplayValue cho customer_address)
            let displayValue = (fieldName === 'customer_address') ? oldDisplayValue : oldValue;
            $span.text(displayValue).show();
        }

        $td.find(".edit-icon").show();
        $(this).remove();

        // Cập nhật logo ngân hàng sau khi rời input ở cả 2 trường hợp
        if (fieldName === 'nganhang' || fieldName === 'agency_bank') {
            updateBankLogoForCell($td);
        }
    });

    // Xử lý nhấn Enter
    $input.on("keypress", function(e) {
        if (e.which === 13) $(this).blur();
    });

    // Ẩn span và icon, hiển thị input
    $span.hide();
     // Ẩn icon bút// Nếu là địa chỉ khách hàng, ẩn cả phần địa chỉ tĩnh (tỉnh/huyện/xã)
    if (fieldName === 'customer_address') {
        $td.contents().filter(function() { return this.nodeType === 3; }).remove(); // Xóa text node ", {{ $fullAddress }}"
    }      
    $(this).hide();
    $td.prepend($input);
    $input.focus();
    
    // Chạy validation ngay khi input xuất hiện
    validateDynamicField($input, fieldName);
});

