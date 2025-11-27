/**
 * Các hàm cập nhật dữ liệu CTV và Đại lý
 */

function UpdateCollaborator() {
    let id = $("#ctv_id").val();
    let orderCode = window.ORDER_CODE || '';
    let data = {
        _token: $('meta[name="csrf-token"]').attr("content"),
        id: id,
        order_code: orderCode
    };
    $("td[data-field]").each(function() {
        let $td = $(this);
        let field = $td.data("field");
        let value;
        if ($td.find("input").length) {
            value = $td.find("input").val().trim();
        } else {
            value = $td.find(".text-value").text().trim();
        }
        
        // NÂNG CẤP: Gửi ngày tháng đúng định dạng Y-m-d
        if (field === 'ngaycap' && value && value.includes('/')) {
             let parts = value.split('/');
             if (parts.length === 3) value = parts[2] + '-' + parts[1] + '-' + parts[0];
        }
        
        data[field] = value;
    });

    $.ajax({
        url: window.ROUTES.ctv_update || '/ctv/update',
        method: "POST",
        data: data,
        success: function(response) {
            // Collaborator updated successfully
        },
        error: function(xhr, status, error) {
            // Error updating collaborator
        }
    });
}

function UpdateAgency() {
    let orderCode = window.ORDER_CODE || '';
    let data = {
        _token: $('meta[name="csrf-token"]').attr("content"),
        order_code: orderCode
    };
    
    // Đảm bảo agency_phone luôn được gửi
    let agencyPhone = '';
    $("td[data-agency]").each(function() {
        let $td = $(this);
        let agency = $td.data("agency");
        let value;
        if ($td.find("input").length) {
            value = $td.find("input").val().trim();
        } else {
            value = $td.find(".text-value").text().trim();
        }
        
        if (agency === "agency_phone") {
            agencyPhone = value;
        }
        
        // Xử lý format ngày tháng cho agency_release_date
        if (agency === "agency_release_date" && value && value.includes('/')) {
            // Chuyển từ d/m/Y sang Y-m-d cho database
            let parts = value.split('/');
            if (parts.length === 3) {
                let day = parts[0].padStart(2, '0');
                let month = parts[1].padStart(2, '0');
                let year = parts[2];
                value = year + '-' + month + '-' + day;
            }
        }
        
        data[agency] = value;
    });
    
    // Kiểm tra nếu không có agency_phone
    if (!agencyPhone) {
        return;
    }
    
    $.ajax({
        url: window.ROUTES.agency_update || '/agency/update',
        method: "POST",
        data: data,
        success: function(response) {
            if (response.success) {
                // Agency update successful
            }
        },
        error: function(xhr, status, error) {
            // Error updating agency
        }
    });
}

function Update() {
    $("#btnUpdate, #btnComplete, #btnPay").on("click", function(e) {
        e.preventDefault();
        
        // 1. Kiểm tra validation tổng thể (đã sửa ở bước trước)
        if (!validateAll()) {
            return; // Dừng lại nếu có lỗi
        }
        
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Hành động này không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Có, tiếp tục!',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                const urlParams = new URLSearchParams(window.location.search);
                const type = urlParams.get('type');
                
                let action = $(this).data('action');
                let isInstallAgency = $("#isInstallAgency").is(":checked") ? 1 : 0;
                let formData = new FormData();
                formData.append("_token", $('meta[name="csrf-token"]').attr("content"));
                formData.append("id", window.MODEL_ID || '');
                formData.append("action", action);
                formData.append("type", type);
                formData.append("product", $('#product_name').val());
                

                if (isInstallAgency === 1) {
                    formData.append("ctv_id", 1);
                    formData.append("successed_at", $("#successed_at").val().trim());
                    
                    // SỬA LỖI TẠI ĐÂY: Thêm $()
                    formData.append("installcost", getCurrencyValue( $('#install_cost_agency') ));
                
                } else {
                    formData.append("ctv_id", $("#ctv_id").val());
                    formData.append("successed_at", $("#successed_at_ctv").val().trim());
                    
                    // SỬA LỖI TẠI ĐÂY: Thêm $()
                    formData.append("installcost", getCurrencyValue( $('#install_cost_ctv') ));
                    
                    let file = $("#install_review")[0].files[0];
                    if (file) {
                        formData.append("installreview", file);
                    }
                }
                OpenWaitBox();
                $.ajax({
                    url: window.ROUTES.dieuphoi_update || '/dieuphoi/update',
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        CloseWaitBox();
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                UpdateCollaborator();
                                if (hasAgencyChanges()) {
                                    UpdateAgency();
                                }
                                location.reload();
                                loadTableData();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            })
                        }
                    },
                    error: function(xhr) {
                        CloseWaitBox();
                        alert("Có lỗi xảy ra khi cập nhật!");
                    }
                });
            }
        });
    });
}

function validateBasicInfo() {
    if ($("#isInstallAgency").is(":checked")) {
        // SỬA LỖI: Thêm $() để truyền vào một jQuery object, không phải string
        return parseInt(getCurrencyValue( $('#install_cost_agency') ), 10) > 0;
    } else {
        // SỬA LỖI: Thêm $() để truyền vào một jQuery object, không phải string
        return $("#ctv_id").val() !== '' && parseInt(getCurrencyValue( $('#install_cost_ctv') ), 10) > 0;
    }
}

// NÂNG CẤP: Hàm kiểm tra tổng thể mới
function validateAll() {
    // 1. Chạy lại tất cả validation để bắt lỗi
    runAllInitialValidations();
    
    // 2. Kiểm tra thông tin cơ bản (CTV, chi phí...)
    if (!validateBasicInfo()) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi thông tin cơ bản',
            text: 'Vui lòng chọn CTV và nhập chi phí, hoặc chọn "Đại lý lắp đặt" và nhập chi phí.',
            timer: 3000,
            showConfirmButton: false
        });
        return false;
    }
    
    // 3. Kiểm tra cờ lỗi
    let hasErrors = Object.keys(validationErrors).length > 0;
    if (hasErrors) {
         Swal.fire({
            icon: 'error',
            title: 'Lỗi điền thông tin',
            text: 'Vui lòng sửa các lỗi được tô đỏ trước khi tiếp tục.',
            timer: 3000,
            showConfirmButton: false
        });
        return false;
    }
    
    return true; // Tất cả đều hợp lệ
}

