/**
 * Product autocomplete and selection logic for formwarranty
 */

$(document).ready(function () {
    const productList = window.productListAll || [];
    
    $('#product').on('input', function () {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#product-suggestions');
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        const matchedProducts = productList.filter(p =>
            p.product_name.toLowerCase().includes(keyword)
        );

        if (matchedProducts.length > 0) {
            matchedProducts.slice(0, 10).forEach(p => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action" data-product-id="${p.check_seri || 0}">${p.product_name}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    // Khi người dùng chọn sản phẩm gợi ý
    $(document).on('mousedown', '#product-suggestions button', function () {
        $('#product').val($(this).text());
        const productId = $(this).data('product-id');
        if(productId == 1) {
            $('#serialthanmayGroup').removeClass('d-none');
        } else {
            $('#serialthanmayGroup').addClass('d-none');
        }
        $('#product-suggestions').addClass('d-none');
    });

    $('#product').on('blur', function () {
        const inputVal = $(this).val().trim().replace(/\r?\n|\r/g, '');
        const matchedProduct = productList.find(product =>
            product.product_name.trim().replace(/\r?\n|\r/g, '') === inputVal
        );
        if (!matchedProduct && inputVal !== '') {
            $('#product').val('');
            showSwalMessage('warning', 'Sản phẩm cũ không có trong hệ thống .Vui lòng liên hệ quản trị viên CNTT để được hỗ trợt.', '', {
                timer: 1500
            });
        }
        if(matchedProduct && matchedProduct.check_seri == 1) {
            $('#serialthanmayGroup').removeClass('d-none');
        } else {
            $('#serialthanmayGroup').addClass('d-none');
        }
    });

    // Ẩn gợi ý khi click ra ngoài
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#product, #product-suggestions').length) {
            $('#product-suggestions').addClass('d-none');
        }
    });
    
    // Auto fill vào serial khi chọn từ select
    $('#product').on('change', function () {
        var serial = $(this).find(':selected').data('serial') || '';
        $('#serial_number').val(serial);
    });
});

