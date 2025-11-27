// Chức năng tìm kiếm và gợi ý sản phẩm

// Gợi ý sản phẩm cho ô tìm kiếm chính
function setupMainProductSearch() {
    $('#tensp').off('input');
    $(document).off('mousedown', '#tensp-suggestions button');
    
    $('#tensp').on('input', function() {
        if (typeof validateTensp === 'function') {
            validateTensp(); 
        }

        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#tensp-suggestions');
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        if (!mainProductList || mainProductList.length === 0) {
            return;
        }

        const matchedProducts = mainProductList.filter(productName =>
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

    $(document).off('mousedown', '#tensp-suggestions button').on('mousedown', '#tensp-suggestions button', function() {
        $('#tensp').val($(this).text());
        $('#tensp-suggestions').addClass('d-none');
        if (typeof validateTensp === 'function') {
            validateTensp(); 
        }
    });
}

// Search functionality
function Search() {
    $('#searchCard').off('click').on('click', function(e){
        if (!validateSearchForm()) {
            e.preventDefault();
            return;
        }
        
        const sophieu = $('#sophieu').val();
        const tensp = $('#tensp').val();
        const tungay = $('#tungay').val();
        const denngay = $('#denngay').val();

        OpenWaitBox();
        $.get(routes.search, { 
            sophieu: sophieu, 
            tensp: tensp, 
            tungay: tungay, 
            denngay: denngay 
        })
        .done(function(html) {
            $('#tableContent').html(html);
            if (typeof initTableBody === 'function') {
                initTableBody();
            } else if (typeof loadTableBodyScript === 'function') {
                loadTableBodyScript();
            }
        })
        .fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Không thể tải dữ liệu, vui lòng thử lại.'
            });
        })
        .always(function() {
            CloseWaitBox();
        });
    });
}

// Xóa bộ lọc tìm kiếm
function setupResetFilters() {
    $('#resetFilters').off('click').on('click', function() {
        // Reset các input
        $('#sophieu').val('');
        $('#tensp').val('');
        $('#tungay').val('');
        $('#denngay').val('');
        $('#tensp-suggestions').addClass('d-none').empty();
        
        // Xóa trạng thái lỗi (nếu có)
        if (typeof hideError === 'function') {
            hideError($('#sophieu'));
            hideError($('#tensp'));
            hideError($('#tungay'));
            hideError($('#denngay'));
        }
        
        // Load lại bảng dữ liệu
        OpenWaitBox();
        $.get(routes.partial)
            .done(function(html) {
                $('#tableContent').html(html);
                if (typeof initTableBody === 'function') {
                    initTableBody();
                } else if (typeof loadTableBodyScript === 'function') {
                    loadTableBodyScript();
                }
            })
            .fail(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể tải dữ liệu, vui lòng thử lại.'
                });
            })
            .always(function() {
                CloseWaitBox();
            });
    });
}

