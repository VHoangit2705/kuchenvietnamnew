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

    var selectedProductId = null;
    var selectedOrigin = null;

    var editorDesc, editorSteps, editorSafety;
    var ckToolbar = [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'imageUpload', '|', 'undo', 'redo' ];

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

    function loadErrorsByProduct(productId, origin) {
        jQuery.get(routes.getErrorsByModel || '', { product_id: productId, xuat_xu: origin }, function (res) {
            var opts = '<option value="">-- Chọn mã lỗi --</option>';
            (res || []).forEach(function (e) {
                opts += '<option value="' + e.id + '">' + (e.error_code || '') + ' - ' + (e.error_name || '') + '</option>';
            });
            jQuery('#createErrorId').html(opts);
            jQuery('#errorList').empty();
            if (res && res.length) {
                res.forEach(function (e) {
                    jQuery('#errorList').append(
                        '<li class="list-group-item d-flex justify-content-between align-items-center" data-id="' + e.id + '">' +
                        '<span><strong>' + (e.error_code || '') + '</strong> ' + (e.error_name || '') + ' <span class="badge bg-secondary">' + (e.severity || 'normal') + '</span></span>' +
                        '<div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-primary btn-edit-error" data-id="' + e.id + '" title="Sửa"><i class="bi bi-pencil"></i></button>' +
                        '<button type="button" class="btn btn-outline-danger btn-delete-error" data-id="' + e.id + '" title="Xóa"><i class="bi bi-trash"></i></button></div>' +
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
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: msg,
            confirmButtonColor: '#d33'
        });
    }

    function init() {
        if (typeof ClassicEditor !== 'undefined') {
            var ckConfig = {
                extraPlugins: [ MyCustomUploadAdapterPlugin ],
                toolbar: ckToolbar
            };
            ClassicEditor.create(document.querySelector('#modalDesc'), ckConfig)
                .then(function(e) { editorDesc = e; }).catch(function(err) { console.error(err); });
            ClassicEditor.create(document.querySelector('#guideSteps'), ckConfig)
                .then(function(e) { editorSteps = e; }).catch(function(err) { console.error(err); });
            ClassicEditor.create(document.querySelector('#guideSafetyNote'), ckConfig)
                .then(function(e) { editorSafety = e; }).catch(function(err) { console.error(err); });
        }

        // Bước 1: Danh mục → load Sản phẩm
        jQuery('#createCategory').on('change', function () {
            var cid = jQuery(this).val();
            resetSelect('#createProduct', '-- Chọn sản phẩm --', true);
            resetSelect('#createOrigin', '-- Chọn xuất xứ --', true);
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
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu thông tin',
                    text: 'Vui lòng chọn Sản phẩm trước.',
                    confirmButtonColor: '#0d6efd'
                });
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
                    if (res && res.message) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function (xhr) { showError(xhr); }
            });
        });

        // Bước 3: Xuất xứ → show block quản lý lỗi
        jQuery('#createOrigin').on('change', function () {
            var pid = jQuery('#createProduct').val();
            var origin = jQuery(this).val();
            jQuery('#blockAfterModel').hide();
            selectedProductId = pid;
            selectedOrigin = origin;
            
            if (!pid || !origin) return;
            
            jQuery('#blockAfterModel').show();
            loadErrorsByProduct(pid, origin);
        });

        // Mở modal Thêm mã lỗi → clear edit mode
        jQuery('#modalAddError').on('show.bs.modal', function (ev) {
            if (!jQuery(ev.relatedTarget).hasClass('btn-edit-error')) {
                jQuery('#errorEditId').val('');
                jQuery('#modalAddErrorTitle').html('<i class="bi bi-bug me-2"></i>Khai báo lỗi mới');
                jQuery('#formAddError')[0].reset();
                if (editorDesc) editorDesc.setData('');
            }
        });

        // Sửa mã lỗi: load dữ liệu và mở modal
        jQuery('#errorList').on('click', '.btn-edit-error', function () {
            var id = jQuery(this).data('id');
            jQuery('#modalAddErrorTitle').text('Sửa mã lỗi');
            jQuery.get(routes.getErrorById + '/' + id).then(function (res) {
                jQuery('#errorEditId').val(res.id);
                jQuery('#modalErrorCode').val(res.error_code || '');
                jQuery('#modalErrorName').val(res.error_name || '');
                jQuery('#modalSeverity').val(res.severity || 'normal');
                jQuery('#modalDesc').val(res.description || '');
                if (editorDesc) editorDesc.setData(res.description || '');
                jQuery('#modalAddError').modal('show');
            }).fail(function (xhr) { showError(xhr); });
        });

        // Xóa mã lỗi
        jQuery('#errorList').on('click', '.btn-delete-error', function () {
            var id = jQuery(this).data('id');
            Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc muốn xóa mã lỗi này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    jQuery.ajax({
                        url: routes.destroyError + '/' + id,
                        type: 'DELETE',
                        data: { _token: csrfToken },
                        success: function (res) {
                            if (res && res.message) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Đã xóa',
                                    text: res.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                            loadErrorsByProduct(selectedProductId, selectedOrigin);
                        },
                        error: function (xhr) { showError(xhr); }
                    });
                }
            });
        });

        // Modal: Thêm/Sửa mã lỗi (Bước 5)
        jQuery('#formAddError').on('submit', function (e) {
            e.preventDefault();
            if (editorDesc) editorDesc.updateSourceElement();
            var editId = jQuery('#errorEditId').val();
            if (editId) {
                var fd = new FormData(this);
                fd.append('_token', csrfToken);
                fd.append('_method', 'PUT');
                jQuery.ajax({
                    url: routes.updateError + '/' + editId,
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        jQuery('#modalAddError').modal('hide');
                        jQuery('#formAddError')[0].reset();
                        if (editorDesc) editorDesc.setData('');
                        jQuery('#errorEditId').val('');
                        if (res && res.message) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cập nhật thành công',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                        loadErrorsByProduct(selectedProductId, selectedOrigin);
                    },
                    error: function (xhr) { showError(xhr); }
                });
            } else {
                if (!selectedProductId || !selectedOrigin) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thiếu thông tin',
                        text: 'Vui lòng chọn Sản phẩm và Xuất xứ trước.',
                        confirmButtonColor: '#0d6efd'
                    });
                    return;
                }
                var fd = new FormData(this);
                fd.append('product_id', selectedProductId);
                fd.append('xuat_xu', selectedOrigin);
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
                        if (editorDesc) editorDesc.setData('');
                        loadErrorsByProduct(selectedProductId, selectedOrigin);
                    },
                    error: function (xhr) { showError(xhr); }
                });
            }
        });

        // Chọn mã lỗi → gán vào form hướng dẫn + load danh sách hướng dẫn sửa
        jQuery('#createErrorId').on('change', function () {
            var errId = jQuery(this).val();
            jQuery('#guideErrorId').val(errId);
            var card = jQuery('#repairGuidesListCard');
            var list = jQuery('#repairGuidesList');
            list.empty();
            if (!errId) {
                card.hide();
                return;
            }
            jQuery.get(routes.getRepairGuidesByError || '', { error_id: errId }, function (res) {
                if (res && res.length) {
                    res.forEach(function (g) {
                        list.append(
                            '<div class="list-group-item d-flex justify-content-between align-items-center py-2">' +
                            '<span class="small">' + (g.title || '') + '</span>' +
                            '<div class="btn-group btn-group-sm">' +
                            '<a href="' + (routes.editRepairGuide || '') + '/' + g.id + '" class="btn btn-outline-primary btn-sm" title="Sửa"><i class="bi bi-pencil"></i></a>' +
                            '<button type="button" class="btn btn-outline-danger btn-sm btn-delete-guide" data-id="' + g.id + '" title="Xóa"><i class="bi bi-trash"></i></button>' +
                            '</div></div>'
                        );
                    });
                    card.show();
                } else {
                    card.hide();
                }
            });
        });

        // Xóa hướng dẫn sửa
        jQuery('#repairGuidesList').on('click', '.btn-delete-guide', function () {
            var id = jQuery(this).data('id');
            Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc muốn xóa hướng dẫn sửa này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    jQuery.ajax({
                        url: (routes.destroyRepairGuide || '') + '/' + id,
                        type: 'DELETE',
                        data: { _token: csrfToken },
                        success: function (res) {
                            if (res && res.message) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Đã xóa',
                                    text: res.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                            jQuery('#createErrorId').trigger('change');
                        },
                        error: function (xhr) { showError(xhr); }
                    });
                }
            });
        });

        // Giới hạn file tài liệu: ảnh 2MB, PDF 5MB, video 10MB
        var MAX_IMAGE_BYTES = 2 * 1024 * 1024;
        var MAX_PDF_BYTES = 5 * 1024 * 1024;
        var MAX_VIDEO_BYTES = 10 * 1024 * 1024;

        function validateGuideFiles(files) {
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var name = file.name || '';
                var ext = (name.split('.').pop() || '').toLowerCase();
                var size = file.size;
                if (['jpg', 'jpeg', 'png'].indexOf(ext) !== -1) {
                    if (size > MAX_IMAGE_BYTES) {
                        return { valid: false, message: 'File ảnh "' + name + '" vượt quá 2MB. Vui lòng chọn file nhỏ hơn.' };
                    }
                } else if (ext === 'pdf') {
                    if (size > MAX_PDF_BYTES) {
                        return { valid: false, message: 'File PDF "' + name + '" vượt quá 5MB. Vui lòng chọn file nhỏ hơn.' };
                    }
                } else if (['mp4', 'webm'].indexOf(ext) !== -1) {
                    if (size > MAX_VIDEO_BYTES) {
                        return { valid: false, message: 'File video "' + name + '" vượt quá 10MB. Vui lòng chọn file nhỏ hơn.' };
                    }
                }
            }
            return { valid: true };
        }

        function setSaveGuideButtonLoading(loading) {
            var $btn = jQuery('#btnSaveGuide');
            var $label = $btn.find('.btn-save-label');
            var $spinner = $btn.find('.spinner-border');
            if (loading) {
                $btn.prop('disabled', true);
                $label.addClass('d-none');
                $spinner.removeClass('d-none');
            } else {
                $btn.prop('disabled', false);
                $label.removeClass('d-none');
                $spinner.addClass('d-none');
            }
        }

        jQuery('#guideForm').on('submit', function (e) {
            e.preventDefault();
            var errorId = jQuery('#guideErrorId').val() || jQuery('#createErrorId').val();
            if (!errorId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu thông tin',
                    text: 'Vui lòng chọn mã lỗi.',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }
            var files = document.getElementById('docFiles').files;
            var fileCheck = validateGuideFiles(files);
            if (!fileCheck.valid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kích thước file không hợp lệ',
                    text: fileCheck.message,
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }

            var fd = new FormData();
            fd.append('error_id', errorId);
            fd.append('title', jQuery('#guideTitle').val() || '');
            fd.append('steps', editorSteps ? editorSteps.getData() : jQuery('#guideSteps').val());
            fd.append('estimated_time', jQuery('#guideEstimatedTime').val() || 0);
            fd.append('safety_note', editorSafety ? editorSafety.getData() : jQuery('#guideSafetyNote').val());
            fd.append('_token', csrfToken);
            for (var i = 0; i < files.length; i++) {
                fd.append('files[]', files[i]);
            }

            setSaveGuideButtonLoading(true);

            jQuery.ajax({
                url: routes.storeRepairGuide || '',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    var categoryId = jQuery('#createCategory').val();
                    setSaveGuideButtonLoading(false);

                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: (res && res.message) || 'Đã lưu hướng dẫn.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () {
                        // Redirect sang trang tra cứu với query params để auto-fill filter
                        var baseUrl = '/baohanh/tailieukithuat';
                        var params = '?category_id=' + encodeURIComponent(categoryId || '')
                            + '&product_id=' + encodeURIComponent(selectedProductId || '')
                            + '&xuat_xu=' + encodeURIComponent(selectedOrigin || '');
                        window.location.href = baseUrl + params;
                    });
                },
                error: function (xhr) {
                    showError(xhr);
                    setSaveGuideButtonLoading(false);
                }
            });
        });

        jQuery('#btnResetGuide').on('click', function () {
            jQuery('#guideForm')[0].reset();
            if (editorSteps) editorSteps.setData('');
            if (editorSafety) editorSafety.setData('');
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
