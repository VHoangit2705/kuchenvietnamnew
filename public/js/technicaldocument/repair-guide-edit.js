/**
 * Repair Guide Edit Page JavaScript
 * Handles guide deletion and document detachment
 */

(function () {
    'use strict';

    jQuery(document).ready(function () {
        initDeleteGuide();
        initDetachDocuments();
    });

    function initDeleteGuide() {
        document.getElementById('btnDeleteGuide').addEventListener('click', function () {
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
                    document.getElementById('formDeleteGuide').submit();
                }
            });
        });
    }

    function initDetachDocuments() {
        document.querySelectorAll('.btn-detach-doc').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var docId = this.getAttribute('data-document-id');
                
                Swal.fire({
                    title: 'Xác nhận gỡ',
                    text: 'Gỡ tài liệu này khỏi hướng dẫn?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Gỡ',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('DELETE', window.repairGuideData.detachUrl + docId);
                        xhr.setRequestHeader('X-CSRF-TOKEN', window.repairGuideData.csrf);
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.onload = function () {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                btn.closest('li').remove();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: 'Không thể gỡ tài liệu.',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        };
                        xhr.send();
                    }
                });
            });
        });
    }
})();
