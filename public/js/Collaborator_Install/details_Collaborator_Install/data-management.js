/**
 * Quản lý dữ liệu CTV và Đại lý
 * Lưu và khôi phục dữ liệu ban đầu
 */

// Lưu giá trị ban đầu của các trường CTV
function saveOriginalCtvData() {
    originalCtvData = {
        ctv_name: $("#ctv_name").text().trim(),
        ctv_phone: $("#ctv_phone").text().trim(),
        ctv_id: $("#ctv_id").val(),
        sotaikhoan: $("#sotaikhoan .text-value").text().trim(),
        chinhanh: $("#chinhanh .text-value").text().trim(),
        nganhang: $("#nganhang .text-value").text().trim(),
        cccd: $("#cccd .text-value").text().trim(),
        ngaycap: $("#ngaycap .text-value").text().trim(),
        install_cost_ctv: $("#install_cost_ctv").val(),
        successed_at_ctv: $("#successed_at_ctv").val()
    };
}

// Lưu giá trị ban đầu của các trường Đại lý
function saveOriginalAgencyData() {
    originalAgencyData = {
        agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
        agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
        agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
        agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
        agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
        agency_branch: $("td[data-agency='agency_branch'] .text-value").text().trim(),
        agency_cccd: $("td[data-agency='agency_cccd'] .text-value").text().trim(),
        agency_release_date: $("td[data-agency='agency_release_date'] .text-value").text().trim()
    };
}

// Khôi phục giá trị ban đầu của các trường CTV
function restoreOriginalCtvData() {
    // Lưu thông tin đại lý hiện tại trước khi chuyển về CTV
    saveAgencyDataBeforeSwitch();
    
    $("#ctv_name").text(originalCtvData.ctv_name);
    $("#ctv_phone").text(originalCtvData.ctv_phone);
    $("#ctv_id").val(originalCtvData.ctv_id);
    updateField("sotaikhoan", originalCtvData.sotaikhoan);
    updateField("chinhanh", originalCtvData.chinhanh);
    updateField("nganhang", originalCtvData.nganhang);
    updateField("cccd", originalCtvData.cccd);
    updateField("ngaycap", originalCtvData.ngaycap);
    $("#install_cost_ctv").val(originalCtvData.install_cost_ctv);
    $("#successed_at_ctv").val(originalCtvData.successed_at_ctv);
    
    // Ghi log việc chuyển từ "Đại lý lắp đặt" về CTV
    logSwitchToCtv();
}

// Lưu thông tin đại lý trước khi chuyển về CTV
function saveAgencyDataBeforeSwitch() {
    let orderCode = window.ORDER_CODE || '';
    let data = {
        _token: $('meta[name="csrf-token"]').attr("content"),
        order_code: orderCode
    };
    
    // Thu thập thông tin đại lý hiện tại
    $("td[data-agency]").each(function() {
        let $td = $(this);
        let agency = $td.data("agency");
        let value;
        if ($td.find("input").length) {
            value = $td.find("input").val().trim();
        } else {
            value = $td.find(".text-value").text().trim();
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
    
    // Gửi AJAX để lưu thông tin đại lý trước khi chuyển
    $.ajax({
        url: window.ROUTES.agency_update || '/agency/update',
        method: "POST",
        data: data,
        success: function(response) {
            console.log('Agency data saved before switch to CTV');
        },
        error: function(xhr, status, error) {
            console.log('Error saving agency data before switch:', error);
        }
    });
}

// AJAX call để ghi log chuyển về CTV
function logSwitchToCtv() {
    let orderCode = window.ORDER_CODE || '';
    $.ajax({
        url: window.ROUTES.ctv_switch || '/ctv/switch',
        method: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr("content"),
            order_code: orderCode
        },
        success: function(response) {
            console.log('Logged switch to CTV');
        },
        error: function(xhr, status, error) {
            console.log('Error logging switch to CTV:', error);
        }
    });
}

// Clear các trường CTV về rỗng
function clearCtvData() {
    $("#ctv_name").text('');
    $("#ctv_phone").text('');
    $("#ctv_id").val('');
    updateField("sotaikhoan", '');
    updateField("chinhanh", '');
    updateField("nganhang", '');
    updateField("cccd", '');
    updateField("ngaycap", '');
    $("#install_cost_ctv").val('');
    $("#successed_at_ctv").val('');
    
    // Clear file input
    $("#install_review").val('');
    
    // Gửi AJAX để clear CTV data trên server nếu cần
    clearCtvDataOnServer();
}

// AJAX call để clear CTV data trên server
function clearCtvDataOnServer() {
    let orderCode = window.ORDER_CODE || '';
    $.ajax({
        url: window.ROUTES.ctv_clear || '/ctv/clear',
        method: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr("content"),
            order_code: orderCode
        },
        success: function(response) {
            // CTV data cleared successfully
            console.log('CTV data cleared on server');
        },
        error: function(xhr, status, error) {
            console.log('Error clearing CTV data:', error);
        }
    });
}

// Hàm kiểm tra xem có thay đổi thông tin đại lý không
function hasAgencyChanges() {
    // Lấy giá trị hiện tại của các trường đại lý
    let currentAgencyData = {
        agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
        agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
        agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
        agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
        agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
        agency_branch: $("td[data-agency='agency_branch'] .text-value").text().trim(),
        agency_cccd: $("td[data-agency='agency_cccd'] .text-value").text().trim(),
        agency_release_date: $("td[data-agency='agency_release_date'] .text-value").text().trim()
    };

    // So sánh với giá trị ban đầu đã lưu
    for (let field in originalAgencyData) {
        if (originalAgencyData[field] !== currentAgencyData[field]) {
            return true; // Có thay đổi
        }
    }
    
    return false; // Không có thay đổi
}

