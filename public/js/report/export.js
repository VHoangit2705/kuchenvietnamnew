$(document).on('click', '#exportExcel', function(e) {
    e.preventDefault();
    
    // Lấy dữ liệu bộ lọc hiện tại từ form
    const form = document.getElementById('reportFilterForm');
    const params = Object.assign({}, (window.reportParams || {}));
    if (form) {
        const formData = new FormData(form);
        formData.forEach((value, key) => {
            params[key] = value;
        });
    }
    
    // Mở preview modal
    params.embed = '1';
    const queryParams = new URLSearchParams(params);
    const previewUrl = (window.previewReportRoute || '') + '?' + queryParams.toString();
    const $iframe = $('#previewModal iframe');
    const $spinner = $('#previewModal .preview-loading');
    $spinner.removeClass('d-none');
    $iframe.addClass('d-none');
    $iframe.off('load').on('load', function() {
        $spinner.addClass('d-none');
        $iframe.removeClass('d-none');
    });
    $iframe.attr('src', previewUrl);
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
});

// Xử lý khi đóng modal
$(document).on('hidden.bs.modal', '#previewModal', function() {
    $('#previewModal iframe').attr('src', '');
    $('#previewModal .preview-loading').removeClass('d-none');
    $('#previewModal iframe').addClass('d-none');
});


