/**
 * Technical Document Filter Module
 * Handles cascading dropdowns for Category -> Product -> Origin -> Model
 */

window.TechnicalDocumentFilter = (function () {
    'use strict';

    let config = {
        routes: {},
        selectors: {
            category: '#filterCategory',
            product: '#filterProduct',
            origin: '#filterOrigin',
            model: '#filterModel'
        },
        currentModelId: null,
        filter: {}
    };

    function init(routes, options = {}) {
        config.routes = routes;
        config.currentModelId = options.currentModelId || null;
        config.filter = options.filter || {};
        
        if (options.selectors) {
            Object.assign(config.selectors, options.selectors);
        }

        attachEventListeners();
        initializeFilter();
    }

    function loadProducts(categoryId, selectProductId) {
        if (!categoryId) {
            jQuery(config.selectors.product).html('<option value="">Chọn sản phẩm</option>').prop('disabled', true);
            jQuery(config.selectors.origin + ', ' + config.selectors.model).html('<option value="">...</option>').prop('disabled', true);
            return;
        }

        jQuery.get(config.routes.getProductsByCategory, { category_id: categoryId }, function (res) {
            let opts = '<option value="">Chọn sản phẩm</option>';
            (res || []).forEach(function (p) {
                let sel = (selectProductId && p.id == selectProductId) ? ' selected' : '';
                opts += '<option value="' + p.id + '"' + sel + '>' + (p.name || p.product_name || '') + '</option>';
            });
            jQuery(config.selectors.product).html(opts).prop('disabled', false);
            jQuery(config.selectors.origin).html('<option value="">Xuất xứ</option>').prop('disabled', true);
            jQuery(config.selectors.model).html('<option value="">Chọn model</option>').prop('disabled', true);
            if (selectProductId) loadOrigins(selectProductId, config.filter.xuat_xu);
        });
    }

    function loadOrigins(productId, selectOrigin) {
        if (!productId) {
            jQuery(config.selectors.origin).html('<option value="">Xuất xứ</option>').prop('disabled', true);
            jQuery(config.selectors.model).html('<option value="">Chọn model</option>').prop('disabled', true);
            return;
        }

        jQuery.get(config.routes.getOriginsByProduct, { product_id: productId }, function (res) {
            let opts = '<option value="">Xuất xứ</option>';
            
            // Check if no origins found or all origins are empty
            if (!res || res.length === 0) {
                opts = '<option value="">Không tìm thấy xuất xứ của sản phẩm</option>';
                jQuery(config.selectors.origin).html(opts).prop('disabled', true);
                jQuery(config.selectors.model).html('<option value="">Chọn model</option>').prop('disabled', true);
                return;
            }
            
            (res || []).forEach(function (o) {
                let x = o.xuat_xu || '';
                if (x) { // Only add non-empty origins
                    let sel = (selectOrigin && x === selectOrigin) ? ' selected' : '';
                    opts += '<option value="' + x + '"' + sel + '>' + x + '</option>';
                }
            });
            
            // Check if no valid origins were added
            if (opts === '<option value="">Xuất xứ</option>') {
                opts = '<option value="">Không tìm thấy xuất xứ của sản phẩm</option>';
                jQuery(config.selectors.origin).html(opts).prop('disabled', true);
            } else {
                jQuery(config.selectors.origin).html(opts).prop('disabled', false);
            }
            
            jQuery(config.selectors.model).html('<option value="">Chọn model</option>').prop('disabled', true);
            if (selectOrigin) loadModels(productId, selectOrigin);
        });
    }

    function loadModels(productId, origin) {
        if (!productId || !origin) {
            jQuery(config.selectors.model).html('<option value="">Chọn model</option>').prop('disabled', true);
            return;
        }

        jQuery.get(config.routes.getModelsByOrigin, { product_id: productId, xuat_xu: origin }, function (res) {
            let opts = '<option value="">Chọn model</option>';
            (res || []).forEach(function (m) {
                let sel = (config.currentModelId && m.id == config.currentModelId) ? ' selected' : '';
                opts += '<option value="' + m.id + '"' + sel + '>' + (m.model_code || '') + (m.version ? ' (' + m.version + ')' : '') + '</option>';
            });
            jQuery(config.selectors.model).html(opts).prop('disabled', false);
        });
    }

    function attachEventListeners() {
        jQuery(config.selectors.category).on('change', function () {
            loadProducts(jQuery(this).val());
        });

        jQuery(config.selectors.product).on('change', function () {
            loadOrigins(jQuery(this).val());
        });

        jQuery(config.selectors.origin).on('change', function () {
            loadModels(jQuery(config.selectors.product).val(), jQuery(this).val());
        });
    }

    function initializeFilter() {
        let cat = jQuery(config.selectors.category).val();
        if (cat) {
            if (config.filter.product_id) {
                loadProducts(cat, config.filter.product_id);
            } else {
                loadProducts(cat);
            }
        }
    }

    return {
        init: init,
        loadProducts: loadProducts,
        loadOrigins: loadOrigins,
        loadModels: loadModels
    };
})();
