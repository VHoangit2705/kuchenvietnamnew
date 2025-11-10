/**
 * Quản lý tab và load dữ liệu
 */

// Đặt loadTabData và loadCounts ở global scope
window.loadTabData = function(tab, formData, page = 1) {
    let url = window.ROUTES.dieuphoi_tabdata || '/dieuphoi/tabdata';
    url += "?tab=" + tab + "&page=" + page;
    if (formData) {
        url += "&" + formData;
    }
    
    $('#tabContent').html('<div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.get(url, function(response) {
        if (response && response.table) {
            $('#tabContent').html(response.table);
            localStorage.setItem('activeTab', tab);
            
            // Highlight tab active
            $('#collaborator_tab .nav-link').removeClass('active');
            $('#collaborator_tab .nav-link[data-tab="' + tab + '"]').addClass('active');
        }
    }).fail(function() {
        $('#tabContent').html('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu!</div>');
    });
};

window.loadCounts = function(formData, callback, renderHeader) {
    let url = window.ROUTES.dieuphoi_counts || '/dieuphoi/counts';
    if (formData) {
        url += "?" + formData;
    }
    
    // Hiển thị hiệu ứng loading cho tất cả count badges
    $('.count-badge').each(function() {
        const $badge = $(this);
        const originalText = $badge.text();
        $badge.data('original-text', originalText).html('<span class="spinner-border spinner-border-sm" style="width: 0.75rem; height: 0.75rem;" role="status"><span class="visually-hidden">Loading...</span></span>');
    });
    
    $.get(url, function(counts) {
        if (counts) {
            // Nếu renderHeader = true, render lại toàn bộ tab header
            if (renderHeader === true) {
                const activeTab = localStorage.getItem('activeTab') || 'donhang';
                renderTabHeader(counts, activeTab);
            } else {
                // Chỉ cập nhật counts cho từng tab bằng vòng lặp
                Object.keys(counts).forEach(function(tabKey) {
                    $('.count-badge[data-count-for="' + tabKey + '"]').text('(' + (counts[tabKey] || 0) + ')');
                });
                
                // Đảm bảo các badge không có trong response vẫn được khôi phục giá trị cũ
                $('.count-badge').each(function() {
                    const $badge = $(this);
                    // Kiểm tra nếu badge vẫn chứa spinner (nghĩa là chưa được cập nhật)
                    if ($badge.find('.spinner-border').length > 0) {
                        const tabKey = $badge.data('count-for');
                        // Nếu tabKey không có trong counts, khôi phục giá trị cũ
                        if (!counts.hasOwnProperty(tabKey)) {
                            const originalText = $badge.data('original-text');
                            $badge.text(originalText || '(0)');
                        }
                    }
                });
            }
            
            if (typeof callback === 'function') {
                callback(counts);
            }
        }
    }).fail(function() {
        // Nếu load thất bại, khôi phục text gốc cho tất cả badges
        $('.count-badge').each(function() {
            const $badge = $(this);
            const originalText = $badge.data('original-text');
            if (originalText) {
                $badge.text(originalText);
            } else {
                $badge.text('(0)');
            }
        });
    });
};

// Hàm render lại tab header từ counts (KHÔNG dùng API)
// Render trực tiếp bằng JavaScript dựa trên counts và activeTab
window.renderTabHeader = function(counts, activeTab) {
    activeTab = activeTab || localStorage.getItem('activeTab') || 'donhang';
    counts = counts || {};
    
    // Định nghĩa danh sách các tab
    const tabs = [
        { key: 'donhang', label: 'ĐƠN HÀNG' },
        { key: 'dieuphoidonhangle', label: 'ĐƠN HÀNG LẺ' },
        { key: 'dieuphoibaohanh', label: 'CA BẢO HÀNH' },
        { key: 'dadieuphoi', label: 'ĐÃ ĐIỀU PHỐI' },
        { key: 'dahoanthanh', label: 'ĐÃ HOÀN THÀNH' },
        { key: 'dathanhtoan', label: 'ĐÃ THANH TOÁN' },
        { key: 'dailylapdat', label: 'ĐẠI LÝ LẮP ĐẶT' }
    ];
    
    // Render HTML
    let html = '<ul class="nav nav-tabs flex-nowrap" id="collaborator_tab">';
    
    tabs.forEach(function(tab) {
        const isActive = tab.key === activeTab ? 'active' : '';
        const count = counts[tab.key] || 0;
        
        html += '<li class="nav-item">';
        html += '<a class="nav-link ' + isActive + '" data-tab="' + tab.key + '" href="#">';
        html += tab.label + ' <span class="text-danger count-badge" data-count-for="' + tab.key + '">(' + count + ')</span>';
        html += '</a>';
        html += '</li>';
    });
    
    html += '</ul>';
    
    // Cập nhật HTML vào container
    $('#tabHeaderContainer').html(html);
};

