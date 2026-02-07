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
            
            Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc muốn xóa mã lỗi "' + code + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    jQuery.ajax({
                        url: window.errorIndexRoutes.destroyError + '/' + id,
                        type: 'DELETE',
                        data: { _token: window.errorIndexData.csrf },
                        success: function () {
                            location.reload();
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: 'Có lỗi xảy ra khi xóa.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });
    }
})();
