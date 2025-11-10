/**
 * Collaborator Install Index Page JavaScript
 * Main file - sử dụng các module riêng biệt
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
        
        // Setup Excel upload (sử dụng module)
        if (typeof CollaboratorInstallExcelUpload !== 'undefined') {
            CollaboratorInstallExcelUpload.setup(this);
        }
        
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
            if (typeof validateDates === 'function') {
                if (!validateDates('#tungay', '#denngay', () => {
                    if (typeof checkFormValidity === 'function') {
                        checkFormValidity();
                    }
                })) {
                    return; // Dừng lại nếu validation fail
                }
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
        if (typeof validateAlphaNumeric === 'function') {
            validateAlphaNumeric('madon', 25, () => {
                if (typeof checkFormValidity === 'function') {
                    checkFormValidity();
                }
            });
        }
        if (typeof validateProductsName === 'function') {
            validateProductsName('sanpham', 50, () => {
                if (typeof checkFormValidity === 'function') {
                    checkFormValidity();
                }
            });
        }
        if (typeof validateAlphaSpace === 'function') {
            validateAlphaSpace('customer_name', 80, () => {
                if (typeof checkFormValidity === 'function') {
                    checkFormValidity();
                }
            });
            validateAlphaSpace('agency_name', 100, () => {
                if (typeof checkFormValidity === 'function') {
                    checkFormValidity();
                }
            });
        }
        if (typeof validateNumeric === 'function') {
            validateNumeric('customer_phone', 11, () => {
                if (typeof checkFormValidity === 'function') {
                    checkFormValidity();
                }
            });
            validateNumeric('agency_phone', 11, () => {
                if (typeof checkFormValidity === 'function') {
                    checkFormValidity();
                }
            });
        }
        
        // Gắn event listener cho input date để validate khi thay đổi
        $('#tungay, #denngay').on('change', () => {
            if (typeof validateDates === 'function') {
                validateDates('#tungay', '#denngay', () => {
                    if (typeof checkFormValidity === 'function') {
                        checkFormValidity();
                    }
                });
            }
        });
        
        // Setup autocomplete cho sản phẩm
        const setupProductAutocomplete = function() {
            if (productList && productList.length > 0) {
                if (typeof setupClientAutoComplete === 'function') {
                    setupClientAutoComplete('#sanpham', '#sanpham-suggestions', productList, null, 10);
                }
                if (typeof setupClickOutsideToHide === 'function') {
                    setupClickOutsideToHide([{ input: '#sanpham', suggestion: '#sanpham-suggestions' }]);
                }
            }
        };
        
        // Đợi common utils load xong trước khi setup autocomplete
        if (window.commonUtilsLoaded && typeof setupClientAutoComplete === 'function') {
            setupProductAutocomplete();
        } else {
            // Đợi event commonUtils:loaded
            document.addEventListener('commonUtils:loaded', function() {
                setupProductAutocomplete();
            });
            // Fallback: thử lại sau 500ms nếu event không trigger
            setTimeout(function() {
                if (typeof setupClientAutoComplete === 'function') {
                    setupProductAutocomplete();
                }
            }, 500);
        }
    },
    
    /**
     * Setup table features
     */
    setupTableFeatures: function() {
        // Đợi common utils load xong trước khi sử dụng các hàm từ utils
        if (window.commonUtilsLoaded && typeof setupRowHighlight === 'function') {
            // Setup row highlight
            setupRowHighlight('#tabContent', 'tbody tr', 'highlight-row');
            
            // Setup drag scroll
            if (typeof setupTableDragScroll === 'function') {
                setupTableDragScroll('#tabContent', '.table-container');
            }
        } else {
            // Đợi event commonUtils:loaded
            document.addEventListener('commonUtils:loaded', function() {
                if (typeof setupRowHighlight === 'function') {
                    setupRowHighlight('#tabContent', 'tbody tr', 'highlight-row');
                }
                if (typeof setupTableDragScroll === 'function') {
                    setupTableDragScroll('#tabContent', '.table-container');
                }
            });
        }
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
