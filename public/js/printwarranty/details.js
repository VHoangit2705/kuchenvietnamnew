// JavaScript cho details.blade.php
// Xử lý preview PDF và download
$(document).ready(function() {
    const $previewContainer = $('#previewContainer');
    const temUrl = $previewContainer.data('tem-url');
    const itemId = $previewContainer.data('item-id');
    const pdfBaseUrl = $previewContainer.data('pdf-base-url') || 'https://kuchenvietnam.vn/kuchen/trungtambaohanhs/storage/app/public/pdfs';
    
    if (temUrl && itemId) {
        OpenWaitBox();
        $.get(temUrl)
            .done(function(data) {
                const iframe = `<iframe src="${pdfBaseUrl}/tem-bao-hanh-${itemId}.pdf" style="width: 100%; height: 1000px;" frameborder="0"></iframe>`;
                $previewContainer.html(iframe);
                CloseWaitBox();
            })
            .fail(function(xhr, status, error) {
                CloseWaitBox();
                $previewContainer.html(`<div style="color:red;">Không thể tải PDF: ${error}</div>`);
            });
    }

    $('#downloadBtn').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('download-url');
        
        if (!url) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Không tìm thấy URL download'
            });
            return;
        }
        
        Swal.fire({
            title: 'Đang tải file...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Lỗi tải file');  
                const disposition = response.headers.get('Content-Disposition');
                let filename = "tem-bao-hanh.pdf";
                if (disposition && disposition.indexOf('filename=') !== -1) {
                    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (match && match[1]) {
                        filename = decodeURIComponent(match[1].replace(/['"]/g, ''));
                    }
                }

                return response.blob().then(blob => ({
                    blob,
                    filename
                }));
            })
            .then(({ blob, filename }) => {
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(downloadUrl);
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Tải file thành công!',
                    showConfirmButton: false,
                    timer: 1500
                });
            })
            .catch(error => {
                console.error('Lỗi khi tải file:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi khi tải file!',
                    text: error.message
                });
            });
    });
});


