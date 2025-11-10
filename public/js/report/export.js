$(document).on('click', '#exportExcel', function(e) {
    e.preventDefault();
    const COOLDOWN_PERIOD_MS = 2 * 60 * 1000;
    const LAST_EXPORT_KEY = 'lastExportTimestamp_reportWarranty';
    const lastExportTime = localStorage.getItem(LAST_EXPORT_KEY);
    const currentTime = Date.now();
    if (lastExportTime) {
        const timeDiff = currentTime - parseInt(lastExportTime, 10);
        if (timeDiff < COOLDOWN_PERIOD_MS) {
            const timeLeftSeconds = Math.ceil((COOLDOWN_PERIOD_MS - timeDiff) / 1000);
            const minutes = Math.floor(timeLeftSeconds / 60);
            const seconds = timeLeftSeconds % 60;
            swal.fire({
                icon: 'warning',
                title: 'Vui lòng chờ',
                text: `Bạn vừa xuất file. Vui lòng chờ ${minutes} phút ${seconds} giây trước khi xuất tiếp.`,
            });
            return;
        }
    }
    Swal.fire({
        title: 'Đang xuất file...',
        text: 'Vui lòng chờ trong giây lát',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    var params = (window.reportParams || {});
    const exportUrl = (window.exportReportRoute || '');
    fetch(exportUrl + "?" + new URLSearchParams(params))
        .then(response => {
            Swal.close();
            const contentType = response.headers.get("Content-Type");
            if (contentType && contentType.includes("application/json")) {
                return response.json().then(json => {
                    Swal.fire({
                        icon: 'error',
                        text: json.message
                    });
                });
            } else {
                return response.blob().then(blob => {
                    localStorage.setItem(LAST_EXPORT_KEY, currentTime.toString());
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = "bao_cao_bao_hanh.xlsx";
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                icon: 'error',
                text: 'Lỗi server.'
            });
            console.error(error);
        });
});


