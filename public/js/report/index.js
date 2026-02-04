$(document).ready(function() {
    initTooltipsAndResize();
    initReplacementSuggestions();
    initReportValidation();
    initTabSwitching();
    initFormSubmit();
});

function initTabSwitching() {
    // Xử lý click tab
    $(document).on('click', '#warrantyTabs .nav-link', function (e) {
        e.preventDefault();
        let tab = $(this).data('tab');
        // Update hidden input
        $('#activeTabInput').val(tab);
        let formData = $('#reportFilterForm').serialize();
        loadReportData(tab, formData);
    });
}

function initFormSubmit() {
    // Xử lý submit form
    $('#reportFilterForm').on('submit', function (e) {
        e.preventDefault();
        let activeTab = $('#warrantyTabs .nav-link.active').data('tab') || 'warranty';
        // Update hidden input
        $('#activeTabInput').val(activeTab);
        let formData = $(this).serialize();
        loadReportData(activeTab, formData);
    });
    
    // Xử lý filter reportType riêng cho tab work_process
    $(document).on('click', '#btnFilterReportType', function (e) {
        e.preventDefault();
        let reportType = $('#reportType').val() || 'weekly'; // Mặc định là weekly
        let activeTab = 'work_process';
        
        // Lấy tất cả các giá trị từ form chính
        let formData = $('#reportFilterForm').serialize();
        
        // Thêm reportType vào formData
        formData += '&reportType=' + encodeURIComponent(reportType);
        
        // Update hidden input
        $('#activeTabInput').val(activeTab);
        loadReportData(activeTab, formData);
    });
    
    // Hàm cập nhật text tuần/tháng (sẽ được gọi sau khi load dữ liệu)
    function updateReportPeriodInfo() {
        // Text sẽ được cập nhật tự động khi server trả về response.filter
        // Không cần xử lý thêm ở đây
    }
    
    // Xử lý Enter key trong select reportType
    $(document).on('keypress', '#reportType', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#btnFilterReportType').click();
        }
    });
}


