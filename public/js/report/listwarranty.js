// Đợi validation files load xong trước khi khởi tạo
function initListWarranty() {
    if (typeof resizeTableContainer === 'function') {
        resizeTableContainer();
    }
    // limitButtonClicks('btnsearch', 6);
    if (typeof validateDatesReport === 'function') {
        validateDatesReport();
    }
    if (typeof setupAutoComplete === 'function') {
        setupAutoComplete('#product', '#suggestions-product-name', window.productSearchRoute || '');
        setupAutoComplete('#staff_received', '#suggestions-product-staff', window.staffSearchRoute || '');
    }
    if (typeof runAllValidationsReport === 'function') {
        runAllValidationsReport();
    }
    if (typeof shortenTextWithTooltip === 'function') {
        shortenTextWithTooltip('.shorten-text', 50);
    }
}

// Khởi tạo khi validation files đã load hoặc khi DOM ready
if (window.validationFilesLoaded) {
    $(document).ready(initListWarranty);
} else {
    $(document).on('validation:loaded', initListWarranty);
    $(document).ready(function() {
        // Fallback: nếu event không trigger, thử lại sau 1 giây
        setTimeout(function() {
            if (window.validationFilesLoaded) {
                initListWarranty();
            }
        }, 1000);
    });
}

// Button Xuất Excel
$('#exportExcel').on('click', function(e) {
    e.preventDefault();
    const params = window.exportParams || {};
    const url = `${window.exportRoute || ''}?${new URLSearchParams(params)}`;
    
    downloadFileWithCooldown(url, {
        defaultFilename: 'bao_cao_bao_hanh.xlsx',
        acceptHeader: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        cooldownKey: 'lastExportTimestamp_reportWarranty',
        cooldownPeriodMs: 2 * 60 * 1000 // 2 phút
    });
});

// Gợi ý linh kiện thay thế (client-side)
function initAutoComplete() {
    const replacementList = window.replacementList || [];
    if (replacementList.length > 0 && typeof setupClientAutoComplete === 'function') {
        setupClientAutoComplete('#replacement', '#replacement-suggestions', replacementList, 'product_name', 10);
    }

    // Ẩn suggestion box khi click ra ngoài
    if (typeof setupClickOutsideToHide === 'function') {
        setupClickOutsideToHide([
            { input: '#replacement', suggestion: '#replacement-suggestions' },
            { input: '#product', suggestion: '#suggestions-product-name' },
            { input: '#staff_received', suggestion: '#suggestions-product-staff' }
        ]);
    }
}

if (window.validationFilesLoaded) {
    $(document).ready(initAutoComplete);
} else {
    $(document).on('validation:loaded', initAutoComplete);
    $(document).ready(function() {
        setTimeout(function() {
            if (window.validationFilesLoaded) {
                initAutoComplete();
            }
        }, 1000);
    });
}

/**
 * NOTE: Các hàm validate đã được di chuyển sang /js/validate_input/report.js
 * Các hàm showError, hideError đã được di chuyển sang /js/validate_input/helpers.js
 * Vui lòng đảm bảo các file sau được load trước file này:
 * - /js/validate_input/helpers.js
 * - /js/validate_input/report.js
 */

// Validation form
let validationErrors = {};

// Hàm cập nhật trạng thái nút "Lọc" VÀ "Xuất Excel"
function updateButtonState() {
    let hasErrors = Object.keys(validationErrors).length > 0;
    $('#btnSearch').prop('disabled', hasErrors);
    $('#exportExcel').toggleClass('disabled', hasErrors);
}

// 7. Gắn sự kiện
$(document).ready(function() {
    // Gắn sự kiện validation cho các trường input
    $('#product').on('input', validateProductReport);
    $('#replacement').on('input', validateReplacement);
    $('#staff_received').on('input', validateStaff);
    $('#fromDate, #toDate').on('change', validateDatesReport);
    // Xử lý khi submit form
    $('form').on('submit', function(e) {
        // Chạy tất cả các hàm validation một lần cuối
        runAllValidationsReport();
        // Kiểm tra lại cờ lỗi tổng thể
        if (Object.keys(validationErrors).length > 0) {
            e.preventDefault(); // Ngăn form submit
            // Focus vào ô lỗi đầu tiên để người dùng dễ sửa
            const firstErrorId = Object.keys(validationErrors)[0];
            $('#' + firstErrorId).focus();
            if (typeof toastr !== 'undefined') {
                toastr.error('Vui lòng kiểm tra lại các thông tin đã nhập.', 'Dữ liệu không hợp lệ');
            }
            return false;
        }
        // Nếu không có lỗi, form sẽ được submit bình thường
    });
});