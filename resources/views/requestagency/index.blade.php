@extends('layout.layout')

@section('content')
<style>
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        text-align: center;
        min-width: fit-content;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
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
    .status-cho_kiem_tra {
        background-color: #17a2b8;
        color: #fff;
    }
    /* Đảm bảo cột trạng thái không bị vỡ */
    table th:nth-child(11),
    table td:nth-child(11) {
        min-width: 150px;
        max-width: 200px;
        word-wrap: break-word;
    }
    table td:nth-child(11) {
        text-align: center;
        vertical-align: middle;
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

    /* Badge thông báo trên nút Quản lý xác nhận đại lý */
    .agency-alert-dot {
        width: 14px;
        height: 14px;
        background-color: #dc3545;
        border-radius: 50%;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.4);
        animation: agency-pulse 1.2s infinite;
    }

    @keyframes agency-pulse {
        0% {
            transform: translate(-50%, -50%) scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }
        70% {
            transform: translate(-50%, -50%) scale(1.3);
            box-shadow: 0 0 0 8px rgba(220, 53, 69, 0);
        }
        100% {
            transform: translate(-50%, -50%) scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }
</style>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Quản lý yêu cầu lắp đặt đại lý</h4>
            <div>
                <a href="{{ route('useragency.index') }}" class="btn btn-info me-2">
                    <i class="bi bi-people me-1"></i>Quản lý tài khoản đại lý
                </a>
                <a href="{{ route('requestagency.manage-agencies') }}" class="btn btn-success me-2 position-relative">
                    <i class="bi bi-shield-check me-1"></i>Quản lý xác nhận đại lý
                    @if(!empty($hasFirstTimePendingAgencies) && $hasFirstTimePendingAgencies)
                        <span class="position-absolute top-0 start-100 translate-middle agency-alert-dot">
                            <span class="visually-hidden">Có đại lý chưa xác nhận lần đầu</span>
                        </span>
                    @endif
                </a>
                <a href="{{ route('requestagency.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Thêm mới
                </a>
            </div>
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
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'cho_kiem_tra' ? 'active' : '' }}" 
                href="{{ route('requestagency.index', array_merge(request()->except(['status','page']), ['status' => 'cho_kiem_tra'])) }}">
                Chờ kiểm tra <span class="badge bg-info">({{ $counts['cho_kiem_tra'] }})</span>
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
                            <th style="min-width: 20px;">STT</th>
                            <th style="min-width: 120px;">Mã đơn hàng</th>
                            <th style="min-width: 200px;">Tên sản phẩm</th>
                            <th style="min-width: 150px;">Khách hàng</th>
                            <th style="min-width: 100px;">SĐT</th>
                            <th style="min-width: 150px;">Đại lý</th>
                            <th style="min-width: 120px;">SĐT đại lý</th>
                            <th style="min-width: 120px;">CCCD đại lý</th>
                            <th style="min-width: 250px;">Địa chỉ lắp đặt</th>
                            <th style="min-width: 150px;">Loại yêu cầu</th>
                            <th style="min-width: 150px; text-align: center;">Trạng thái</th>
                            <th style="min-width: 100px;">Ngày tạo</th>
                            <th style="min-width: 50px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $index => $request)
                        <tr>
                            <td>{{ $requests->firstItem() + $index }}</td>
                            <td>
                                <strong class="order-code-link" 
                                    style="{{ isset($hasOtherAgencyFlags[$request->id]) && $hasOtherAgencyFlags[$request->id] ? 'background-color: #ffc107; color: #000; padding: 4px 8px; border-radius: 4px; cursor: pointer;' : 'color: #0d6efd; cursor: pointer; text-decoration: underline;' }}"
                                    data-order-code="{{ $request->order_code }}"
                                    data-product-name="{{ $request->product_name }}"
                                    title="Click để xem chi tiết trong điều phối">
                                    {{ $request->order_code }}
                                </strong>
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
                                {{ $request->type == 0 ? 'Đại lý tự lắp đặt' : ($request->type == 1 ? 'Kuchen cử nhân viên lắp đặt' : 'Yêu cầu đại lý') }}
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <span class="status-badge status-{{ $request->status }}" title="{{ $request->status_name }}">
                                    {{ $request->status_name }}
                                </span>
                            </td>
                            <td>
                                {{ $request->created_at->format('d/m/Y') }}<br>
                                Vào lúc: {{ $request->created_at->format('H:i') }}
                            </td>
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

<script>
    // Xử lý click vào mã đơn hàng để chuyển đến trang chi tiết điều phối
    document.addEventListener('DOMContentLoaded', function() {
        const orderCodeLinks = document.querySelectorAll('.order-code-link');
        
        orderCodeLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const orderCode = this.getAttribute('data-order-code');
                const productName = this.getAttribute('data-product-name');
                
                // Hiển thị loading
                const originalText = this.textContent;
                const originalStyle = this.style.cssText;
                this.textContent = 'Đang tìm...';
                this.style.pointerEvents = 'none';
                this.style.opacity = '0.6';
                
                // Gọi API để tìm InstallationOrder
                const url = '{{ route("requestagency.find-installation-order") }}?order_code=' + 
                           encodeURIComponent(orderCode) + 
                           (productName ? '&product_name=' + encodeURIComponent(productName) : '');
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.url) {
                            window.location.href = data.url;
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Thông báo',
                                    text: data.message || 'Không tìm thấy đơn hàng trong hệ thống điều phối',
                                    confirmButtonText: 'Đóng'
                                }).then(() => {
                                    this.textContent = originalText;
                                    this.style.cssText = originalStyle;
                                    this.style.pointerEvents = 'auto';
                                });
                            } else {
                                alert(data.message || 'Không tìm thấy đơn hàng trong hệ thống điều phối');
                                this.textContent = originalText;
                                this.style.cssText = originalStyle;
                                this.style.pointerEvents = 'auto';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: 'Có lỗi xảy ra khi tìm kiếm đơn hàng',
                                confirmButtonText: 'Đóng'
                            }).then(() => {
                                this.textContent = originalText;
                                this.style.cssText = originalStyle;
                                this.style.pointerEvents = 'auto';
                            });
                        } else {
                            alert('Có lỗi xảy ra khi tìm kiếm đơn hàng');
                            this.textContent = originalText;
                            this.style.cssText = originalStyle;
                            this.style.pointerEvents = 'auto';
                        }
                    });
            });
        });
    });

    function deleteRequest(id) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Bạn chắc chắn?',
                text: 'Yêu cầu lắp đặt sẽ bị xóa vĩnh viễn!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ route('requestagency.destroy', ':id') }}".replace(':id', id);

                    var token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = '{{ csrf_token() }}';
                    form.appendChild(token);

                    var method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';
                    form.appendChild(method);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        } else {
            if (confirm('Bạn có chắc chắn muốn xóa yêu cầu này?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('requestagency.destroy', ':id') }}".replace(':id', id);

                var token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = '{{ csrf_token() }}';
                form.appendChild(token);

                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                document.body.appendChild(form);
                form.submit();
            }
        }
    }
</script>

@endsection
