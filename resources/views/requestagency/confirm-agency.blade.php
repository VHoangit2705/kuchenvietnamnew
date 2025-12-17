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
    .info-box {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .warning-box {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
</style>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-shield-check me-2"></i>Xác nhận đại lý
            </h4>
            <div>
                <a href="{{ route('requestagency.manage-agencies') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($isFirstRequest)
            <div class="info-box">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Đây là yêu cầu đầu tiên của đại lý này.</strong> Sau khi xác nhận, đại lý sẽ được kích hoạt và có thể tiếp tục gửi các yêu cầu khác.
            </div>
            @else
            <div class="warning-box">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Lưu ý:</strong> Đại lý này đã có các yêu cầu trước đó nhưng chưa được xác nhận. 
                Việc xác nhận sẽ kích hoạt đại lý cho tất cả các yêu cầu.
            </div>
            @endif

            <!-- Thông tin yêu cầu -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Thông tin yêu cầu lắp đặt</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Mã đơn hàng</label>
                            <div class="form-control-plaintext">
                                <strong>{{ $request->order_code }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Trạng thái</label>
                            <div>
                                <span class="status-badge status-{{ $request->status }}">
                                    {{ $request->status_name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label text-muted">Tên sản phẩm</label>
                            <div class="form-control-plaintext">
                                {{ $request->product_name }}
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Họ tên khách hàng</label>
                            <div class="form-control-plaintext">
                                {{ $request->customer_name }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Số điện thoại khách hàng</label>
                            <div class="form-control-plaintext">
                                {{ $request->customer_phone }}
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label text-muted">Địa chỉ lắp đặt</label>
                            <div class="form-control-plaintext">
                                {{ $request->installation_address }}
                            </div>
                        </div>
                    </div>

                    @if($request->notes)
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label text-muted">Ghi chú</label>
                            <div class="form-control-plaintext">
                                {{ $request->notes }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Thông tin đại lý -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Thông tin đại lý</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Tên đại lý</label>
                            <div class="form-control-plaintext">
                                <strong>
                                    @if($request->agency)
                                        {{ $request->agency->name ?? '-' }}
                                    @else
                                        <span class="text-danger">Chưa có thông tin đại lý</span>
                                    @endif
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Số điện thoại đại lý</label>
                            <div class="form-control-plaintext">
                                @if($request->agency)
                                    {{ $request->agency->phone ?? '-' }}
                                @else
                                    <span class="text-danger">-</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($request->agency && $request->agency->cccd)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">CCCD đại lý</label>
                            <div class="form-control-plaintext">
                                {{ $request->agency->cccd }}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($request->agency && $request->agency->address)
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label text-muted">Địa chỉ đại lý</label>
                            <div class="form-control-plaintext">
                                {{ $request->agency->address }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Ngày tạo yêu cầu</label>
                            <div class="form-control-plaintext">
                                {{ $request->created_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form xác nhận -->
            <form method="POST" action="{{ route('requestagency.confirm-agency', $request->id) }}" id="confirmForm">
                @csrf
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Xác nhận đại lý</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Người xác nhận <span class="text-danger">*</span></label>
                                <input type="text" name="received_by" class="form-control" 
                                    value="{{ session('user', 'system') }}" 
                                    placeholder="Nhập tên người xác nhận" required>
                                <small class="form-text text-muted">Người thực hiện xác nhận đại lý này</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Ghi chú xác nhận</label>
                                <textarea name="notes" class="form-control" rows="3" 
                                    placeholder="Nhập ghi chú (nếu có)"></textarea>
                                <small class="form-text text-muted">Ghi chú sẽ được thêm vào phần ghi chú của yêu cầu</small>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Lưu ý:</strong> Sau khi xác nhận, trạng thái sẽ chuyển từ "Chưa xác nhận đại lý" sang "Đã xác nhận đại lý" 
                            và đại lý sẽ có thể tiếp tục gửi các yêu cầu khác.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('requestagency.manage-agencies') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Hủy
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i>Xác nhận đại lý
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('confirmForm');
    
    form.addEventListener('submit', function(e) {
        if (!confirm('Bạn có chắc chắn muốn xác nhận đại lý này?')) {
            e.preventDefault();
            return false;
        }
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
