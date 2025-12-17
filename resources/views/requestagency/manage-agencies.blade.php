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
    .table-container {
        overflow-x: auto;
        max-width: 100%;
    }
    .first-time-badge {
        background-color: #ffc107;
        color: #000;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
    }
</style>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-shield-check me-2"></i>Quản lý xác nhận đại lý lần đầu
            </h4>
            <div>
                <a href="{{ route('requestagency.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Form tìm kiếm -->
            <form method="GET" action="{{ route('requestagency.manage-agencies') }}" id="searchForm">
                <div class="row mb-3">
                    <div class="col-md-10 mb-2">
                        <label class="form-label small text-muted">Tìm kiếm (Tên đại lý, SĐT, CCCD, Mã đơn hàng, Tên khách hàng)</label>
                        <input type="text" name="search" class="form-control" 
                            placeholder="Nhập thông tin cần tìm..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2 w-100">
                            <i class="bi bi-search me-1"></i>Tìm kiếm
                        </button>
                    </div>
                </div>
            </form>

            <!-- Thông báo -->
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Lưu ý:</strong> Danh sách này chỉ hiển thị các đại lý gửi yêu cầu lần đầu và chưa được xác nhận. 
                Sau khi xác nhận, đại lý sẽ không còn xuất hiện trong danh sách này.
            </div>

            <!-- Form xác nhận nhiều -->
            <form id="bulkConfirmForm" method="POST" action="{{ route('requestagency.confirm-multiple') }}">
                @csrf
                <div class="mb-3">
                    <button type="button" class="btn btn-success" id="selectAllBtn">
                        <i class="bi bi-check-all me-1"></i>Chọn tất cả
                    </button>
                    <button type="button" class="btn btn-secondary" id="deselectAllBtn">
                        <i class="bi bi-x-square me-1"></i>Bỏ chọn tất cả
                    </button>
                    <button type="submit" class="btn btn-primary" id="confirmSelectedBtn" disabled>
                        <i class="bi bi-check-circle me-1"></i>Xác nhận đã chọn
                    </button>
                    <span id="selectedCount" class="ms-2 text-muted">Đã chọn: 0</span>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bảng dữ liệu -->
<div class="container-fluid mt-3">
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="min-width: 50px;">
                                <input type="checkbox" id="selectAllCheckbox">
                            </th>
                            <th style="min-width: 20px;">STT</th>
                            <th style="min-width: 120px;">Mã đơn hàng</th>
                            <th style="min-width: 200px;">Tên sản phẩm</th>
                            <th style="min-width: 150px;">Khách hàng</th>
                            <th style="min-width: 100px;">SĐT KH</th>
                            <th style="min-width: 200px;">Đại lý</th>
                            <th style="min-width: 120px;">SĐT đại lý</th>
                            <th style="min-width: 120px;">CCCD đại lý</th>
                            <th style="min-width: 250px;">Địa chỉ lắp đặt</th>
                            <th style="min-width: 120px;">Trạng thái</th>
                            <th style="min-width: 100px;">Ngày tạo</th>
                            <th style="min-width: 150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginator as $index => $request)
                        <tr>
                            <td>
                                <input type="checkbox" name="request_ids[]" 
                                    value="{{ $request->id }}" 
                                    class="request-checkbox">
                            </td>
                            <td>{{ $paginator->firstItem() + $index }}</td>
                            <td>
                                <strong>{{ $request->order_code }}</strong>
                                <br>
                                <span class="first-time-badge">Lần đầu</span>
                            </td>
                            <td>{{ $request->product_name }}</td>
                            <td>{{ $request->customer_name }}</td>
                            <td>{{ $request->customer_phone }}</td>
                            <td>
                                @if($request->agency)
                                    <strong>{{ $request->agency->name ?? '-' }}</strong>
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
                            <td>
                                {{ $request->created_at->format('d/m/Y') }}<br>
                                <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('requestagency.confirm-agency-form', $request->id) }}" 
                                        class="btn btn-success" title="Xác nhận đại lý">
                                        <i class="bi bi-check-circle me-1"></i>Xác nhận
                                    </a>
                                    <a href="{{ route('requestagency.show', $request->id) }}" 
                                        class="btn btn-info" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="mt-2 text-muted">Không có đại lý nào cần xác nhận</p>
                                <a href="{{ route('requestagency.index') }}" class="btn btn-primary mt-2">
                                    <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($paginator->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                {{ $paginator->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal xác nhận hàng loạt -->
<div class="modal fade" id="bulkConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận đại lý</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xác nhận <strong id="selectedCountText">0</strong> đại lý đã chọn?</p>
                <div class="mb-3">
                    <label class="form-label">Người xác nhận</label>
                    <input type="text" name="received_by" class="form-control" 
                        value="{{ session('user', 'system') }}" placeholder="Tên người xác nhận">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="confirmBulkBtn">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const confirmSelectedBtn = document.getElementById('confirmSelectedBtn');
    const requestCheckboxes = document.querySelectorAll('.request-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const bulkConfirmForm = document.getElementById('bulkConfirmForm');
    const bulkConfirmModal = new bootstrap.Modal(document.getElementById('bulkConfirmModal'));

    // Cập nhật số lượng đã chọn
    function updateSelectedCount() {
        const checked = document.querySelectorAll('.request-checkbox:checked').length;
        selectedCount.textContent = `Đã chọn: ${checked}`;
        confirmSelectedBtn.disabled = checked === 0;
        selectAllCheckbox.checked = checked > 0 && checked === requestCheckboxes.length;
        selectAllCheckbox.indeterminate = checked > 0 && checked < requestCheckboxes.length;
    }

    // Chọn tất cả
    selectAllCheckbox.addEventListener('change', function() {
        requestCheckboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });

    selectAllBtn.addEventListener('click', function() {
        requestCheckboxes.forEach(cb => cb.checked = true);
        selectAllCheckbox.checked = true;
        updateSelectedCount();
    });

    deselectAllBtn.addEventListener('click', function() {
        requestCheckboxes.forEach(cb => cb.checked = false);
        selectAllCheckbox.checked = false;
        updateSelectedCount();
    });

    // Cập nhật khi checkbox thay đổi
    requestCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    // Xác nhận hàng loạt
    confirmSelectedBtn.addEventListener('click', function() {
        const checked = document.querySelectorAll('.request-checkbox:checked');
        if (checked.length === 0) {
            toastr.warning('Vui lòng chọn ít nhất một đại lý để xác nhận!');
            return;
        }
        
        // Cập nhật form với các ID đã chọn
        const form = document.getElementById('bulkConfirmForm');
        const requestIdsInput = form.querySelector('input[name="request_ids[]"]');
        
        // Xóa các input cũ
        form.querySelectorAll('input[name="request_ids[]"]').forEach(input => input.remove());
        
        // Thêm các input mới
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'request_ids[]';
            input.value = cb.value;
            form.appendChild(input);
        });
        
        document.getElementById('selectedCountText').textContent = checked.length;
        bulkConfirmModal.show();
    });

    document.getElementById('confirmBulkBtn').addEventListener('click', function() {
        const receivedBy = document.querySelector('#bulkConfirmModal input[name="received_by"]').value;
        const form = document.getElementById('bulkConfirmForm');
        
        // Thêm received_by vào form
        let receivedByInput = form.querySelector('input[name="received_by"]');
        if (!receivedByInput) {
            receivedByInput = document.createElement('input');
            receivedByInput.type = 'hidden';
            receivedByInput.name = 'received_by';
            form.appendChild(receivedByInput);
        }
        receivedByInput.value = receivedBy;

        // Lấy CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                         document.querySelector('input[name="_token"]')?.value;

        // Gửi form
        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                request_ids: Array.from(form.querySelectorAll('input[name="request_ids[]"]')).map(i => i.value),
                received_by: receivedBy
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                bulkConfirmModal.hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(data.message || 'Có lỗi xảy ra!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Có lỗi xảy ra khi xác nhận!');
        });
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});
</script>
@endsection
