/**
 * Trang thêm tài liệu kỹ thuật (create)
 * Cần có window.technicalDocumentCreateConfig trước khi load:
 * { routes: { ... }, csrfToken: '...' }
 */
(function () {
    'use strict';

    var config = window.technicalDocumentCreateConfig || {};
    var routes = config.routes || {};
    var csrfToken = config.csrfToken || '';

    var selectedModelId = null;

    function resetSelect(sel, placeholder, disabled) {
        jQuery(sel).html('<option value="">' + placeholder + '</option>').prop('disabled', disabled);
    }

    function loadOriginsByProduct(productId) {
        jQuery.get(routes.getOriginsByProduct || '', { product_id: productId }, function (res) {
            resetSelect('#createOrigin', '-- Chọn xuất xứ --', false);
            (res || []).forEach(function (o) {
                jQuery('#createOrigin').append('<option value="' + (o.xuat_xu || '') + '">' + (o.xuat_xu || '') + '</option>');
            });
        });
    }

    function loadErrorsByModel(modelId) {
        jQuery.get(routes.getErrorsByModel || '', { model_id: modelId }, function (res) {
            var opts = '<option value="">-- Chọn mã lỗi --</option>';
            (res || []).forEach(function (e) {
                opts += '<option value="' + e.id + '">' + (e.error_code || '') + ' - ' + (e.error_name || '') + '</option>';
            });
            jQuery('#createErrorId').html(opts);
            jQuery('#errorList').empty();
            if (res && res.length) {
                res.forEach(function (e) {
                    jQuery('#errorList').append(
                        '<li class="list-group-item d-flex justify-content-between align-items-center">' +
                        '<span><strong>' + (e.error_code || '') + '</strong> ' + (e.error_name || '') + ' <span class="badge bg-secondary">' + (e.severity || 'normal') + '</span></span>' +
                        '</li>'
                    );
                });
            } else {
                jQuery('#errorList').append('<li class="list-group-item text-muted">Chưa có mã lỗi. Bấm "Thêm mã lỗi" ở trên.</li>');
            }
        });
    }

    function showError(xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) ||
            (xhr.responseJSON && xhr.responseJSON.errors ? JSON.stringify(xhr.responseJSON.errors) : null) ||
            'Có lỗi xảy ra.';
        alert(msg);
    }

    function init() {
        // Bước 1: Danh mục → load Sản phẩm
        jQuery('#createCategory').on('change', function () {
            var cid = jQuery(this).val();
            resetSelect('#createProduct', '-- Chọn sản phẩm --', true);
            resetSelect('#createOrigin', '-- Chọn xuất xứ --', true);
            resetSelect('#createModelId', '-- Chọn mã SP --', true);
            jQuery('#blockAfterModel').hide();
            if (!cid) return;
            jQuery.get(routes.getProductsByCategory || '', { category_id: cid }, function (res) {
                resetSelect('#createProduct', '-- Chọn sản phẩm --', false);
                (res || []).forEach(function (p) {
                    jQuery('#createProduct').append('<option value="' + p.id + '">' + (p.name || p.product_name || '') + (p.model ? ' (' + p.model + ')' : '') + '</option>');
                });
            });
        });

        // Bước 2: Sản phẩm → load Xuất xứ, bật nút Thêm xuất xứ
        jQuery('#createProduct').on('change', function () {
            var pid = jQuery(this).val();
            resetSelect('#createOrigin', '-- Chọn xuất xứ --', true);
            resetSelect('#createModelId', '-- Chọn mã SP --', true);
            jQuery('#blockAfterModel').hide();
            jQuery('#originProductId').val(pid || '');
            jQuery('#btnAddOrigin').prop('disabled', !pid);
            if (!pid) return;
            loadOriginsByProduct(pid);
        });

        // Modal: Thêm xuất xứ
        jQuery('#formAddOrigin').on('submit', function (e) {
            e.preventDefault();
            var productId = jQuery('#originProductId').val();
            if (!productId) {
                alert('Vui lòng chọn Sản phẩm trước.');
                return;
            }
            var fd = new FormData(this);
            fd.append('product_id', productId);
            fd.append('_token', csrfToken);
            jQuery.ajax({
                url: routes.storeOrigin || '',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    jQuery('#modalAddOrigin').modal('hide');
                    jQuery('#formAddOrigin')[0].reset();
                    jQuery('#formAddOrigin input[name="product_id"]').val(productId);
                    loadOriginsByProduct(productId);
                    if (res && res.message) alert(res.message);
                },
                error: function (xhr) { showError(xhr); }
            });
        });

        // Bước 3–4: Xuất xứ → load Mã SP
        jQuery('#createOrigin').on('change', function () {
            var pid = jQuery('#createProduct').val();
            var origin = jQuery(this).val();
            resetSelect('#createModelId', '-- Chọn mã SP --', true);
            jQuery('#blockAfterModel').hide();
            if (!origin) return;
            jQuery.get(routes.getModelsByOrigin || '', { product_id: pid, xuat_xu: origin }, function (res) {
                resetSelect('#createModelId', '-- Chọn mã SP --', false);
                (res || []).forEach(function (m) {
                    jQuery('#createModelId').append('<option value="' + m.id + '">' + (m.model_code || '') + (m.version ? ' (' + m.version + ')' : '') + '</option>');
                });
            });
        });

        // Bước 4: Chọn Model → hiện block thêm lỗi & hướng dẫn, load danh sách lỗi
        jQuery('#createModelId').on('change', function () {
            selectedModelId = jQuery(this).val();
            if (!selectedModelId) {
                jQuery('#blockAfterModel').hide();
                return;
            }
            jQuery('#blockAfterModel').show();
            loadErrorsByModel(selectedModelId);
        });

        // Modal: Thêm mã lỗi (Bước 5)
        jQuery('#formAddError').on('submit', function (e) {
            e.preventDefault();
            if (!selectedModelId) {
                alert('Vui lòng chọn Mã sản phẩm (Model) trước.');
                return;
            }
            var fd = new FormData(this);
            fd.append('model_id', selectedModelId);
            fd.append('_token', csrfToken);
            jQuery.ajax({
                url: routes.storeError || '',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function () {
                    jQuery('#modalAddError').modal('hide');
                    jQuery('#formAddError')[0].reset();
                    loadErrorsByModel(selectedModelId);
                },
                error: function (xhr) { showError(xhr); }
            });
        });

        // Chọn mã lỗi → gán vào form hướng dẫn
        jQuery('#createErrorId').on('change', function () {
            jQuery('#guideErrorId').val(jQuery(this).val());
        });

        // Bước 6–7: Lưu hướng dẫn + tài liệu
        jQuery('#guideForm').on('submit', function (e) {
            e.preventDefault();
            var errorId = jQuery('#guideErrorId').val() || jQuery('#createErrorId').val();
            if (!errorId) {
                alert('Vui lòng chọn mã lỗi.');
                return;
            }
            var fd = new FormData();
            fd.append('error_id', errorId);
            fd.append('title', jQuery('#guideTitle').val());
            fd.append('steps', jQuery('#guideSteps').val());
            fd.append('estimated_time', jQuery('#guideEstimatedTime').val() || 0);
            fd.append('safety_note', jQuery('#guideSafetyNote').val());
            fd.append('_token', csrfToken);
            var files = document.getElementById('docFiles').files;
            for (var i = 0; i < files.length; i++) {
                fd.append('files[]', files[i]);
            }
            jQuery.ajax({
                url: routes.storeRepairGuide || '',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res && res.message) alert(res.message);
                    jQuery('#guideForm')[0].reset();
                    jQuery('#guideErrorId').val('');
                    document.getElementById('docFiles').value = '';
                    jQuery('#uploadedDocList').empty();
                    loadErrorsByModel(selectedModelId);
                },
                error: function (xhr) { showError(xhr); }
            });
        });

        jQuery('#btnResetGuide').on('click', function () {
            jQuery('#guideForm')[0].reset();
            jQuery('#guideErrorId').val('');
            document.getElementById('docFiles').value = '';
            jQuery('#uploadedDocList').empty();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
