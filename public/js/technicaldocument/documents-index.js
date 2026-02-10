/**
 * Documents Index Page JavaScript (DEBUG VERSION)
 */

(function () {
    'use strict';

    console.log('[DOC] JS loaded');

    jQuery(document).ready(function () {
        console.log('[DOC] Document ready');

        console.log('[DOC] docIndexRoutes:', window.docIndexRoutes);
        console.log('[DOC] docIndexData:', window.docIndexData);

        // Initialize filter
        if (window.TechnicalDocumentFilter) {
            console.log('[DOC] Init TechnicalDocumentFilter');
            window.TechnicalDocumentFilter.init(window.docIndexRoutes, {
                selectors: {
                    category: '#filterCategory',
                    product: '#filterProduct',
                    origin: '#filterOrigin'
                },
                filter: window.docIndexData.filter
            });
        } else {
            console.warn('[DOC] TechnicalDocumentFilter NOT FOUND');
        }

        initDeleteDocument();
        initShareModal();
        initTableDeleteDocument();
    });

    function initTableDeleteDocument() {
        jQuery(document).on('submit', '.form-delete-document', function (e) {
            e.preventDefault();
            var form = this;
            var title = jQuery(this).data('doc-title') || 'tài liệu này';
            var url = form.action;

            Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc chắn muốn xóa tài liệu "' + title + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request instead of form.submit()
                    jQuery.ajax({
                        url: url,
                        type: 'POST', // Use POST with _method=DELETE
                        data: jQuery(form).serialize(), // Includes _token and _method
                        success: function (res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: res.message || 'Đã xóa tài liệu.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            console.error('[DOC] Delete error:', xhr);
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: xhr.responseJSON?.message || 'Có lỗi xảy ra khi xóa.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });
    }

    function initDeleteDocument() {
        console.log('[DOC] initDeleteDocument');

        jQuery(document).on('click', '.btn-delete-doc', function () {
            console.log('[DOC] Click delete');

            var id = jQuery(this).data('id');
            var title = jQuery(this).data('title') || '';

            console.log('[DOC] Delete ID:', id, 'Title:', title);

            Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc muốn xóa tài liệu "' + title + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = window.docIndexRoutes.destroyDocument + '/' + id;
                    console.log('[DOC] DELETE URL:', url);

                    jQuery.ajax({
                        url: url,
                        type: 'DELETE',
                        data: { _token: window.docIndexData.csrf },
                        success: function (res) {
                            console.log('[DOC] Delete success:', res);
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: res.message || 'Đã xóa tài liệu.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            console.error('[DOC] Delete error:', xhr);
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: xhr.responseJSON?.message || 'Có lỗi xảy ra.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });
    }

    function initShareModal() {
        console.log('[DOC] initShareModal');

        var modalEl = document.getElementById('shareModal');
        if (!modalEl) {
            console.error('[DOC] shareModal element NOT FOUND');
            return;
        }

        var shareModal = new bootstrap.Modal(modalEl);

        // Open Modal
        jQuery(document).on('click', '.btn-share-doc', function () {
            console.log('[DOC] Click share');

            var versionId = jQuery(this).data('version-id');
            var title = jQuery(this).data('doc-title');

            console.log('[DOC] Share versionId:', versionId);
            console.log('[DOC] Share title:', title);

            jQuery('#shareDocTitle').text(title);
            jQuery('#shareVersionId').val(versionId);

            loadShareList(versionId);
            shareModal.show();
        });

        // Load Share List
        function loadShareList(versionId) {
            console.log('[DOC] loadShareList versionId:', versionId);

            jQuery('#shareListBody').html(
                '<tr><td colspan="6" class="text-center text-muted">Đang tải...</td></tr>'
            );

            var url = window.docIndexRoutes.shareList + '/' + versionId;
            console.log('[DOC] GET share list:', url);

            jQuery.get(url)
                .done(function (data) {
                    console.log('[DOC] Share list response:', data);

                    var html = '';
                    if (!data || data.length === 0) {
                        html = '<tr><td colspan="6" class="text-center text-muted">Chưa có liên kết chia sẻ nào.</td></tr>';
                    } else {
                        data.forEach(function (item, index) {
                            console.log('[DOC] Share item #' + index, item);

                            html += `<tr>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" value="${item.full_url}" readonly>
                                        <button class="btn btn-outline-secondary btn-copy" type="button" data-url="${item.full_url}">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>${item.permission}</td>
                                <td>${item.has_password ? '🔒' : '🌐'}</td>
                                <td class="small">${item.expires_at}</td>
                                <td class="text-center">${item.access_count}</td>
                                <td class="text-center">
                                    ${item.status === 'active'
                                        ? `<button class="btn btn-sm btn-outline-danger btn-revoke" data-id="${item.id}">Thu hồi</button>`
                                        : '-'}
                                </td>
                            </tr>`;
                        });
                    }

                    jQuery('#shareListBody').html(html);
                })
                .fail(function (xhr) {
                    console.error('[DOC] Load share list FAILED', xhr);
                    jQuery('#shareListBody').html(
                        '<tr><td colspan="6" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>'
                    );
                });
        }

        // Create Share Link
        jQuery('#createShareForm').on('submit', function (e) {
            e.preventDefault();
            console.log('[DOC] Submit createShareForm');

            var formData = jQuery(this).serializeArray();
            formData.push({ name: '_token', value: window.docIndexData.csrf });

            console.log('[DOC] Share create payload:', formData);
            console.log('[DOC] POST shareStore:', window.docIndexRoutes.shareStore);

            jQuery.post(window.docIndexRoutes.shareStore, formData)
                .done(function (res) {
                    console.log('[DOC] Share created:', res);
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    jQuery('#createShareForm')[0].reset();

                    var versionId = formData.find(x => x.name === 'document_version_id')?.value;
                    console.log('[DOC] Reload share list for version:', versionId);

                    jQuery('#shareVersionId').val(versionId);
                    loadShareList(versionId);
                })
                .fail(function (xhr) {
                    console.error('[DOC] Create share FAILED', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: xhr.responseJSON?.message || 'Lỗi khi tạo link.',
                        confirmButtonColor: '#d33'
                    });
                });
        });

        // Revoke Link
        jQuery(document).on('click', '.btn-revoke', function () {
            var id = jQuery(this).data('id');
            console.log('[DOC] Revoke share id:', id);

            Swal.fire({
                title: 'Xác nhận thu hồi',
                text: 'Bạn có chắc chắn muốn thu hồi liên kết này không?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Thu hồi',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = window.docIndexRoutes.shareRevoke + '/' + id;
                    console.log('[DOC] POST revoke:', url);

                    jQuery.post(url, { _token: window.docIndexData.csrf })
                        .done(function (res) {
                            console.log('[DOC] Revoke success:', res);
                            loadShareList(jQuery('#shareVersionId').val());
                        })
                        .fail(function (xhr) {
                            console.error('[DOC] Revoke FAILED', xhr);
                        });
                }
            });
        });

        // Copy to Clipboard
        jQuery(document).on('click', '.btn-copy', function () {
            var url = jQuery(this).data('url');
            console.log('[DOC] Copy URL:', url);

            navigator.clipboard.writeText(url).then(function () {
                console.log('[DOC] Copied to clipboard');
                Swal.fire({
                    icon: 'success',
                    title: 'Đã sao chép',
                    text: 'Đã sao chép liên kết!',
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }).catch(function (err) {
                console.error('[DOC] Clipboard error:', err);
            });
        });
    }
})();
