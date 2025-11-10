$(document).on("click", "#exportActiveWarranty", function (e) {
    e.preventDefault();

    const COOLDOWN_PERIOD_MS = 1 * 60 * 1000;
    const LAST_EXPORT_KEY = 'lastExportTimestamp_warranty';

    const lastExportTime = localStorage.getItem(LAST_EXPORT_KEY);
    const currentTime = Date.now();

    if (lastExportTime) {
        const timeDiff = currentTime - parseInt(lastExportTime, 10);
        if (timeDiff < COOLDOWN_PERIOD_MS) {
            const timeLeftSeconds = Math.ceil((COOLDOWN_PERIOD_MS - timeDiff) / 1000);
            const minutes = Math.floor(timeLeftSeconds / 60);
            const seconds = timeLeftSeconds % 60;

            Swal.fire({
                icon: 'error',
                title: 'Thao tác quá nhanh!',
                text: `Vui lòng đợi ${minutes} phút ${seconds} giây nữa trước khi xuất lại.`
            });
            return;
        }
    }

    const tungay = $("#tungay").val();
    const denngay = $("#denngay").val();
    const queryParams = new URLSearchParams({
        fromDate: tungay,
        toDate: denngay
    });

    OpenWaitBox();
    fetch(`${window.baoCaoKichHoatBaoHanhRoute || ""}?${queryParams.toString()}`)
        .then(response => {
            CloseWaitBox();
            const contentType = response.headers.get("Content-Type");
            if (contentType && contentType.includes("application/json")) {
                return response.json().then(json => {
                    Swal.fire({
                        icon: 'error',
                        text: json.message
                    });
                });
            } else {
                localStorage.setItem(LAST_EXPORT_KEY, currentTime.toString());
                return response.blob().then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = "Báo cáo kích hoạt bảo hành.xlsx";
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                });
            }
        })
        .catch(error => {
            CloseWaitBox();
            Swal.fire({
                icon: 'error',
                text: 'Lỗi server.'
            });
            console.error(error);
        });
});


