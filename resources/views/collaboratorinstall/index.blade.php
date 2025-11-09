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
    <div class="modal-dialog modal-dialog-centered modal-fullscreen modal-dialog-scrollable" style="max-width: 108vw;">
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
                <iframe src="" style="width: 100%; height: 95vh; border: 0;" class="d-none"></iframe>
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

<script src="{{ asset('js/common.js') }}"></script>
<script src="{{ asset('js/validate_input/collaboratorinstall.js') }}"></script>
<script src="{{ asset('js/collaboratorinstall/index.js') }}"></script>
<script>
    $(document).ready(function() {
        // Initialize CollaboratorInstallIndex
        CollaboratorInstallIndex.init(
            '{{ route("dieuphoi.tabdata") }}',
            '{{ route("dieuphoi.counts") }}',
            '{{ $tab ?? "donhang" }}',
            {!! json_encode($products ?? []) !!}
        );
        
        // Setup Excel upload URL
        $('#excelUploadFormNew').data('url', '{{ route('upload-excel-sync') }}');
        
        // Setup report preview URL
        $('#reportCollaboratorInstall').data('preview-url', '{{ route('collaborator.export.preview') }}');
    });
</script>
@endsection