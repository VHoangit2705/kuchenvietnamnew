/**
 * Document Create Page JavaScript
 * Validate file size: ảnh 2MB, PDF 5MB, video 10MB
 */

(function () {
    'use strict';

    var MAX_IMAGE_BYTES = 2 * 1024 * 1024;
    var MAX_PDF_BYTES = 5 * 1024 * 1024;
    var MAX_VIDEO_BYTES = 10 * 1024 * 1024;

    function validateDocFile(file) {
        if (!file) return { valid: true };
        var name = file.name || '';
        var ext = (name.split('.').pop() || '').toLowerCase();
        var size = file.size;
        if (['jpg', 'jpeg', 'png'].indexOf(ext) !== -1) {
            if (size > MAX_IMAGE_BYTES) return { valid: false, message: 'File ảnh không được vượt quá 2MB.' };
        } else if (ext === 'pdf') {
            if (size > MAX_PDF_BYTES) return { valid: false, message: 'File PDF không được vượt quá 5MB.' };
        } else if (['mp4', 'webm'].indexOf(ext) !== -1) {
            if (size > MAX_VIDEO_BYTES) return { valid: false, message: 'File video không được vượt quá 10MB.' };
        }
        return { valid: true };
    }

    jQuery(document).ready(function () {
        window.TechnicalDocumentFilter.init(window.docCreateRoutes, {
            selectors: {
                category: '#docCategory',
                product: '#docProduct',
                origin: '#docOrigin',
                model: '#docModelId'
            }
        });

        // Cập nhật nút theo products.status: status=4 => "Lưu và share", khác => "Lưu tài liệu"
        function updateSubmitButton(productId) {
            var btn = jQuery('#btnSubmitDoc');
            var labelSave = btn.data('label-save') || 'Lưu tài liệu';
            var labelSaveShare = btn.data('label-save-share') || 'Lưu và share';
            if (!productId) {
                btn.html('<i class="bi bi-upload me-1"></i>' + labelSave);
                return;
            }
            jQuery.get(window.docCreateRoutes.getProductStatus || '', { product_id: productId }, function (res) {
                var status = (res && res.status !== undefined && res.status !== null) ? parseInt(res.status, 10) : null;
                var html = status === 4
                    ? '<i class="bi bi-share me-1"></i>' + labelSaveShare
                    : '<i class="bi bi-upload me-1"></i>' + labelSave;
                btn.html(html);
            }).fail(function () {
                btn.html('<i class="bi bi-upload me-1"></i>' + labelSave);
            });
        }

        jQuery('#docProduct').on('change', function () {
            updateSubmitButton(jQuery(this).val() || null);
        });

        jQuery('#formDocCreate').on('submit', function (e) {
            var btn = jQuery('#btnSubmitDoc');
            
            var fileInput = document.getElementById('docCreateFile');
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                var result = validateDocFile(fileInput.files[0]);
                if (!result.valid) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'Kích thước file không hợp lệ', text: result.message, confirmButtonColor: '#0d6efd' });
                    } else {
                        alert(result.message);
                    }
                    return;
                }
            }
            // Prevent double submit
            if (jQuery(this).data('submitted') === true) {
                e.preventDefault();
            } else {
                jQuery(this).data('submitted', true);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang lưu...');
            }
        });
    });
})();
