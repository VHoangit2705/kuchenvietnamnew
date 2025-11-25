// Function để load dữ liệu report qua AJAX
function loadReportData(tab, formData) {
    let url = window.reportRoute || window.location.pathname;
    url += '?tab=' + tab + '&' + formData;
    
    // Show loading indicator
    $('#reportTableContent').html('<tr><td colspan="15" class="text-center">Đang tải...</td></tr>');
    
    $.ajax({
        url: url,
        type: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            if (typeof response === 'object' && response.tab && response.table) {
                // Update tab header
                $('#reportTabHeader').html(response.tab);
                
                // Update table content (replace thead and tbody inside table)
                $('#reportTableContent').html(response.table);
                
                // Update reportType filter nếu có
                if (response.filter) {
                    $('#reportTypeFilterWrapper').html(response.filter);
                } else {
                    // Nếu không có filter (tab khác), xóa filter
                    $('#reportTypeFilterWrapper').html('');
                }
                
                // Highlight active tab
                $('#warrantyTabs .nav-link').removeClass('active');
                $('#warrantyTabs .nav-link[data-tab="' + tab + '"]').addClass('active');
                
                // Reinitialize tooltips and resize
                setTimeout(function() {
                    initTooltipsAndResize();
                }, 100);
                
                // Update URL without reload
                let currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('tab', tab);
                window.history.pushState({}, '', currentUrl.href);
            }
        },
        error: function() {
            $('#reportTableContent').html('<tr><td colspan="15" class="text-center text-danger">Có lỗi xảy ra khi tải dữ liệu</td></tr>');
        }
    });
}

// Xử lý reset filters
$(document).on('click', '#resetFiltersReport', function(e) {
    e.preventDefault();
    
    // Xóa trạng thái lỗi hiện tại (nếu có)
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    
    // Reset form fields
    $('#reportFilterForm')[0].reset();
    
    // Lấy tab hiện tại hoặc mặc định là 'warranty'
    let activeTab = $('#warrantyTabs .nav-link.active').data('tab') || 'warranty';
    
    // Reset date fields về mặc định (đầu tháng đến hôm nay)
    let today = new Date();
    let firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    let fromDate = firstDay.toISOString().split('T')[0];
    let toDate = today.toISOString().split('T')[0];
    
    $('#fromDate').val(fromDate);
    $('#toDate').val(toDate);
    
    // Update hidden input
    $('#activeTabInput').val(activeTab);
    
    // Load dữ liệu với filters đã reset
    let formData = $('#reportFilterForm').serialize();
    loadReportData(activeTab, formData);
});


