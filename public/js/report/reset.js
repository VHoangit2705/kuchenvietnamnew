$(document).on('click', '#resetFiltersReport', function(e) {
    e.preventDefault();
    // Xóa toàn bộ query params bằng cách điều hướng về path hiện tại (GET)
    const baseAction = window.location.pathname;
    // Xóa trạng thái lỗi hiện tại (nếu có)
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    // Chuyển hướng để tải lại dữ liệu mặc định
    window.location.href = baseAction;
});


