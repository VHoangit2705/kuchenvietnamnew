/**
 * Các hàm dùng chung cho trang quản lý cộng tác viên
 */
(function (window, $) {
    if (!$) return;

    const shared = {
        _csrfSetup: false
    };

    /**
     * Thiết lập CSRF token cho toàn bộ AJAX request (chỉ chạy một lần)
     */
    shared.setupAjaxCsrf = function setupAjaxCsrf() {
        if (shared._csrfSetup) return;
        const token = $('meta[name="csrf-token"]').attr('content');
        if (!token) return;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': token
            }
        });
        shared._csrfSetup = true;
    };

    /**
     * Định dạng ngày thành chuỗi Y-m-d để bind vào input[type="date"]
     */
    shared.formatDateToInput = function formatDateToInput(dateString) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            // Kiểm tra date hợp lệ
            if (Number.isNaN(date.getTime())) return '';
            return date.toISOString().split('T')[0];
        } catch (error) {
            return '';
        }
    };

    window.CollaboratorShared = shared;

    $(document).ready(function () {
        shared.setupAjaxCsrf();
    });
})(window, window.jQuery);

