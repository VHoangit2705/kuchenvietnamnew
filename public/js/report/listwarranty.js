function resizeTableContainer() {
    const windowHeight = $(window).height();
    const containerHeight = $('.container').outerHeight(true); // bao gồm margin
    const newHeight = windowHeight - containerHeight;
    $('.table-container').height(newHeight - 10);
}

// Ẩn dữ liệu khi quá dài và hover hiện tooltip
function shortenTextWithTooltip() {
    document.addEventListener("DOMContentLoaded", function() {
        const cells = document.querySelectorAll('.shorten-text');
        cells.forEach(cell => {
            const originalText = cell.textContent.trim();
            if (originalText.length > 50) {
                const words = originalText.split(' ');
                let shortText = '';
                let count = 0;
                for (let word of words) {
                    if ((shortText + word).length > 50) break;
                    shortText += word + ' ';
                }
                shortText = shortText.trim() + '...';
                cell.textContent = shortText;
                cell.setAttribute('title', originalText);
                cell.setAttribute('data-bs-toggle', 'tooltip');
            }
        });
        // Kích hoạt tooltip của Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
}

// Đợi validation files load xong trước khi khởi tạo
function initListWarranty() {
    resizeTableContainer();
    shortenTextWithTooltip();
    // limitButtonClicks('btnsearch', 6);
    if (typeof validateDatesReport === 'function') {
        validateDatesReport();
    }
    if (typeof runAllValidationsReport === 'function') {
        runAllValidationsReport();
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
    //Ngăn xuất liên tục trong 2 phút
    const COOLDOWN_PERIOD_MS = 2 * 60 * 1000; // 2 phút
    const LAST_EXPORT_KEY = 'lastExportTimestamp_reportWarranty';
    const lastExportTime = localStorage.getItem(LAST_EXPORT_KEY);
    const currentTime = Date.now();
    if (lastExportTime) {
        const timeDiff = currentTime - parseInt(lastExportTime, 10);
        if (timeDiff < COOLDOWN_PERIOD_MS) {
            const timeLeftSeconds = Math.ceil((COOLDOWN_PERIOD_MS - timeDiff) / 1000);
            const minutes = Math.floor(timeLeftSeconds / 60);
            const seconds = timeLeftSeconds % 60;
            Swal.fire({
                icon: 'warning',
                title: 'Vui lòng chờ',
                text: `Bạn vừa xuất file. Vui lòng chờ ${minutes} phút ${seconds} giây trước khi xuất tiếp.`,
            });
            return; // Dừng thực thi
        }
    }
    Swal.fire({
        title: 'Đang xuất file...',
        text: 'Vui lòng chờ trong giây lát',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    const params = window.exportParams || {};

    fetch(`${window.exportRoute || ''}?${new URLSearchParams(params)}`)
        .then(response => {
            Swal.close();
            const contentType = response.headers.get("Content-Type");
            if (contentType && contentType.includes("application/json")) {
                return response.json().then(json => {
                    Swal.fire({
                        icon: 'error',
                        text: json.message
                    });
                });
            } else {
                return response.blob().then(blob => {
                    // Chỉ lưu timestamp khi tải file thành công
                    localStorage.setItem(LAST_EXPORT_KEY, currentTime.toString());
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = "bao_cao_bao_hanh.xlsx";
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                icon: 'error',
                text: 'Lỗi server.'
            });
            console.error(error);
        })
});

// Gợi ý linh kiện thay thế (client-side)
function initAutoComplete() {
    const replacementList = window.replacementList || [];
    
    $('#replacement').on('input', function() {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#replacement-suggestions');
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }
        const matchedReplacement = replacementList.filter(p =>
            p.product_name.toLowerCase().includes(keyword)
        );

        if (matchedReplacement.length > 0) {
            matchedReplacement.slice(0, 10).forEach(p => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    $(document).on('mousedown', '#replacement-suggestions button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#replacement').val($(this).text());
        $('#replacement-suggestions').addClass('d-none').empty();
    });

    $(document).on('click', function(e) {
        // Logic cho ô linh kiện (client-side)
        if (!$(e.target).closest('#replacement, #replacement-suggestions').length) {
            $('#replacement-suggestions').addClass('d-none').empty();
        }
    });
}

$(document).ready(function() {
    initAutoComplete();
});

/**
 * NOTE: Các hàm validate đã được di chuyển sang /js/validate_input/report.js
 * Các hàm showError, hideError đã được di chuyển sang /js/validate_input/helpers.js
 * Vui lòng đảm bảo các file sau được load trước file này:
 * - /js/validate_input/helpers.js
 * - /js/validate_input/report.js
 * 
 * validationErrors đã được khai báo trong /js/validate_input/report.js
 * Không cần khai báo lại ở đây
 */

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
            Swal.fire({
                icon: 'error',
                title: 'Lỗi dữ liệu',
                text: 'Vui lòng kiểm tra lại các trường nhập liệu.',
            });
            return false;
        }
        // Nếu không có lỗi, form sẽ được submit bình thường
    });
});