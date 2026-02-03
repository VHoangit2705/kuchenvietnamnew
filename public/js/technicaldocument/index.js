/**
 * Trang tra cứu tài liệu kỹ thuật (index)
 * Cần có window.technicalDocumentIndexConfig trước khi load:
 * { routes: { getProductsByCategory, getOriginsByProduct, getModelsByOrigin } }
 */
(function () {
    'use strict';

    var config = window.technicalDocumentIndexConfig || {};
    var routes = config.routes || {};

    function resetSelect(id, placeholder, disable) {
        if (disable === undefined) disable = true;
        jQuery(id).html('<option value="">' + placeholder + '</option>').prop('disabled', disable);
    }

    function init() {
        // 1. Chọn danh mục → load sản phẩm
        jQuery('#categorySelect').on('change', function () {
            var categoryId = jQuery(this).val();

            resetSelect('#productNameSelect', '-- Chọn sản phẩm --', true);
            resetSelect('#originSelect', '-- Chọn xuất xứ --', true);
            resetSelect('#productCodeSelect', '-- Chọn mã SP --', true);
            jQuery('#btnSearch').prop('disabled', true);

            if (!categoryId) return;

            jQuery.get(routes.getProductsByCategory || '', { category_id: categoryId }, function (res) {
                resetSelect('#productNameSelect', '-- Chọn sản phẩm --', false);
                (res || []).forEach(function (p) {
                    jQuery('#productNameSelect').append(
                        '<option value="' + p.id + '">' + (p.name || p.product_name || '') + (p.model ? ' (' + p.model + ')' : '') + '</option>'
                    );
                });
            }).fail(function () {
                resetSelect('#productNameSelect', '-- Chọn sản phẩm --', false);
            });
        });

        // 2. Chọn sản phẩm → load xuất xứ
        jQuery('#productNameSelect').on('change', function () {
            var productId = jQuery(this).val();

            resetSelect('#originSelect', '-- Chọn xuất xứ --', true);
            resetSelect('#productCodeSelect', '-- Chọn mã SP --', true);
            jQuery('#btnSearch').prop('disabled', true);

            if (!productId) return;

            jQuery.get(routes.getOriginsByProduct || '', { product_id: productId }, function (res) {
                resetSelect('#originSelect', '-- Chọn xuất xứ --', false);
                (res || []).forEach(function (o) {
                    jQuery('#originSelect').append(
                        '<option value="' + (o.xuat_xu || '') + '">' + (o.xuat_xu || '') + '</option>'
                    );
                });
            }).fail(function () {
                resetSelect('#originSelect', '-- Chọn xuất xứ --', false);
            });
        });

        // 3. Chọn xuất xứ → load mã sản phẩm (model)
        jQuery('#originSelect').on('change', function () {
            var productId = jQuery('#productNameSelect').val();
            var origin = jQuery(this).val();

            resetSelect('#productCodeSelect', '-- Chọn mã SP --', true);
            jQuery('#btnSearch').prop('disabled', true);

            if (!origin) return;

            jQuery.get(routes.getModelsByOrigin || '', {
                product_id: productId,
                xuat_xu: origin
            }, function (res) {
                resetSelect('#productCodeSelect', '-- Chọn mã SP --', false);
                (res || []).forEach(function (m) {
                    jQuery('#productCodeSelect').append(
                        '<option value="' + m.id + '">' + (m.model_code || '') + (m.version ? ' (' + m.version + ')' : '') + '</option>'
                    );
                });
            }).fail(function () {
                resetSelect('#productCodeSelect', '-- Chọn mã SP --', false);
            });
        });

        // 4. Chọn mã SP → enable tìm kiếm
        jQuery('#productCodeSelect').on('change', function () {
            jQuery('#btnSearch').prop('disabled', !jQuery(this).val());
        });

        // 5. Bấm Tìm kiếm → load mã lỗi theo model, hiển thị bảng
        jQuery('#btnSearch').on('click', function () {
            var modelId = jQuery('#productCodeSelect').val();
            if (!modelId) {
                alert('Vui lòng chọn Mã sản phẩm trước khi tìm kiếm.');
                return;
            }

            jQuery('#errorTableCard').show();
            jQuery('#emptyState').addClass('d-none');
            jQuery('#errorTableBody').html('<tr><td colspan="6" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải...</td></tr>');

            jQuery.get(routes.getErrorsByModel || '', { model_id: modelId }, function (res) {
                var rows = res || [];
                if (rows.length === 0) {
                    jQuery('#errorTableCard').hide();
                    jQuery('#emptyState').removeClass('d-none');
                    return;
                }

                var html = '';
                rows.forEach(function (e, i) {
                    var severityLabel = { normal: 'Thường', common: 'Phổ biến', critical: 'Nghiêm trọng' }[e.severity] || e.severity;
                    var severityClass = { normal: 'secondary', common: 'warning', critical: 'danger' }[e.severity] || 'secondary';
                    var desc = (e.description || '').toString().substring(0, 80);
                    if ((e.description || '').length > 80) desc += '...';
                    html += '<tr>' +
                        '<td>' + (i + 1) + '</td>' +
                        '<td><span class="badge bg-dark">' + (e.error_code || '') + '</span></td>' +
                        '<td class="fw-semibold">' + (e.error_name || '') + '</td>' +
                        '<td><span class="badge bg-' + severityClass + '">' + severityLabel + '</span></td>' +
                        '<td class="text-muted small">' + (desc || '—') + '</td>' +
                        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary btn-view-error" data-id="' + e.id + '" data-code="' + (e.error_code || '') + '" data-name="' + (e.error_name || '').replace(/"/g, '&quot;') + '" data-desc="' + (e.description || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '" title="Xem chi tiết"><i class="bi bi-eye"></i></button></td>' +
                        '</tr>';
                });
                jQuery('#errorTableBody').html(html);
            }).fail(function () {
                jQuery('#errorTableBody').html('<tr><td colspan="6" class="text-center py-4 text-danger">Không tải được dữ liệu. Kiểm tra kết nối hoặc thử lại.</td></tr>');
                jQuery('#emptyState').addClass('d-none');
            });
        });

        // Click Xem chi tiết → gọi API load hướng dẫn + tài liệu/ảnh/video, mở modal
        jQuery(document).on('click', '.btn-view-error', function () {
            var errorId = jQuery(this).data('id');
            var code = jQuery(this).data('code');
            var name = jQuery(this).data('name');
            var desc = jQuery(this).data('desc');

            jQuery('#detailErrorCode').text(code || '');
            jQuery('#detailErrorName').text(name || '');
            jQuery('#detailDescription').text(desc || 'Chưa có mô tả.');
            jQuery('#detailSolution').html('<p class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải...</p>');
            jQuery('#detailDocuments').empty();
            jQuery('#detailMediaInner').html('<div class="text-center py-4 text-muted">Đang tải...</div>');
            jQuery('#errorDetailModal').modal('show');

            jQuery.get(routes.getErrorDetail || '', { error_id: errorId }, function (res) {
                var guides = (res && res.repair_guides) ? res.repair_guides : [];
                var firstGuide = guides[0];

                if (firstGuide && firstGuide.steps) {
                    var stepsHtml = '<ol class="mb-0">';
                    var lines = (firstGuide.steps || '').split(/\r?\n/).filter(function (s) { return s.trim(); });
                    lines.forEach(function (line) {
                        stepsHtml += '<li class="mb-2">' + escapeHtml(line.trim()) + '</li>';
                    });
                    stepsHtml += '</ol>';
                    if (firstGuide.safety_note) {
                        stepsHtml += '<div class="alert alert-warning mt-3 mb-0"><strong>Lưu ý an toàn:</strong> ' + escapeHtml(firstGuide.safety_note) + '</div>';
                    }
                    jQuery('#detailSolution').html(stepsHtml);
                } else {
                    jQuery('#detailSolution').html('<p class="text-muted">Chưa có hướng dẫn xử lý.</p>');
                }

                var allDocs = [];
                guides.forEach(function (g) {
                    (g.documents || []).forEach(function (d) {
                        allDocs.push({ title: d.title, doc_type: d.doc_type, file_url: d.file_url, file_type: d.file_type });
                    });
                });

                var docListHtml = '';
                allDocs.forEach(function (d, idx) {
                    var icon = 'file-earmark';
                    if (d.doc_type === 'manual' || d.file_type === 'pdf') icon = 'file-earmark-pdf';
                    else if (d.doc_type === 'image' || /^(jpg|jpeg|png|gif|webp)$/i.test(d.file_type)) icon = 'file-earmark-image';
                    else if (d.doc_type === 'video' || /^(mp4|webm|ogg)$/i.test(d.file_type)) icon = 'file-earmark-play';

                    docListHtml += '<a href="javascript:void(0)" class="list-group-item list-group-item-action document-item btn-preview-doc" ' +
                        'data-url="' + escapeHtml(d.file_url) + '" data-title="' + escapeHtml(d.title) + '" data-type="' + escapeHtml(d.file_type || '') + '">' +
                        '<i class="bi bi-' + icon + ' me-2"></i>' + escapeHtml(d.title) + ' <small class="text-muted">(' + (d.file_type || '') + ')</small></a>';
                });
                jQuery('#detailDocuments').html(docListHtml || '<p class="text-muted small mb-0">Chưa có tài liệu đính kèm.</p>');

                var mediaItems = allDocs.filter(function (d) {
                    return d.doc_type === 'image' || d.doc_type === 'video' || (d.file_type && /^(jpg|jpeg|png|gif|webp|mp4|webm)$/i.test(d.file_type));
                });
                var carouselHtml = '';
                if (mediaItems.length > 0) {
                    mediaItems.forEach(function (item, idx) {
                        var active = idx === 0 ? ' active' : '';
                        var isVideo = /^(mp4|webm|ogg)$/i.test(item.file_type || '');
                        if (isVideo) {
                            carouselHtml += '<div class="carousel-item' + active + '"><div class="ratio ratio-16x9"><video src="' + item.file_url + '" controls class="w-100"></video></div></div>';
                        } else {
                            carouselHtml += '<div class="carousel-item' + active + '"><img src="' + item.file_url + '" class="d-block w-100" alt="' + escapeHtml(item.title) + '" style="max-height: 400px; object-fit: contain;"></div>';
                        }
                    });
                    jQuery('#detailMediaInner').html(carouselHtml);
                    if (mediaItems.length > 1) {
                        jQuery('#mediaCarousel').carousel(0);
                    }
                } else {
                    jQuery('#detailMediaInner').html('<div class="text-center py-5 text-muted"><i class="bi bi-images fs-1 opacity-50"></i><p class="mt-2 mb-0">Không có hình ảnh / video minh họa.</p></div>');
                }

                if (allDocs.length > 0) {
                    jQuery('#btnDownloadAll')
                        .attr('href', (routes.downloadAllDocuments || '') + '?error_id=' + errorId)
                        .attr('download', '')
                        .show();
                } else {
                    jQuery('#btnDownloadAll').hide();
                }
            }).fail(function () {
                jQuery('#detailSolution').html('<p class="text-muted">Không tải được chi tiết.</p>');
                jQuery('#detailMediaInner').html('<div class="text-center py-5 text-muted">Không tải được dữ liệu.</div>');
            });
        });

        // Click vào tài liệu → mở modal preview (PDF/ảnh/video) thay vì tab mới
        jQuery(document).on('click', '.btn-preview-doc', function () {
            var url = jQuery(this).data('url');
            var title = jQuery(this).data('title');
            var fileType = (jQuery(this).data('type') || '').toLowerCase();

            if (fileType === 'pdf') {
                jQuery('#pdfPreviewTitle').text(title || 'Tài liệu PDF');
                jQuery('#pdfPreviewIframe').attr('src', url);
                jQuery('#pdfPreviewModal').modal('show');
            } else if (/^(jpg|jpeg|png|gif|webp)$/i.test(fileType)) {
                jQuery('#imagePreviewTitle').text(title || 'Hình ảnh');
                jQuery('#imagePreviewImg').attr('src', url);
                jQuery('#imagePreviewModal').modal('show');
            } else {
                window.open(url, '_blank');
            }
        });

        // Đóng modal PDF → xóa src để tránh reload
        jQuery('#pdfPreviewModal').on('hidden.bs.modal', function () {
            jQuery('#pdfPreviewIframe').attr('src', '');
        });

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
