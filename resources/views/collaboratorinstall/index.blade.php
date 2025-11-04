@extends('layout.layout')

@section('content')
<style>
    #collaborator_tab .nav-link.active {
        background-color: #666666 !important;
        color: #ffffff !important;
        border-color: #666666 #666666 transparent !important;
        font-weight: bold;
    }
    
    #collaborator_tab .nav-link.active .count-badge {
        color: #ffffff !important;
        font-weight: bold;
    }
    
    #collaborator_tab .nav-link {
        color: #495057;
        transition: all 0.3s ease;
    }
    
    #collaborator_tab .nav-link:hover:not(.active) {
        background-color: #d9d9d9;
        border-color: #d9d9d9 #d9d9d9 transparent;
    }
</style>
<div class="container mt-4">
    <form id="searchForm">
        <!-- @csrf -->
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="mb-0">
                    Tìm kiếm đơn hàng lắp đặt
                </h4>
            </div>
            <div class="card-body">
                <!-- Hàng 1: Thông tin cơ bản -->
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Mã đơn hàng</label>
                            <input type="text" id="madon" name="madon" class="form-control"
                                placeholder="Nhập mã đơn hàng" value="{{ request('madon') }}" maxlength="25">
                            <div class="invalid-feedback">
                                Lưu ý: chỉ nhập chữ và số.
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 position-relative">
                            <label class="form-label small text-muted">Sản phẩm</label>
                            <input type="text" id="sanpham" name="sanpham" class="form-control"
                                placeholder="Nhập tên sản phẩm" value="{{ request('sanpham') }}" maxlength="50">
                            <div class="invalid-feedback">
                                Lưu ý: chỉ nhập chữ và số.
                            </div>
                            <div id="sanpham-suggestions" class="list-group position-absolute w-100 d-none"
                                style="z-index: 1000; max-height: 200px; overflow-y: auto; border: 1px solid #ced4da;">
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Từ ngày</label>
                            <input type="date" id="tungay" name="tungay" class="form-control"
                                value="{{ request('tungay') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Đến ngày</label>
                            <input type="date" id="denngay" name="denngay" class="form-control"
                                value="{{ request('denngay') }}">
                        </div>
                </div>

                <!-- Hàng 2: Thông tin khách hàng và đại lý -->
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Tên khách hàng</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control"
                                placeholder="Nhập tên khách hàng" value="{{ request('customer_name') }}" maxlength="80">
                            <div class="invalid-feedback">
                                Chỉ nhập chữ
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">SĐT khách hàng</label>
                            <input type="text" id="customer_phone" name="customer_phone" class="form-control"
                                placeholder="Nhập SĐT khách hàng" value="{{ request('customer_phone') }}" maxlength="11">
                            <div class="invalid-feedback">
                                Chỉ nhập số, tối đa 10 chữ số.
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Tên đại lý</label>
                            <input type="text" id="agency_name" name="agency_name" class="form-control"
                                placeholder="Nhập tên đại lý" value="{{ request('agency_name') }}" maxlength="100">
                            <div class="invalid-feedback">
                                Chỉ nhập chữ.
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">SĐT đại lý</label>
                            <input type="text" id="agency_phone" name="agency_phone" class="form-control"
                                placeholder="Nhập SĐT đại lý" value="{{ request('agency_phone') }}" maxlength="11">
                            <div class="invalid-feedback">
                                Chỉ nhập số, tối đa 11 chữ số.
                            </div>
                        </div>
                </div>

                <!-- Hàng 3: Trạng thái và phân loại -->
                <div class="row mb-3">
                   <div class="col-md-6 mb-2">
                            <label class="form-label small text-muted">Trạng thái điều phối</label>
                            <select id="trangthai" name="trangthai" class="form-control">
                                <option value="">-- Chọn trạng thái --</option>
                                <option value="0" {{ request('trangthai') == '0' ? 'selected' : '' }}>Chưa điều phối
                                </option>
                                <option value="1" {{ request('trangthai') == '1' ? 'selected' : '' }}>Đã điều phối
                                </option>
                                <option value="2" {{ request('trangthai') == '2' ? 'selected' : '' }}>Đã hoàn thành
                                </option>
                                <option value="3" {{ request('trangthai') == '3' ? 'selected' : '' }}>Đã thanh toán
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small text-muted">Phân loại lắp đặt</label>
                            <select id="phanloai" name="phanloai" class="form-control">
                                <option value="">-- Chọn phân loại --</option>
                                <option value="collaborator"
                                    {{ request('phanloai') == 'collaborator' ? 'selected' : '' }}>
                                    Cộng tác viên lắp đặt</option>
                                <option value="agency" {{ request('phanloai') == 'agency' ? 'selected' : '' }}>Đại lý lắp
                                    đặt</option>
                            </select>
                        </div>
                </div>

                <!-- Hàng 4: Nút điều khiển -->
                <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type"submit" id="btnSearch" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Tìm kiếm
                                </button>
                                <a href="#" id="reportCollaboratorInstall" class="btn btn-success">
                                    <i class="fas fa-chart-bar me-1"></i>Thống kê
                                </a>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-sync-alt me-1"></i>Đồng Bộ
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" id="dataSynchronizationNew"
                                                data-bs-toggle="modal" data-bs-target="#excelModalNew">
                                                <i class="fas fa-file-excel me-2"></i>Đồng bộ dữ liệu cũ (File Excel cũ)
                                            </a></li>
                                    </ul>
                                </div>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearForm()">
                                <i class="fas fa-eraser me-1"></i>Xóa bộ lọc
                            </button>
                        </div>
                    </div>
                </div>
            </div>
<!-- Modal xem trước báo cáo -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Xem trước báo cáo Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-0">
                <div class="preview-loading text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <iframe src="" style="width: 100%; height: 75vh; border: 0;" class="d-none"></iframe>
            </div>
        </div>
    </div>
    </div>
        </div>
    </form>
</div>
<!-- Modal cho đồng bộ dữ liệu mới với upsert -->
<div class="modal fade" id="excelModalNew" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-sync-alt me-2"></i>Đồng Bộ Dữ Liệu Mới (Upsert)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Chức năng này sẽ:</strong>
                    <ul class="mb-2 mt-2">
                        <li>Tự động tạo cộng tác viên mới nếu chưa có</li>
                        <li>Tự động tạo đại lý mới nếu chưa có</li>
                        <li>Đồng bộ dữ liệu vào các bảng: orders, installation_orders, warranty_requests</li>
                        <li>Xử lý trạng thái và ngày tháng tự động</li>
                        <li><strong>Bỏ qua 2 sheet đầu và 2 sheet cuối</strong></li>
                        <li>Tối ưu hóa cho file lớn với nhiều sheet</li>
                    </ul>
                    <div class="alert alert-warning mt-2 mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Lưu ý:</strong> File lớn có thể mất vài phút để xử lý. Vui lòng kiên nhẫn chờ đợi.
                    </div>
                </div>
                
                <form id="excelUploadFormNew" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="excelFileNew" class="form-label">
                            <i class="fas fa-file-excel me-1"></i>Chọn file Excel (.xlsx, .xls)
                        </label>
                        <input class="form-control" type="file" id="excelFileNew" name="excelFile" accept=".xlsx,.xls" required>
                        <div class="form-text">
                            <strong>Định dạng file:</strong> Cột B=Ngày, C=Tên đại lý, D=SĐT đại lý, F=Tên khách, G=SĐT khách, H=Địa chỉ, I=Thiết bị, J=Tên CTV, K=SĐT CTV, L=Trạng thái, Q=Mã đơn hàng
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Hủy
                </button>
                <button type="submit" form="excelUploadFormNew" class="btn btn-primary">
                    <i class="fas fa-upload me-1"></i>Đồng Bộ
                </button>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-3">
    <div class="d-flex" style="overflow-x: auto; white-space: nowrap;" id="tabHeaderContainer">
        @include('collaboratorinstall.tableheader', ['counts' => $counts, 'activeTab' => $tab ?? 'donhang'])
    </div>
    <!-- Nội dung tab - Lazy load qua AJAX -->
    <div id="tabContent">
        <div class="text-center p-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    // Đặt loadTabData và loadCounts ở global scope
    window.loadTabData = function(tab, formData, page = 1) {
        let url = "{{ route('dieuphoi.tabdata') }}?tab=" + tab + "&page=" + page;
        if (formData) {
            url += "&" + formData;
        }
        
        $('#tabContent').html('<div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        $.get(url, function(response) {
            if (response && response.table) {
                $('#tabContent').html(response.table);
                localStorage.setItem('activeTab', tab);
                
                // Highlight tab active
                $('#collaborator_tab .nav-link').removeClass('active');
                $('#collaborator_tab .nav-link[data-tab="' + tab + '"]').addClass('active');
            }
        }).fail(function() {
            $('#tabContent').html('<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu!</div>');
        });
    };

    window.loadCounts = function(formData, callback, renderHeader) {
        let url = "{{ route('dieuphoi.counts') }}";
        if (formData) {
            url += "?" + formData;
        }
        
        // Hiển thị hiệu ứng loading cho tất cả count badges
        $('.count-badge').each(function() {
            const $badge = $(this);
            const originalText = $badge.text();
            $badge.data('original-text', originalText).html('<span class="spinner-border spinner-border-sm" style="width: 0.75rem; height: 0.75rem;" role="status"><span class="visually-hidden">Loading...</span></span>');
        });
        
        $.get(url, function(counts) {
            if (counts) {
                // Nếu renderHeader = true, render lại toàn bộ tab header
                if (renderHeader === true) {
                    const activeTab = localStorage.getItem('activeTab') || 'donhang';
                    renderTabHeader(counts, activeTab);
                } else {
                    // Chỉ cập nhật counts cho từng tab bằng vòng lặp
                    Object.keys(counts).forEach(function(tabKey) {
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
        }).fail(function() {
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
    };

    window.checkFormValidity = function() {
        // 1. Check tất cả input có class 'is-invalid' bên trong form
        const hasInputErrors = $('#searchForm .is-invalid').length > 0;

        // 2. Check logic ngày tháng (vì nó phức tạp hơn)
        const fromDate = $('#tungay').val();
        const toDate = $('#denngay').val();
        const today = new Date().toISOString().split('T')[0];
        let hasDateErrors = false;

        // Yêu cầu phải nhập cả hai ngày
        if ((fromDate && !toDate) || (!fromDate && toDate)) {
            hasDateErrors = true; // Lỗi thiếu một trong hai ngày
        }
        // Kiểm tra logic khi có cả hai ngày
        if (fromDate && toDate && fromDate > toDate) {
            hasDateErrors = true; // Lỗi ngược ngày
        }
        if (toDate && toDate > today) {
            hasDateErrors = true; // Lỗi ngày tương lai
        }
        if (fromDate && fromDate > today) {
            hasDateErrors = true; // Lỗi ngày tương lai
        }
        // Kiểm tra nếu có class is-invalid trên các input ngày
        if ($('#tungay').hasClass('is-invalid') || $('#denngay').hasClass('is-invalid')) {
            hasDateErrors = true;
        }

        // 3. Vô hiệu hóa nút nếu có BẤT KỲ lỗi nào
        $('#btnSearch').prop('disabled', hasInputErrors || hasDateErrors);
    };

    // Hàm render lại tab header từ counts (KHÔNG dùng API)
    // Render trực tiếp bằng JavaScript dựa trên counts và activeTab
    window.renderTabHeader = function(counts, activeTab) {
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
        
        tabs.forEach(function(tab) {
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
    };

    $(document).ready(function() {
        // Load counts và tab data khi trang mở
        const serverTab = '{{ $tab ?? "donhang" }}';
        const activeTab = serverTab || 'donhang';
        
        localStorage.setItem('activeTab', activeTab);
        
        const formData = $('#searchForm').serialize();
        
        // Load counts trước
        loadCounts(formData);
        
        // Sau đó load tab data
        loadTabData(activeTab, formData, 1);

        // Xử lý click tab 
        $(document).on('click', '#collaborator_tab .nav-link', function(e) {
            e.preventDefault();
            // Nếu đang ở tab active thì bỏ qua, không load lại
            // if ($(this).hasClass('active')) {
            //     return;
            // }
            let tab = $(this).data('tab');
            let formData = $('#searchForm').serialize();
            loadTabData(tab, formData, 1);
        });
          // === HÀM KIỂM TRA TỔNG THỂ VÀ VÔ HIỆU HÓA NÚT ===
            // Hàm checkFormValidity đã được đưa ra global scope ở trên

            // Xử lý validation mã đơn hàng
            const madonInput = $('#madon');
            const maxLength = 25;

            madonInput.on('input', function() {
                let value = $(this).val();
                let sanitizedValue = value.replace(/[^a-zA-Z0-9]/g, '');

                let hasInvalidChars = (value !==
                    sanitizedValue);
                let isTooLong = (sanitizedValue.length >= maxLength);

                if (hasInvalidChars) {
                    $(this).val(sanitizedValue);
                    value = sanitizedValue;
                    isTooLong = (value.length >= maxLength);
                }
                if (hasInvalidChars || isTooLong) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
                checkFormValidity();
            });

            // Xử lý valiation ngày tháng
            function validateDates() {
                const $tungay = $('#tungay');
                const $denngay = $('#denngay');
                const fromDate = $tungay.val();
                const toDate = $denngay.val();
                const today = new Date().toISOString().split('T')[0];

                let isValid = true;

                // Xóa lỗi cũ - xóa cả class và thông báo lỗi
                $tungay.removeClass('is-invalid');
                $denngay.removeClass('is-invalid');
                $tungay.next('.invalid-feedback').remove();
                $denngay.next('.invalid-feedback').remove();

                // Yêu cầu phải nhập cả hai ngày
                if ((fromDate && !toDate) || (!fromDate && toDate)) {
                    if (!fromDate) {
                        $tungay.addClass('is-invalid').after('<div class="invalid-feedback d-block">Vui lòng nhập cả hai ngày.</div>');
                    }
                    if (!toDate) {
                        $denngay.addClass('is-invalid').after('<div class="invalid-feedback d-block">Vui lòng nhập cả hai ngày.</div>');
                    }
                    isValid = false;
                }

                // Kiểm tra ngày tương lai cho "Từ ngày"
                if (fromDate && fromDate > today) {
                    $tungay.addClass('is-invalid');
                    // Chỉ thêm thông báo lỗi nếu chưa có (tránh trùng lặp)
                    if ($tungay.next('.invalid-feedback').length === 0) {
                        $tungay.after('<div class="invalid-feedback d-block">"Từ ngày" không được ở tương lai.</div>');
                    }
                    isValid = false;
                }

                // Kiểm tra ngày tương lai cho "Đến ngày"
                if (toDate && toDate > today) {
                    $denngay.addClass('is-invalid');
                    // Chỉ thêm thông báo lỗi nếu chưa có (tránh trùng lặp)
                    if ($denngay.next('.invalid-feedback').length === 0) {
                        $denngay.after('<div class="invalid-feedback d-block">"Đến ngày" không được ở tương lai.</div>');
                    }
                    isValid = false;
                }

                // Kiểm tra logic khi có cả hai ngày
                if (fromDate && toDate) {
                    if (fromDate > toDate) {
                        $denngay.addClass('is-invalid');
                        // Chỉ thêm thông báo lỗi nếu chưa có (tránh trùng lặp)
                        if ($denngay.next('.invalid-feedback').length === 0) {
                            $denngay.after('<div class="invalid-feedback d-block">"Đến ngày" phải sau hoặc bằng "Từ ngày".</div>');
                        }
                        isValid = false;
                    }
                }

                // GỌI HÀM CHECK TỔNG THỂ
                checkFormValidity();
                return isValid;
            }

            // Hàm Validate nhập tên sản phẩm
            function validateProductsName(inputId, maxLength) {
                const inputField = $('#' + inputId);
                inputField.on('input', function() {
                    let value = $(this).val();
                    // Xóa ký tự không phải chữ/số
                    let sanitizedValue = value.replace(/[^\p{L}\p{N}\s]/gu, '');
                    let hasInvalidChars = (value !== sanitizedValue && value !==
                        '');
                    let isTooLong = (sanitizedValue.length >=
                        maxLength);

                    if (hasInvalidChars) {
                        $(this).val(sanitizedValue);
                        isTooLong = (sanitizedValue.length >= maxLength);
                    } else if (value.length > maxLength) {
                        sanitizedValue = value.substring(0, maxLength);
                        $(this).val(sanitizedValue);
                        isTooLong = true;
                    }

                    if (hasInvalidChars || isTooLong) {
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                    checkFormValidity();
                });
            }
            // Hàm Validate chỉ cho phép Chữ cái & Số (Và giới hạn độ dài)
            function validateAlphaNumeric(inputId, maxLength) {
                const inputField = $('#' + inputId);
                inputField.on('input', function() {
                    let value = $(this).val();
                    // Xóa ký tự không phải chữ/số
                    let sanitizedValue = value.replace(/[^a-zA-Z0-9]/g, '');
                    let hasInvalidChars = (value !== sanitizedValue && value !==
                        '');
                    let isTooLong = (sanitizedValue.length >=
                        maxLength);

                    if (hasInvalidChars) {
                        $(this).val(sanitizedValue);
                        isTooLong = (sanitizedValue.length >= maxLength);
                    } else if (value.length > maxLength) {
                        sanitizedValue = value.substring(0, maxLength);
                        $(this).val(sanitizedValue);
                        isTooLong = true;
                    }

                    if (hasInvalidChars || isTooLong) {
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                    checkFormValidity();
                });
            }

            // Hàm Validate chỉ cho phép Chữ cái & Khoảng trắng (Và giới hạn độ dài)
            function validateAlphaSpace(inputId, maxLength) {
                const inputField = $('#' + inputId);
                inputField.on('input', function() {
                    let value = $(this).val();
                    let sanitizedValue = value.replace(/[^\p{L}\s]/gu, '');

                    if (sanitizedValue.length > maxLength) {
                        sanitizedValue = sanitizedValue.substring(0, maxLength);
                    }

                    let hasError = (value !== sanitizedValue && value !== '');
                    // --- SỬA ĐIỀU KIỆN NÀY ---
                    let isTooLong = (sanitizedValue.length >= maxLength);

                    if (value !== sanitizedValue) {
                        $(this).val(sanitizedValue);
                        value = sanitizedValue;
                        isTooLong = (value.length >= maxLength);
                    }

                    if (hasError || isTooLong) {
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                    checkFormValidity();
                });
            }

            // Hàm Validate chỉ cho phép Số (Và giới hạn độ dài)
            function validateNumeric(inputId, maxLength) {
                const inputField = $('#' + inputId);
                inputField.on('input', function() {
                    let value = $(this).val();
                    let sanitizedValue = value.replace(/[^0-9]/g, '');

                    if (sanitizedValue.length > maxLength) {
                        sanitizedValue = sanitizedValue.substring(0, maxLength);
                    }

                    let hasError = (value !== sanitizedValue && value !== '');
                    let isTooLong = (sanitizedValue.length >= maxLength);

                    if (value !== sanitizedValue) {
                        $(this).val(sanitizedValue);
                        value = sanitizedValue;
                        isTooLong = (value.length >= maxLength);
                    }

                    if (hasError || isTooLong) {
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                    checkFormValidity();
                });
            }

            validateAlphaNumeric('madon', 25);
            validateProductsName('sanpham', 50);
            validateAlphaSpace('customer_name', 80);
            validateNumeric('customer_phone', 11);
            validateAlphaSpace('agency_name', 100);
            validateNumeric('agency_phone', 11);

            // Gắn event listener cho input date để validate khi thay đổi
            $('#tungay, #denngay').on('change', function() {
                validateDates();
            });

            //Phần đánh dấu 1 hàng trong bảng
            $('#tabContent').on('click', 'tbody tr', function() {
            const isHighlighted = $(this).hasClass('highlight-row');
            $('tbody tr').removeClass('highlight-row');
            if (!isHighlighted) {
                $(this).addClass('highlight-row');
            }
            });

            // Dữ liệu sản phẩm từ server
            const productList = {!! json_encode($products ?? []) !!};


            $('#sanpham').on('input', function() {
                const keyword = $(this).val().toLowerCase().trim();
                const $suggestionsBox = $('#sanpham-suggestions');
                $suggestionsBox.empty();

                if (!keyword) {
                    $suggestionsBox.addClass('d-none');
                    return;
                }

                const matchedProducts = productList.filter(productName =>
                    productName.toLowerCase().includes(keyword)
                );

                if (matchedProducts.length > 0) {
                    matchedProducts.slice(0, 10).forEach(productName => {
                        $suggestionsBox.append(
                            `<button type="button" class="list-group-item list-group-item-action">${productName}</button>`
                        );
                    });
                    $suggestionsBox.removeClass('d-none');
                } else {
                    $suggestionsBox.addClass('d-none');
                }
            });

            $(document).on('mousedown', '#sanpham-suggestions button', function(e) {
                e.preventDefault();
                $('#sanpham').val($(this).text());
                $('#sanpham-suggestions').addClass('d-none');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#sanpham, #sanpham-suggestions').length) {
                    $('#sanpham-suggestions').addClass('d-none');
                }
            });

            // Hàm kiêm tra và xử lý khi submit form
            function validateFormFields() {
                let isValid = true;

                // Helper function to check a single field
                function checkField(inputId, regex, maxLength, allowOnlyDigits = false) {
                    const inputField = $('#' + inputId);
                    let value = inputField.val();
                    let sanitizedValue;
                    let currentValid = true;

                    if (allowOnlyDigits) {
                        sanitizedValue = value.replace(/[^0-9]/g, '');
                    } else {
                        if (inputId === 'madon') {
                            sanitizedValue = value.replace(/[^a-zA-Z0-9]/g, '');
                        } else {
                            sanitizedValue = value.replace(/[^\p{L}\s]/gu, '');
                        }
                    }

                    // Kiểm tra ký tự không hợp lệ
                    if (value !== sanitizedValue && value !== '') {
                        // Không set isValid = false ngay, chỉ đánh dấu để thêm class
                        currentValid = false;
                        // Cập nhật giá trị ngay lập tức để kiểm tra độ dài chính xác
                        inputField.val(sanitizedValue);
                        value = sanitizedValue;
                    }

                    // Kiểm tra độ dài
                    if (value.length > maxLength) {
                        currentValid = false;
                        // Cắt bớt nếu cần (dù maxlength đã làm)
                        inputField.val(value.substring(0, maxLength));
                    }

                    // Thêm/xóa class is-invalid dựa trên currentValid
                    if (!currentValid) {
                        inputField.addClass('is-invalid');
                        isValid = false; // Nếu có BẤT KỲ lỗi nào, toàn bộ form là không hợp lệ
                    } else {
                        inputField.removeClass('is-invalid');
                    }
                }

                // Kiểm tra từng trường khi submit
                checkField('madon', /[^a-zA-Z0-9]/g, 25);
                checkField('customer_name', /[^\p{L}\s]/gu, 80);
                checkField('customer_phone', /[^0-9]/g, 11, true);
                checkField('agency_name', /[^\p{L}\s]/gu, 80);
                checkField('agency_phone', /[^0-9]/g, 11, true);

                return isValid; // Trả về true nếu tất cả hợp lệ, false nếu có lỗi
            }
        // Xử lý form search
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate ngày tháng trước khi submit
            if (!validateDates()) {
                return; // Dừng lại nếu validation fail
            }
            
            let tab = localStorage.getItem('activeTab') || 'donhang';
            let formData = $(this).serialize();
            
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
            loadCounts(formData);
            loadTabData(tab, formData, 1);
        });

        // Xử lý phân trang (khi click pagination link)
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            let page = new URL(url).searchParams.get('page') || 1;
            let tab = localStorage.getItem('activeTab') || 'donhang';
            let formData = $('#searchForm').serialize();
            
            loadTabData(tab, formData, page);
        });

        Report();

        //Kéo ngang bảng trong .table-container bằng chuột
        //Kéo ngang bảng trong .table-container bằng chuột
        
        // Biến trạng thái
        let isMouseDown = false; // Cờ cho biết chuột đang được nhấn
        let isGrabbing = false;  // Cờ cho biết chế độ kéo-cuộn đang được kích hoạt
        let startX, scrollLeft;
        let $dragTarget; // Biến lưu trữ container .table-container đang được kéo

        // 1. Gắn sự kiện 'mousedown' vào #tabContent, 
        //    nhưng chỉ lắng nghe cho phần tử con .table-container
        $('#tabContent').on('mousedown', '.table-container', function(e) {
            // Chỉ xử lý khi nhấn chuột trái
            if (e.button !== 0) return;

            // Nếu người dùng click vào scrollbar thì không làm gì cả
            if (e.target.scrollHeight > e.target.clientHeight && e.offsetX > e.target.clientWidth) {
                return;
            }

            isMouseDown = true;
            isGrabbing = false; // Reset cờ kéo-cuộn
            $dragTarget = $(this); // Lưu lại container này

            // Ghi lại vị trí bắt đầu và vị trí cuộn hiện tại
            startX = e.pageX;
            scrollLeft = $dragTarget.scrollLeft();

            // KHÔNG gọi e.preventDefault() ở đây để cho phép chọn văn bản
        });

        // 2. Gắn sự kiện 'mousemove' vào cả trang (document)
        //    để bạn vẫn kéo được ngay cả khi chuột ra khỏi bảng
        $(document).on('mousemove', function(e) {
            if (!isMouseDown || !$dragTarget) return; // Nếu chưa mousedown, bỏ qua

            const x = e.pageX;
            const walk = x - startX;
            
            // Chỉ kích hoạt chế độ kéo-cuộn nếu di chuyển chuột đủ xa (ví dụ: 5px)
            if (!isGrabbing && Math.abs(walk) > 5) {
                isGrabbing = true; // Kích hoạt chế độ kéo-cuộn
                $dragTarget.addClass('is-grabbing'); // Thêm class để đổi con trỏ
            }

            if (!isGrabbing) return; // Nếu chưa ở chế độ kéo-cuộn, không làm gì cả

            e.preventDefault();
            const scrollDistance = walk * 2; // Nhân 2 để kéo nhạy hơn
            
            // Thiết lập vị trí cuộn mới = vị trí cũ - khoảng cách di chuyển
            $dragTarget.scrollLeft(scrollLeft - scrollDistance);
        });

        // 3. Gắn sự kiện 'mouseup' vào cả trang (document)
        //    để dừng kéo khi nhả chuột ở bất cứ đâu
        $(document).on('mouseup', function(e) {
            isMouseDown = false;
            if (isGrabbing) { // Chỉ reset nếu đã ở chế độ kéo-cuộn
                isGrabbing = false;
                if ($dragTarget) {
                    $dragTarget.removeClass('is-grabbing');
                }
                $dragTarget = null; // Xóa mục tiêu
            }
        });

        // 4. Cũng dừng kéo nếu chuột đi ra ngoài cửa sổ trình duyệt
        $(document).on('mouseleave', function() {
            isMouseDown = false;
            if (isGrabbing) { // Chỉ reset nếu đã ở chế độ kéo-cuộn
                isGrabbing = false;
                if ($dragTarget) {
                    $dragTarget.removeClass('is-grabbing');
                }
                $dragTarget = null;
            }
        });

        // 5. Thêm class 'can-grab' vào .table-container
        //    Chúng ta cũng dùng ủy quyền sự kiện cho việc này
        $('#tabContent').on('mouseenter', '.table-container', function() {
            const $container = $(this);
            // Kiểm tra xem bảng có thực sự bị tràn không
            if ($container[0].scrollWidth > $container[0].clientWidth) {
                $container.addClass('can-grab');
            }
        }).on('mouseleave', '.table-container', function() {
            $(this).removeClass('can-grab');
        });
    });
    
    // Hàm xóa bộ lọc
    function clearForm() {
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
        if (typeof window.checkFormValidity === 'function') {
            window.checkFormValidity();
        }

        // Reload dữ liệu với form trống
        const tab = localStorage.getItem('activeTab') || 'donhang';
        // Serialize lại form sau khi reset để đảm bảo formData rỗng
        const formData = $('#searchForm').serialize();
        
        // Load lại counts (giữ nguyên tab thẻ, chỉ cập nhật số counts)
        loadCounts(formData);
        
        // Load lại tab content
        loadTabData(tab, formData, 1);
    }
    
    function Report() {
        $('#reportCollaboratorInstall').on('click', function(e) {
            e.preventDefault();
            const queryParams = new URLSearchParams({
                start_date: $('#tungay').val(),
                end_date: $('#denngay').val()
            });

            Swal.fire({
                title: 'Chọn hành động',
                text: 'Bạn muốn xem trước hay tải Excel?',
                showDenyButton: true,
                confirmButtonText: 'Tải Excel',
                denyButtonText: 'Xem trước',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Đang xuất file...',
                        text: 'Vui lòng chờ trong giây lát',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    fetch(`{{ route('collaborator.export') }}?${queryParams.toString()}`)
                        .then(response => {
                            Swal.close();
                            const contentType = response.headers.get("Content-Type") || '';
                            if (contentType.includes("application/json")) {
                                return response.json().then(json => {
                                    Swal.fire({
                                        icon: 'error',
                                        text: json.message
                                    });
                                });
                            } else {
                                return response.blob().then(blob => {
                                    const url = window.URL.createObjectURL(blob);
                                    const link = document.createElement('a');
                                    link.href = url;
                                    link.download = "KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT.xlsx";
                                    document.body.appendChild(link);
                                    link.click();
                                    document.body.removeChild(link);
                                });
                            }
                        })
                        .catch(() => {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                text: 'Lỗi server.'
                            });
                        });
                } else if (result.isDenied) {
                    queryParams.set('embed', '1');
                    const previewUrl = `{{ route('collaborator.export.preview') }}` + `?${queryParams.toString()}`;
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
                }
            });
        });
    }


    // Xử lý form đồng bộ dữ liệu mới với upsert
    $('#excelUploadFormNew').on('submit', function(e) {
        e.preventDefault();
        uploadExcel('{{ route('upload-excel-sync') }}', this, 'excelModalNew');
    });

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
    // Dọn src khi đóng modal xem trước
    $(document).on('hidden.bs.modal', '#previewModal', function() {
        $('#previewModal iframe').attr('src', '');
        $('#previewModal .preview-loading').removeClass('d-none');
        $('#previewModal iframe').addClass('d-none');
    });
</script>
@endsection