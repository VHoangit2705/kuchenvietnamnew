/**
 * Xử lý tìm kiếm và sản phẩm gợi ý
 */

// Dữ liệu sản phẩm từ server
function initProductSuggestions() {
    const productList = window.PRODUCT_LIST || [];

    $('#sanpham').on('input', function() {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#sanpham-suggestions');
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        const matchedProducts = productList.filter(productName =>
            productName.toLowerCase().includes(keyword)
        );

        if (matchedProducts.length > 0) {
            matchedProducts.slice(0, 10).forEach(productName => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${productName}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    $(document).on('mousedown', '#sanpham-suggestions button', function(e) {
        e.preventDefault();
        $('#sanpham').val($(this).text());
        $('#sanpham-suggestions').addClass('d-none');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#sanpham, #sanpham-suggestions').length) {
            $('#sanpham-suggestions').addClass('d-none');
        }
    });
}

// Xử lý form search
function initSearchForm() {
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate ngày tháng trước khi submit
        if (!validateDates()) {
            return; // Dừng lại nếu validation fail
        }
        
        let tab = localStorage.getItem('activeTab') || 'donhang';
        let formData = $(this).serialize();
        
        // Tự động chuyển đến tab tương ứng với trạng thái đã chọn
        const selectedStatus = $('#trangthai').val();
        if (selectedStatus !== '') {
            // Mapping trạng thái với tab
            const statusToTabMap = {
                '0': 'donhang',           
                '1': 'dadieuphoi',        
                '2': 'dahoanthanh',       
                '3': 'dathanhtoan'        
            };
            
            if (statusToTabMap.hasOwnProperty(selectedStatus)) {
                tab = statusToTabMap[selectedStatus];
                localStorage.setItem('activeTab', tab);
                
                // Cập nhật active state cho tab
                $('#collaborator_tab .nav-link').removeClass('active');
                $('#collaborator_tab .nav-link[data-tab="' + tab + '"]').addClass('active');
            }
        }
        
        // Load lại counts và tab data
        loadCounts(formData);
        loadTabData(tab, formData, 1);
    });
}

// Hàm xóa bộ lọc
function clearForm() {
    // Reset form
    $('#searchForm')[0].reset();
    // Reset các select về giá trị mặc định
    $('#trangthai').val('');
    $('#phanloai').val('');
    // Đảm bảo input date cũng được reset
    $('#tungay').val('');
    $('#denngay').val('');

    // Xóa tất cả các class 'is-invalid'
    $('#searchForm .is-invalid').removeClass('is-invalid');

    // Trả về nút tìm kiếm về trạng thái ban đầu (enabled)
    $('#btnSearch').prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');

    // Kiểm tra lại validation để đảm bảo logic đúng
    if (typeof window.checkFormValidity === 'function') {
        window.checkFormValidity();
    }

    // Reload dữ liệu với form trống
    const tab = localStorage.getItem('activeTab') || 'donhang';
    // Serialize lại form sau khi reset để đảm bảo formData rỗng
    const formData = $('#searchForm').serialize();
    
    // Load lại counts (giữ nguyên tab thẻ, chỉ cập nhật số counts)
    loadCounts(formData);
    
    // Load lại tab content
    loadTabData(tab, formData, 1);
}

