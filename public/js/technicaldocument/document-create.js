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

        jQuery('#formDocCreate').on('submit', function (e) {
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
                }
            }
        });
    });
})();
