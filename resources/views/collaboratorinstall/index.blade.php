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
                        <input type="text" id="madon" name="madon" class="form-control" placeholder="Nhập mã đơn hàng" value="{{ request('madon') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Sản phẩm</label>
                        <input type="text" id="sanpham" name="sanpham" class="form-control" placeholder="Nhập tên sản phẩm" value="{{ request('sanpham') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Từ ngày</label>
                        <input type="date" id="tungay" name="tungay" class="form-control" value="{{ request('tungay') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Đến ngày</label>
                        <input type="date" id="denngay" name="denngay" class="form-control" value="{{ request('denngay') }}">
                    </div>
                </div>

                <!-- Hàng 2: Thông tin khách hàng và đại lý -->
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Tên khách hàng</label>
                        <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="Nhập tên khách hàng" value="{{ request('customer_name') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">SĐT khách hàng</label>
                        <input type="text" id="customer_phone" name="customer_phone" class="form-control" placeholder="Nhập SĐT khách hàng" value="{{ request('customer_phone') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Tên đại lý</label>
                        <input type="text" id="agency_name" name="agency_name" class="form-control" placeholder="Nhập tên đại lý" value="{{ request('agency_name') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">SĐT đại lý</label>
                        <input type="text" id="agency_phone" name="agency_phone" class="form-control" placeholder="Nhập SĐT đại lý" value="{{ request('agency_phone') }}">
                    </div>
                </div>

                <!-- Hàng 3: Trạng thái và phân loại -->
                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label class="form-label small text-muted">Trạng thái điều phối</label>
                        <select id="trangthai" name="trangthai" class="form-control">
                            <option value="">-- Chọn trạng thái --</option>
                            <option value="0" {{ request('trangthai') == '0' ? 'selected' : '' }}>Chưa điều phối</option>
                            <option value="1" {{ request('trangthai') == '1' ? 'selected' : '' }}>Đã điều phối</option>
                            <option value="2" {{ request('trangthai') == '2' ? 'selected' : '' }}>Đã hoàn thành</option>
                            <option value="3" {{ request('trangthai') == '3' ? 'selected' : '' }}>Đã thanh toán</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label small text-muted">Phân loại lắp đặt</label>
                        <select id="phanloai" name="phanloai" class="form-control">
                            <option value="">-- Chọn phân loại --</option>
                            <option value="collaborator" {{ request('phanloai') == 'collaborator' ? 'selected' : '' }}>Cộng tác viên lắp đặt</option>
                            <option value="agency" {{ request('phanloai') == 'agency' ? 'selected' : '' }}>Đại lý lắp đặt</option>
                        </select>
                    </div>
                </div>

                <!-- Hàng 4: Nút điều khiển -->
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Tìm kiếm
                            </button>
                            <a href="#" id="reportCollaboratorInstall" class="btn btn-success">
                                <i class="fas fa-chart-bar me-1"></i>Thống kê
                            </a>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-sync-alt me-1"></i>Đồng Bộ
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" id="dataSynchronizationNew" data-bs-toggle="modal" data-bs-target="#excelModalNew">
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
    <div class="d-flex" style="overflow-x: auto; white-space: nowrap;">
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

    window.loadCounts = function(formData) {
        let url = "{{ route('dieuphoi.counts') }}";
        if (formData) {
            url += "?" + formData;
        }
        
        $.get(url, function(counts) {
            if (counts) {
                // Cập nhật counts cho từng tab
                Object.keys(counts).forEach(function(tabKey) {
                    $('.count-badge[data-count-for="' + tabKey + '"]').text('(' + (counts[tabKey] || 0) + ')');
                });
            }
        });
    };

    $(document).ready(function() {
        const activeTab = 'donhang';
        localStorage.setItem('activeTab', activeTab);
        const formData = $('#searchForm').serialize();
        
        $('#collaborator_tab .nav-link').removeClass('active');
        $('#collaborator_tab .nav-link[data-tab="' + activeTab + '"]').addClass('active');
        
        // Load counts trước
        loadCounts(formData);
        
        // Sau đó load tab data
        loadTabData(activeTab, formData, 1);

        // Xử lý click tab
        $('#collaborator_tab').on('click', '.nav-link', function(e) {
            e.preventDefault();
            // Nếu đang ở tab active thì bỏ qua, không load lại
            if ($(this).hasClass('active')) {
                return;
            }
            let tab = $(this).data('tab');
            let formData = $('#searchForm').serialize();
            loadTabData(tab, formData, 1);
        });

        // Xử lý form search
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            let tab = localStorage.getItem('activeTab') || 'donhang';
            let formData = $(this).serialize();
            
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
    });
    
    // Hàm xóa bộ lọc
    function clearForm() {
        $('#searchForm')[0].reset();
        // Reset các select về giá trị mặc định
        $('#trangthai').val('');
        $('#phanloai').val('');
        
        // Reload dữ liệu với form trống
        const tab = localStorage.getItem('activeTab') || 'donhang';
        loadCounts('');
        loadTabData(tab, '', 1);
    }

    function Report() {
        $('#reportCollaboratorInstall').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Đang xuất file...',
                text: 'Vui lòng chờ trong giây lát',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            const queryParams = new URLSearchParams({
                start_date: $('#tungay').val(),
                end_date: $('#denngay').val()
            });
            fetch(`{{ route('collaborator.export') }}?${queryParams.toString()}`)
                .then(response => {
                    Swal.close();
                    const contentType = response.headers.get("Content-Type");
                    if (contentType.includes("application/json")) {
                        hasError = true;
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
                .catch(error => {
                    Swal.close();
                    hasError = true;
                    Swal.fire({
                        icon: 'error',
                        text: 'Lỗi server.'
                    });
                })
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
</script>
@endsection