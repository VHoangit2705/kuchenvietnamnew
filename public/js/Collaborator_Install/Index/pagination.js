/**
 * Xử lý phân trang
 */

// Xử lý phân trang (khi click pagination link)
function initPagination() {
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        let page = new URL(url).searchParams.get('page') || 1;
        let tab = localStorage.getItem('activeTab') || 'donhang';
        let formData = $('#searchForm').serialize();
        
        loadTabData(tab, formData, page);
    });
}

