/**
 * Documents Index Page JavaScript
 * Handles filtering, document deletion, and sharing functionality
 */

(function () {
    'use strict';

    jQuery(document).ready(function () {
        // Initialize filter
        window.TechnicalDocumentFilter.init(window.docIndexRoutes, {
            currentModelId: window.docIndexData.currentModelId,
            filter: window.docIndexData.filter
        });

        // Initialize delete functionality
        initDeleteDocument();

        // Initialize share modal
        initShareModal();
    });

    function initDeleteDocument() {
        jQuery(document).on('click', '.btn-delete-doc', function () {
            var id = jQuery(this).data('id');
            var title = jQuery(this).data('title') || '';
            if (!confirm('Bạn có chắc muốn xóa tài liệu "' + title + '"?')) return;

            jQuery.ajax({
                url: window.docIndexRoutes.destroyDocument + '/' + id,
                type: 'DELETE',
                data: { _token: window.docIndexData.csrf },
                success: function () {
                    location.reload();
                },
                error: function (xhr) {
                    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra.');
                }
            });
        });
    }

    function initShareModal() {
        var shareModal = new bootstrap.Modal(document.getElementById('shareModal'));

        // Open Modal
        jQuery(document).on('click', '.btn-share-doc', function () {
            var versionId = jQuery(this).data('version-id');
            var title = jQuery(this).data('doc-title');

            jQuery('#shareDocTitle').text(title);
            jQuery('#shareVersionId').val(versionId);

            loadShareList(versionId);
            shareModal.show();
        });

        // Load Share List
        function loadShareList(versionId) {
            jQuery('#shareListBody').html('<tr><td colspan="6" class="text-center text-muted">Đang tải...</td></tr>');
            jQuery.get(window.docIndexRoutes.shareList + '/' + versionId, function (data) {
                var html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="6" class="text-center text-muted">Chưa có liên kết chia sẻ nào.</td></tr>';
                } else {
                    data.forEach(function (item) {
                        var statusBadge = item.status === 'active'
                            ? (item.is_expired ? '<span class="badge bg-warning text-dark">Hết hạn</span>' : '<span class="badge bg-success">Hoạt động</span>')
                            : '<span class="badge bg-secondary">Đã hủy</span>';

                        html += `<tr>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" value="${item.full_url}" readonly>
                                    <button class="btn btn-outline-secondary btn-copy" type="button" data-url="${item.full_url}"><i class="bi bi-clipboard"></i></button>
                                </div>
                            </td>
                            <td>${item.permission === 'download' ? '<span class="badge bg-primary">Tải về</span>' : '<span class="badge bg-info text-dark">Xem</span>'}</td>
                            <td>${item.has_password ? '<i class="bi bi-lock-fill text-warning" title="Có mật khẩu"></i>' : '<i class="bi bi-globe text-success" title="Công khai"></i>'}</td>
                            <td class="small">${item.expires_at}</td>
                            <td class="text-center">${item.access_count}</td>
                            <td class="text-center">
                                ${item.status === 'active' ? `<button class="btn btn-sm btn-outline-danger btn-revoke" data-id="${item.id}">Thu hồi</button>` : '-'}
                            </td>
                        </tr>`;
                    });
                }
                jQuery('#shareListBody').html(html);
            });
        }

        // Create Share Link
        jQuery('#createShareForm').on('submit', function (e) {
            e.preventDefault();
            var formData = jQuery(this).serializeArray();
            formData.push({ name: '_token', value: window.docIndexData.csrf });

            jQuery.post(window.docIndexRoutes.shareStore, formData, function (res) {
                alert(res.message);
                jQuery('#createShareForm')[0].reset();
                jQuery('#shareVersionId').val(formData.find(x => x.name === 'document_version_id').value);
                loadShareList(jQuery('#shareVersionId').val());
            }).fail(function (xhr) {
                alert(xhr.responseJSON?.message || 'Lỗi khi tạo link.');
            });
        });

        // Revoke Link
        jQuery(document).on('click', '.btn-revoke', function () {
            if (!confirm('Bạn có chắc chắn muốn thu hồi liên kết này không? Người dùng sẽ không thể truy cập nữa.')) return;
            var id = jQuery(this).data('id');
            jQuery.post(window.docIndexRoutes.shareRevoke + '/' + id, { _token: window.docIndexData.csrf }, function (res) {
                loadShareList(jQuery('#shareVersionId').val());
            });
        });

        // Copy to Clipboard
        jQuery(document).on('click', '.btn-copy', function () {
            var url = jQuery(this).data('url');
            navigator.clipboard.writeText(url).then(function () {
                alert('Đã sao chép liên kết!');
            });
        });
    }
})();
