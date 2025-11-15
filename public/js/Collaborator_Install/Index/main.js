/**
 * File chính kết nối tất cả các module của index
 */

$(document).ready(function() {
    // Load counts và tab data khi trang mở
    const serverTab = window.SERVER_TAB || 'donhang';
    const activeTab = serverTab || 'donhang';
    
    localStorage.setItem('activeTab', activeTab);
    
    const formData = $('#searchForm').serialize();
    
    // Load counts trước
    loadCounts(formData);
    
    // Sau đó load tab data
    loadTabData(activeTab, formData, 1);

    // Xử lý click tab 
    $(document).on('click', '#collaborator_tab .nav-link', function(e) {
        e.preventDefault();
        // Nếu đang ở tab active thì bỏ qua, không load lại
        // if ($(this).hasClass('active')) {
        //     return;
        // }
        let tab = $(this).data('tab');
        let formData = $('#searchForm').serialize();
        loadTabData(tab, formData, 1);
    });

    // Khởi tạo validation
    initFormValidations();

    //Phần đánh dấu 1 hàng trong bảng
    $('#tabContent').on('click', 'tbody tr', function() {
        const isHighlighted = $(this).hasClass('highlight-row');
        $('tbody tr').removeClass('highlight-row');
        if (!isHighlighted) {
            $(this).addClass('highlight-row');
        }
    });

    // Khởi tạo gợi ý sản phẩm
    initProductSuggestions();

    // Khởi tạo form tìm kiếm
    initSearchForm();

    // Khởi tạo phân trang
    initPagination();

    // Khởi tạo báo cáo
    Report();

    // Khởi tạo kéo bảng
    initTableDrag();

    // Khởi tạo upload Excel
    initExcelUpload();

    // Khởi tạo preview modal
    initPreviewModal();
});

