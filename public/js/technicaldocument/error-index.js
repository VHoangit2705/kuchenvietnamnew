/**
 * Error Management Index Page JavaScript
 * Handles filtering and error deletion
 */

(function () {
    'use strict';

    jQuery(document).ready(function () {
        // Initialize filter
        window.TechnicalDocumentFilter.init(window.errorIndexRoutes, {
            currentModelId: window.errorIndexData.currentModelId,
            filter: window.errorIndexData.filter
        });

        // Initialize delete functionality
        initDeleteError();
    });

    function initDeleteError() {
        jQuery(document).on('click', '.btn-delete-error', function () {
            var id = jQuery(this).data('id');
            var code = jQuery(this).data('code');
            if (!confirm('Bạn có chắc muốn xóa mã lỗi "' + code + '"?')) return;

            jQuery.ajax({
                url: window.errorIndexRoutes.destroyError + '/' + id,
                type: 'DELETE',
                data: { _token: window.errorIndexData.csrf },
                success: function () {
                    location.reload();
                },
                error: function (xhr) {
                    alert('Có lỗi xảy ra khi xóa.');
                }
            });
        });
    }
})();
