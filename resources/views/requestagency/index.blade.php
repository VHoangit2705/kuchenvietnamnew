@extends('layout.layout')

@section('content')
<style>
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-chua_xac_nhan_daily {
        background-color: #ff0000;
        color: #fff;
    }
    .status-da_xac_nhan_daily {
        background-color: #ff9800;
        color: #fff;
    }
    .status-da_dieu_phoi {
        background-color: #ffc107;
        color: #000;
    }
    .status-hoan_thanh {
        background-color: #198754;
        color: #fff;
    }
    .status-da_thanh_toan {
        background-color: #0DCAEF;
        color: #fff;
    }
    .nav-tabs .nav-link.active {
        background-color: #666666 !important;
        color: #ffffff !important;
        border-color: #666666 #666666 transparent !important;
        font-weight: bold;
    }
    .table-container {
        overflow-x: auto;
        max-width: 100%;
    }
</style>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Quản lý yêu cầu lắp đặt đại lý</h4>
            <a href="{{ route('requestagency.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Thêm mới
            </a>
        </div>
        <div class="card-body">
            <!-- Form tìm kiếm -->
            <form method="GET" action="{{ route('requestagency.index') }}" id="searchForm">
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Mã đơn hàng</label>
                        <input type="text" name="order_code" class="form-control" 
                            placeholder="Nhập mã đơn hàng" value="{{ request('order_code') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Tên khách hàng</label>
                        <input type="text" name="customer_name" class="form-control" 
                            placeholder="Nhập tên khách hàng" value="{{ request('customer_name') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Số điện thoại</label>
                        <input type="text" name="customer_phone" class="form-control" 
                            placeholder="Nhập số điện thoại" value="{{ request('customer_phone') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Tên đại lý</label>
                        <input type="text" name="agency_name" class="form-control" 
                            placeholder="Nhập tên đại lý" value="{{ request('agency_name') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Số điện thoại đại lý</label>
                        <input type="text" name="agency_phone" class="form-control" 
                            placeholder="Nhập số điện thoại đại lý" value="{{ request('agency_phone') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">CCCD đại lý</label>
                        <input type="text" name="agency_cccd" class="form-control" 
                            placeholder="Nhập CCCD đại lý" value="{{ request('agency_cccd') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Từ ngày</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small text-muted">Đến ngày</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i>Tìm kiếm
                        </button>
                        <a href="{{ route('requestagency.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabs theo trạng thái -->
<div class="container-fluid mt-3">
    <ul class="nav nav-tabs" id="statusTabs">
        <li class="nav-item">
            <a class="nav-link {{ !request('status') ? 'active' : '' }}" 
                href="{{ route('requestagency.index') }}">
                Tất cả <span class="badge bg-secondary">({{ $counts['all'] }})</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'chua_xac_nhan_daily' ? 'active' : '' }}" 
                href="{{ route('requestagency.index', array_merge(request()->except(['status','page']), ['status' => 'chua_xac_nhan_daily'])) }}">
                Chưa xác nhận đại lý <span class="badge bg-danger">({{ $counts['chua_xac_nhan_agency'] }})</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'da_xac_nhan_daily' ? 'active' : '' }}" 
                href="{{ route('requestagency.index', array_merge(request()->except(['status','page']), ['status' => 'da_xac_nhan_daily'])) }}">
                Đã xác nhận đại lý <span class="badge bg-warning">({{ $counts['da_xac_nhan_agency'] }})</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'da_dieu_phoi' ? 'active' : '' }}" 
                href="{{ route('requestagency.index', array_merge(request()->except(['status','page']), ['status' => 'da_dieu_phoi'])) }}">
                Đã điều phối <span class="badge bg-info">({{ $counts['da_dieu_phoi'] }})</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'hoan_thanh' ? 'active' : '' }}" 
                href="{{ route('requestagency.index', array_merge(request()->except(['status','page']), ['status' => 'hoan_thanh'])) }}">
                Hoàn thành <span class="badge bg-success">({{ $counts['hoan_thanh'] }})</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'da_thanh_toan' ? 'active' : '' }}" 
                href="{{ route('requestagency.index', array_merge(request()->except(['status','page']), ['status' => 'da_thanh_toan'])) }}">
                Đã thanh toán <span class="badge bg-secondary">({{ $counts['da_thanh_toan'] }})</span>
            </a>
        </li>
    </ul>

    <!-- Bảng dữ liệu -->
    <div class="card mt-3">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width: 80px;">STT</th>
                            <th style="min-width: 120px;">Mã đơn hàng</th>
                            <th style="min-width: 200px;">Tên sản phẩm</th>
                            <th style="min-width: 150px;">Khách hàng</th>
                            <th style="min-width: 120px;">SĐT</th>
                            <th style="min-width: 200px;">Đại lý</th>
                            <th style="min-width: 120px;">SĐT đại lý</th>
                            <th style="min-width: 120px;">CCCD đại lý</th>
                            <th style="min-width: 250px;">Địa chỉ lắp đặt</th>
                            <th style="min-width: 120px;">Trạng thái</th>
                            <th style="min-width: 150px;">Ngày tạo</th>
                            <th style="min-width: 150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $index => $request)
                        <tr>
                            <td>{{ $requests->firstItem() + $index }}</td>
                            <td>
                                <strong>{{ $request->order_code }}</strong>
                            </td>
                            <td>{{ $request->product_name }}</td>
                            <td>{{ $request->customer_name }}</td>
                            <td>{{ $request->customer_phone }}</td>
                            <td>
                                @if($request->agency)
                                    {{ $request->agency->name ?? '-' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($request->agency)
                                    {{ $request->agency->phone ?? '-' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($request->agency)
                                    {{ $request->agency->cccd ?? '-' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                    title="{{ $request->installation_address }}">
                                    {{ $request->installation_address }}
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-{{ $request->status }}">
                                    {{ $request->status_name }}
                                </span>
                            </td>
                            <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('requestagency.show', $request->id) }}" 
                                        class="btn btn-info" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('requestagency.edit', $request->id) }}" 
                                        class="btn btn-warning" title="Chỉnh sửa">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger" 
                                        onclick="deleteRequest({{ $request->id }})" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="mt-2 text-muted">Không có dữ liệu</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($requests->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                {{ $requests->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa yêu cầu lắp đặt này?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteRequest(id) {
    const form = document.getElementById('deleteForm');
    form.action = '{{ route("requestagency.index") }}/' + id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

@if(session('success'))
    toastr.success('{{ session('success') }}');
@endif

@if(session('error'))
    toastr.error('{{ session('error') }}');
@endif
</script>
@endsection

