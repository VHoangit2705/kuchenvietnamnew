/**
 * Trang tra cứu tài liệu kỹ thuật (index)
 * Cần có window.technicalDocumentIndexConfig trước khi load:
 * { routes: { getProductsByCategory, getOriginsByProduct, getModelsByOrigin } }
 */
(function () {
    'use strict';

    var config = window.technicalDocumentIndexConfig || {};
    var routes = config.routes || {};

    function resetSelect(id, placeholder, disable) {
        if (disable === undefined) disable = true;
        jQuery(id).html('<option value="">' + placeholder + '</option>').prop('disabled', disable);
    }

    function init() {
        // 1. Chọn danh mục → load sản phẩm
        jQuery('#categorySelect').on('change', function () {
            var categoryId = jQuery(this).val();

            resetSelect('#productNameSelect', '-- Chọn sản phẩm --', true);
            resetSelect('#originSelect', '-- Chọn xuất xứ --', true);
            resetSelect('#productCodeSelect', '-- Chọn mã SP --', true);
            jQuery('#btnSearch').prop('disabled', true);

            if (!categoryId) return;

            jQuery.get(routes.getProductsByCategory || '', { category_id: categoryId }, function (res) {
                resetSelect('#productNameSelect', '-- Chọn sản phẩm --', false);
                (res || []).forEach(function (p) {
                    jQuery('#productNameSelect').append(
                        '<option value="' + p.id + '">' + (p.name || p.product_name || '') + (p.model ? ' (' + p.model + ')' : '') + '</option>'
                    );
                });
            }).fail(function () {
                resetSelect('#productNameSelect', '-- Chọn sản phẩm --', false);
            });
        });

        // 2. Chọn sản phẩm → load xuất xứ
        jQuery('#productNameSelect').on('change', function () {
            var productId = jQuery(this).val();

            resetSelect('#originSelect', '-- Chọn xuất xứ --', true);
            resetSelect('#productCodeSelect', '-- Chọn mã SP --', true);
            jQuery('#btnSearch').prop('disabled', true);

            if (!productId) return;

            jQuery.get(routes.getOriginsByProduct || '', { product_id: productId }, function (res) {
                resetSelect('#originSelect', '-- Chọn xuất xứ --', false);
                (res || []).forEach(function (o) {
                    jQuery('#originSelect').append(
                        '<option value="' + (o.xuat_xu || '') + '">' + (o.xuat_xu || '') + '</option>'
                    );
                });
            }).fail(function () {
                resetSelect('#originSelect', '-- Chọn xuất xứ --', false);
            });
        });

        // 3. Chọn xuất xứ → load mã sản phẩm (model)
        jQuery('#originSelect').on('change', function () {
            var productId = jQuery('#productNameSelect').val();
            var origin = jQuery(this).val();

            resetSelect('#productCodeSelect', '-- Chọn mã SP --', true);
            jQuery('#btnSearch').prop('disabled', true);

            if (!origin) return;

            jQuery.get(routes.getModelsByOrigin || '', {
                product_id: productId,
                xuat_xu: origin
            }, function (res) {
                resetSelect('#productCodeSelect', '-- Chọn mã SP --', false);
                (res || []).forEach(function (m) {
                    jQuery('#productCodeSelect').append(
                        '<option value="' + m.id + '">' + (m.model_code || '') + (m.version ? ' (' + m.version + ')' : '') + '</option>'
                    );
                });
            }).fail(function () {
                resetSelect('#productCodeSelect', '-- Chọn mã SP --', false);
            });
        });

        // 4. Chọn mã SP → enable tìm kiếm
        jQuery('#productCodeSelect').on('change', function () {
            jQuery('#btnSearch').prop('disabled', !jQuery(this).val());
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
