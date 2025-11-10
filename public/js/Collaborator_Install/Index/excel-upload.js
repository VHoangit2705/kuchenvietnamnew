/**
 * Xử lý upload Excel
 */

// Xử lý form đồng bộ dữ liệu mới với upsert
function initExcelUpload() {
    $('#excelUploadFormNew').on('submit', function(e) {
        e.preventDefault();
        uploadExcel(window.ROUTES.upload_excel_sync || '/upload-excel-sync', this, 'excelModalNew');
    });
}

function uploadExcel(url, form, modalId) {
    let formData = new FormData(form);

    // Hiển thị loading với thông tin chi tiết
    Swal.fire({
        title: 'Đang xử lý file ...',
        html: `
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Đang xử lý file Excel với nhiều sheet...</p>
                <small class="text-muted">Vui lòng chờ, quá trình này có thể mất tới vài phút.</small>
                <div class="progress mt-3" style="height: 6px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 100%"></div>
                </div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            // Không cần Swal.showLoading() vì đã có spinner custom
        }
    });

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 3600000, // 60 phút timeout (3600 giây)
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data) {
            Swal.close();
            if (data && data.success) {
                if (data.stats) {
                    // Hiển thị kết quả chi tiết cho chức năng upsert
                    let message = `Đồng bộ thành công!\n\n`;
                    message += `📊 Thống kê:\n`;
                    message += `• Đã xử lý: ${data.stats.imported} dòng\n`;
                    message += `• Sheet đã xử lý: ${data.stats.sheets_processed}\n`;
                    message += `• Tạo mới CTV: ${data.stats.collaborators_created}\n`;
                    message += `• Tạo mới đại lý: ${data.stats.agencies_created}\n`;
                    message += `• Tạo mới đơn hàng: ${data.stats.orders_created}\n`;
                    message += `• Tạo mới lắp đặt: ${data.stats.installation_orders_created}\n`;
                    message += `• Tạo mới bảo hành: ${data.stats.warranty_requests_created}\n`;
                    
                    if (data.stats.errors && data.stats.errors.length > 0) {
                        message += `\n⚠️ Lỗi: ${data.stats.errors.length} dòng\n`;
                        message += `\n📝 Chi tiết lỗi:\n`;
                        data.stats.errors.slice(0, 5).forEach(error => {
                            message += `• ${error}\n`;
                        });
                        if (data.stats.errors.length > 5) {
                            message += `• ... và ${data.stats.errors.length - 5} lỗi khác\n`;
                        }
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        html: message.replace(/\n/g, '<br>'),
                        confirmButtonText: 'OK',
                        width: '600px'
                    });
                } 
                // Đóng modal và reload data
                const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                if (modal) modal.hide();
                
                const tab = localStorage.getItem('activeTab') || 'donhang';
                const formData = $('#searchForm').serialize();
                if (typeof loadTabData === 'function') {
                    loadCounts(formData);
                    loadTabData(tab, formData, 1);
                } else {
                    location.reload();
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data && data.message ? data.message : 'Không rõ kết quả từ server.',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            
            // Xử lý timeout
            if (status === 'timeout') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Timeout!',
                    html: `
                        <p>File quá lớn, quá trình xử lý mất quá nhiều thời gian (hơn 60 phút).</p>
                        <p><strong>Gợi ý:</strong></p>
                        <ul class="text-start">
                            <li>Chia nhỏ file Excel thành nhiều file nhỏ hơn (mỗi file < 50MB)</li>
                            <li>Xóa các sheet không cần thiết</li>
                            <li>Kiểm tra dữ liệu có bị lỗi format không</li>
                            <li>Thử import từng sheet một</li>
                            <li>Liên hệ admin để tăng timeout server nếu cần</li>
                        </ul>
                    `,
                    confirmButtonText: 'OK',
                    width: '600px'
                });
                return;
            }
            
            try {
                const json = JSON.parse(xhr.responseText);
                if (xhr.status === 422) {
                    const msg = json.errors && json.errors.excelFile ? json.errors.excelFile.join(', ') : 'Dữ liệu không hợp lệ.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi validation!',
                        text: msg,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi server!',
                        text: json.message || 'Có lỗi xảy ra!',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi xử lý file!',
                    confirmButtonText: 'OK'
                });
            }
        }
    });
}

