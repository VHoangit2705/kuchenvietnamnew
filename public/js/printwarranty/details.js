$(document).ready(function() {
    const temUrl = window.temUrl;
    const itemId = window.itemId;
    const $previewContainer = $('#previewContainer');
    
    if (typeof OpenWaitBox === 'function') {
        OpenWaitBox();
    }
    
    $.get(temUrl)
        .done(function(data) {
            const iframe = `<iframe src="https://kuchenvietnam.vn/kuchen/trungtambaohanhs/storage/app/public/pdfs/tem-bao-hanh-${itemId}.pdf" style="width: 100%; height: 1000px;" frameborder="0"></iframe>`;
            $previewContainer.html(iframe);
            
            if (typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
        })
        .fail(function(xhr, status, error) {
            if (typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            $previewContainer.html(`<div style="color:red;">Không thể tải PDF: ${error}</div>`);
        });
});

$('#downloadBtn').on('click', function(e) {
    e.preventDefault();
    const url = window.downloadUrl;
    
    downloadFile(url, {
        defaultFilename: 'tem-bao-hanh.pdf',
        acceptHeader: 'application/pdf',
        useSwalLoading: true
    });
});

