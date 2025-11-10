/**
 * Excel Upload Module for Collaborator Install
 * Xử lý upload và import file Excel
 */

const CollaboratorInstallExcelUpload = {
    /**
     * Setup Excel upload form handler
     * @param {Object} context - Context object với các methods loadCounts, loadTabData
     */
    setup: function(context) {
        const self = this;
        $('#excelUploadFormNew').on('submit', function(e) {
            e.preventDefault();
            const url = $(this).data('url') || '';
            self.upload(url, this, 'excelModalNew', context);
        });
    },
    
    /**
     * Upload Excel file
     * @param {string} url - Upload URL
     * @param {HTMLElement} form - Form element
     * @param {string} modalId - Modal ID
     * @param {Object} context - Context object với các methods loadCounts, loadTabData
     */
    upload: function(url, form, modalId, context) {
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
            showConfirmButton: false
        });
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 3600000, // 60 phút timeout
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                Swal.close();
                if (data && data.success) {
                    if (data.stats) {
                        // Hiển thị kết quả chi tiết
                        let message = `Đồng bộ thành công!<br><br>`;
                        message += `📊 Thống kê:<br>`;
                        message += `• Đã xử lý: ${data.stats.imported} dòng<br>`;
                        message += `• Sheet đã xử lý: ${data.stats.sheets_processed}<br>`;
                        message += `• Tạo mới CTV: ${data.stats.collaborators_created}<br>`;
                        message += `• Tạo mới đại lý: ${data.stats.agencies_created}<br>`;
                        message += `• Tạo mới đơn hàng: ${data.stats.orders_created}<br>`;
                        message += `• Tạo mới lắp đặt: ${data.stats.installation_orders_created}<br>`;
                        message += `• Tạo mới bảo hành: ${data.stats.warranty_requests_created}<br>`;
                        
                        if (data.stats.errors && data.stats.errors.length > 0) {
                            message += `<br>⚠️ Lỗi: ${data.stats.errors.length} dòng<br>`;
                            message += `<br>📝 Chi tiết lỗi:<br>`;
                            data.stats.errors.slice(0, 5).forEach(error => {
                                message += `• ${error}<br>`;
                            });
                            if (data.stats.errors.length > 5) {
                                message += `• ... và ${data.stats.errors.length - 5} lỗi khác<br>`;
                            }
                        }
                        
                        showSwalMessage('success', 'Thành công!', message, {
                            width: '600px'
                        });
                    }
                    
                    // Đóng modal và reload data
                    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                    if (modal) modal.hide();
                    
                    const tab = localStorage.getItem('activeTab') || 'donhang';
                    const formData = $('#searchForm').serialize();
                    if (context && typeof context.loadCounts === 'function') {
                        context.loadCounts(formData);
                    }
                    if (context && typeof context.loadTabData === 'function') {
                        context.loadTabData(tab, formData, 1);
                    }
                } else {
                    showSwalMessage('error', 'Lỗi!', data && data.message ? data.message : 'Không rõ kết quả từ server.');
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                
                // Xử lý timeout
                if (status === 'timeout') {
                    showSwalMessage('warning', 'Timeout!', 'File quá lớn, quá trình xử lý mất quá nhiều thời gian (hơn 60 phút).', {
                        width: '600px'
                    });
                    return;
                }
                
                try {
                    const json = JSON.parse(xhr.responseText);
                    if (xhr.status === 422) {
                        const msg = json.errors && json.errors.excelFile ? json.errors.excelFile.join(', ') : 'Dữ liệu không hợp lệ.';
                        showSwalMessage('error', 'Lỗi validation!', msg);
                    } else {
                        showSwalMessage('error', 'Lỗi server!', json.message || 'Có lỗi xảy ra!');
                    }
                } catch (e) {
                    showSwalMessage('error', 'Lỗi!', 'Có lỗi xảy ra khi xử lý file!');
                }
            }
        });
    }
};

