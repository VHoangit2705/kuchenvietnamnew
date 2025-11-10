/**
 * Collaborator Install Index Page JavaScript
 * Xử lý logic chính cho trang quản lý đơn hàng lắp đặt
 */

// Global variables
const CollaboratorInstallIndex = {
    tabDataUrl: '',
    countsUrl: '',
    activeTab: 'donhang',
    
    init: function(tabDataUrl, countsUrl, defaultTab = 'donhang', productList = []) {
        this.tabDataUrl = tabDataUrl;
        this.countsUrl = countsUrl;
        this.activeTab = defaultTab;
        
        localStorage.setItem('activeTab', this.activeTab);
        
        // Load counts và tab data khi trang mở
        const formData = $('#searchForm').serialize();
        this.loadCounts(formData);
        this.loadTabData(this.activeTab, formData, 1);
        
        // Setup event handlers
        this.setupEventHandlers();
        this.setupValidation(productList);
        this.setupTableFeatures();
        this.setupExcelUpload();
        this.setupReport();
    },
    
    /**
     * Load tab data
     */
    loadTabData: function(tab, formData, page = 1) {
        let url = this.tabDataUrl + '?tab=' + tab + '&page=' + page;
        if (formData) {
            url += '&' + formData;
        }
        
        $('#tabContent').html('<div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        $.get(url, (response) => {
            if (response && response.table) {
                $('#tabContent').html(response.table);
                localStorage.setItem('activeTab', tab);
                this.activeTab = tab;
                
                // Highlight tab active
                $('#collaborator_tab .nav-link').removeClass('active');
                $('#collaborator_tab .nav-link[data-tab="' + tab + '"]').addClass('active');
            }
        }).fail(() => {
            $('#tabContent').html('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu!</div>');
        });
    },
    
    /**
     * Load counts for tabs
     */
    loadCounts: function(formData, callback, renderHeader) {
        let url = this.countsUrl;
        if (formData) {
            url += '?' + formData;
        }
        
        // Hiển thị hiệu ứng loading cho tất cả count badges
        $('.count-badge').each(function() {
            const $badge = $(this);
            const originalText = $badge.text();
            $badge.data('original-text', originalText).html('<span class="spinner-border spinner-border-sm" style="width: 0.75rem; height: 0.75rem;" role="status"><span class="visually-hidden">Loading...</span></span>');
        });
        
        $.get(url, (counts) => {
            if (counts) {
                // Nếu renderHeader = true, render lại toàn bộ tab header
                if (renderHeader === true) {
                    const activeTab = localStorage.getItem('activeTab') || 'donhang';
                    this.renderTabHeader(counts, activeTab);
                } else {
                    // Chỉ cập nhật counts cho từng tab bằng vòng lặp
                    Object.keys(counts).forEach((tabKey) => {
                        $('.count-badge[data-count-for="' + tabKey + '"]').text('(' + (counts[tabKey] || 0) + ')');
                    });
                    
                    // Đảm bảo các badge không có trong response vẫn được khôi phục giá trị cũ
                    $('.count-badge').each(function() {
                        const $badge = $(this);
                        // Kiểm tra nếu badge vẫn chứa spinner (nghĩa là chưa được cập nhật)
                        if ($badge.find('.spinner-border').length > 0) {
                            const tabKey = $badge.data('count-for');
                            // Nếu tabKey không có trong counts, khôi phục giá trị cũ
                            if (!counts.hasOwnProperty(tabKey)) {
                                const originalText = $badge.data('original-text');
                                $badge.text(originalText || '(0)');
                            }
                        }
                    });
                }
                
                if (typeof callback === 'function') {
                    callback(counts);
                }
            }
        }).fail(() => {
            // Nếu load thất bại, khôi phục text gốc cho tất cả badges
            $('.count-badge').each(function() {
                const $badge = $(this);
                const originalText = $badge.data('original-text');
                if (originalText) {
                    $badge.text(originalText);
                } else {
                    $badge.text('(0)');
                }
            });
        });
    },
    
    /**
     * Render tab header
     */
    renderTabHeader: function(counts, activeTab) {
        activeTab = activeTab || localStorage.getItem('activeTab') || 'donhang';
        counts = counts || {};
        
        // Định nghĩa danh sách các tab
        const tabs = [
            { key: 'donhang', label: 'ĐƠN HÀNG' },
            { key: 'dieuphoidonhangle', label: 'ĐƠN HÀNG LẺ' },
            { key: 'dieuphoibaohanh', label: 'CA BẢO HÀNH' },
            { key: 'dadieuphoi', label: 'ĐÃ ĐIỀU PHỐI' },
            { key: 'dahoanthanh', label: 'ĐÃ HOÀN THÀNH' },
            { key: 'dathanhtoan', label: 'ĐÃ THANH TOÁN' },
            { key: 'dailylapdat', label: 'ĐẠI LÝ LẮP ĐẶT' }
        ];
        
        // Render HTML
        let html = '<ul class="nav nav-tabs flex-nowrap" id="collaborator_tab">';
        
        tabs.forEach((tab) => {
            const isActive = tab.key === activeTab ? 'active' : '';
            const count = counts[tab.key] || 0;
            
            html += '<li class="nav-item">';
            html += '<a class="nav-link ' + isActive + '" data-tab="' + tab.key + '" href="#">';
            html += tab.label + ' <span class="text-danger count-badge" data-count-for="' + tab.key + '">(' + count + ')</span>';
            html += '</a>';
            html += '</li>';
        });
        
        html += '</ul>';
        
        // Cập nhật HTML vào container
        $('#tabHeaderContainer').html(html);
    },
    
    /**
     * Setup event handlers
     */
    setupEventHandlers: function() {
        // Xử lý click tab
        $(document).on('click', '#collaborator_tab .nav-link', (e) => {
            e.preventDefault();
            let tab = $(e.currentTarget).data('tab');
            let formData = $('#searchForm').serialize();
            this.loadTabData(tab, formData, 1);
        });
        
        // Xử lý form search
        $('#searchForm').on('submit', (e) => {
            e.preventDefault();
            
            // Validate ngày tháng trước khi submit
            if (!validateDates('#tungay', '#denngay', () => {
                checkFormValidity();
            })) {
                return; // Dừng lại nếu validation fail
            }
            
            let tab = localStorage.getItem('activeTab') || 'donhang';
            let formData = $('#searchForm').serialize();
            
            // Tự động chuyển đến tab tương ứng với trạng thái đã chọn
            const selectedStatus = $('#trangthai').val();
            if (selectedStatus !== '') {
                // Mapping trạng thái với tab
                const statusToTabMap = {
                    '0': 'donhang',
                    '1': 'dadieuphoi',
                    '2': 'dahoanthanh',
                    '3': 'dathanhtoan'
                };
                
                if (statusToTabMap.hasOwnProperty(selectedStatus)) {
                    tab = statusToTabMap[selectedStatus];
                    localStorage.setItem('activeTab', tab);
                    
                    // Cập nhật active state cho tab
                    $('#collaborator_tab .nav-link').removeClass('active');
                    $('#collaborator_tab .nav-link[data-tab="' + tab + '"]').addClass('active');
                }
            }
            
            // Load lại counts và tab data
            this.loadCounts(formData);
            this.loadTabData(tab, formData, 1);
        });
        
        // Xử lý phân trang
        $(document).on('click', '.pagination a', (e) => {
            e.preventDefault();
            let url = $(e.currentTarget).attr('href');
            let page = new URL(url).searchParams.get('page') || 1;
            let tab = localStorage.getItem('activeTab') || 'donhang';
            let formData = $('#searchForm').serialize();
            
            this.loadTabData(tab, formData, page);
        });
        
        // Dọn src khi đóng modal xem trước
        $(document).on('hidden.bs.modal', '#previewModal', () => {
            $('#previewModal iframe').attr('src', '');
            $('#previewModal .preview-loading').removeClass('d-none');
            $('#previewModal iframe').addClass('d-none');
        });
    },
    
    /**
     * Setup validation
     */
    setupValidation: function(productList) {
        // Setup validation cho các input
        validateAlphaNumeric('madon', 25, () => {
            checkFormValidity();
        });
        validateProductsName('sanpham', 50, () => {
            checkFormValidity();
        });
        validateAlphaSpace('customer_name', 80, () => {
            checkFormValidity();
        });
        validateNumeric('customer_phone', 11, () => {
            checkFormValidity();
        });
        validateAlphaSpace('agency_name', 100, () => {
            checkFormValidity();
        });
        validateNumeric('agency_phone', 11, () => {
            checkFormValidity();
        });
        
        // Gắn event listener cho input date để validate khi thay đổi
        $('#tungay, #denngay').on('change', () => {
            validateDates('#tungay', '#denngay', () => {
                checkFormValidity();
            });
        });
        
        // Setup autocomplete cho sản phẩm
        if (productList && productList.length > 0) {
            setupClientAutoComplete('#sanpham', '#sanpham-suggestions', productList, null, 10);
            setupClickOutsideToHide([{ input: '#sanpham', suggestion: '#sanpham-suggestions' }]);
        }
    },
    
    /**
     * Setup table features
     */
    setupTableFeatures: function() {
        // Setup row highlight
        setupRowHighlight('#tabContent', 'tbody tr', 'highlight-row');
        
        // Setup drag scroll
        setupTableDragScroll('#tabContent', '.table-container');
    },
    
    /**
     * Setup Excel upload
     */
    setupExcelUpload: function() {
        const self = this;
        $('#excelUploadFormNew').on('submit', function(e) {
            e.preventDefault();
            const url = $(this).data('url') || '';
            self.uploadExcel(url, this, 'excelModalNew');
        });
    },
    
    /**
     * Upload Excel file
     */
    uploadExcel: function(url, form, modalId) {
        const self = this;
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
                    self.loadCounts(formData);
                    self.loadTabData(tab, formData, 1);
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
    },
    
    /**
     * Setup report
     */
    setupReport: function() {
        $('#reportCollaboratorInstall').on('click', (e) => {
            e.preventDefault();
            const queryParams = new URLSearchParams({
                start_date: $('#tungay').val(),
                end_date: $('#denngay').val()
            });
            // Always open Preview directly
            queryParams.set('embed', '1');
            const previewUrl = $('#reportCollaboratorInstall').data('preview-url') + '?' + queryParams.toString();
            const $iframe = $('#previewModal iframe');
            const $spinner = $('#previewModal .preview-loading');
            $spinner.removeClass('d-none');
            $iframe.addClass('d-none');
            $iframe.off('load').on('load', () => {
                $spinner.addClass('d-none');
                $iframe.removeClass('d-none');
            });
            $iframe.attr('src', previewUrl);
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        });
    }
};

// Hàm xóa bộ lọc (global để có thể gọi từ onclick)
window.clearForm = function() {
    // Reset form
    $('#searchForm')[0].reset();
    // Reset các select về giá trị mặc định
    $('#trangthai').val('');
    $('#phanloai').val('');
    // Đảm bảo input date cũng được reset
    $('#tungay').val('');
    $('#denngay').val('');
    
    // Xóa tất cả các class 'is-invalid'
    $('#searchForm .is-invalid').removeClass('is-invalid');
    
    // Trả về nút tìm kiếm về trạng thái ban đầu (enabled)
    $('#btnSearch').prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');
    
    // Kiểm tra lại validation để đảm bảo logic đúng
    if (typeof checkFormValidity === 'function') {
        checkFormValidity();
    }
    
    // Reload dữ liệu với form trống
    const tab = localStorage.getItem('activeTab') || 'donhang';
    // Serialize lại form sau khi reset để đảm bảo formData rỗng
    const formData = $('#searchForm').serialize();
    
    // Load lại counts (giữ nguyên tab thẻ, chỉ cập nhật số counts)
    CollaboratorInstallIndex.loadCounts(formData);
    
    // Load lại tab content
    CollaboratorInstallIndex.loadTabData(tab, formData, 1);
};

// Export functions to global scope for backward compatibility
window.loadTabData = function(tab, formData, page) {
    CollaboratorInstallIndex.loadTabData(tab, formData, page);
};

window.loadCounts = function(formData, callback, renderHeader) {
    CollaboratorInstallIndex.loadCounts(formData, callback, renderHeader);
};

window.renderTabHeader = function(counts, activeTab) {
    CollaboratorInstallIndex.renderTabHeader(counts, activeTab);
};
