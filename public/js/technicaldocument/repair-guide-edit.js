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
            if (!confirm('Bạn có chắc muốn xóa hướng dẫn sửa này?')) return;
            document.getElementById('formDeleteGuide').submit();
        });
    }

    function initDetachDocuments() {
        document.querySelectorAll('.btn-detach-doc').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var docId = this.getAttribute('data-document-id');
                if (!confirm('Gỡ tài liệu này khỏi hướng dẫn?')) return;

                var xhr = new XMLHttpRequest();
                xhr.open('DELETE', window.repairGuideData.detachUrl + docId);
                xhr.setRequestHeader('X-CSRF-TOKEN', window.repairGuideData.csrf);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        btn.closest('li').remove();
                    } else {
                        alert('Không thể gỡ tài liệu.');
                    }
                };
                xhr.send();
            });
        });
    }
})();
