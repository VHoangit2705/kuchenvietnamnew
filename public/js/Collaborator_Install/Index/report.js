/**
 * Xử lý báo cáo và preview
 */

function Report() {
    $('#reportCollaboratorInstall').on('click', function(e) {
        e.preventDefault();
        const queryParams = new URLSearchParams({
            start_date: $('#tungay').val(),
            end_date: $('#denngay').val()
        });
        // Always open Preview directly
        queryParams.set('embed', '1');
        const previewUrl = (window.ROUTES.collaborator_export_preview || '/collaborator/export/preview') + `?${queryParams.toString()}`;
        const $iframe = $('#previewModal iframe');
        const $spinner = $('#previewModal .preview-loading');
        $spinner.removeClass('d-none');
        $iframe.addClass('d-none');
        $iframe.off('load').on('load', function() {
            $spinner.addClass('d-none');
            $iframe.removeClass('d-none');
        });
        $iframe.attr('src', previewUrl);
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
    });
}

// Dọn src khi đóng modal xem trước
function initPreviewModal() {
    $(document).on('hidden.bs.modal', '#previewModal', function() {
        $('#previewModal iframe').attr('src', '');
        $('#previewModal .preview-loading').removeClass('d-none');
        $('#previewModal iframe').addClass('d-none');
    });
}

