@extends('layout.layout')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Chi tiết yêu cầu lắp đặt</h4>
            <div>
                <a href="{{ route('requestagency.edit', $request->id) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i>Chỉnh sửa
                </a>
                <a href="{{ route('requestagency.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
            </div>
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
                    <label class="form-label text-muted">Ghi chú thêm</label>
                    <div class="form-control-plaintext">
                        {{ $request->notes }}
                    </div>
                </div>
            </div>
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label text-muted">Tên đại lý</label>
                    <div class="form-control-plaintext">
                        {{ $request->agency_name ?? '-' }}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Số điện thoại đại lý</label>
                    <div class="form-control-plaintext">
                        {{ $request->agency_phone ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label text-muted">Người tiếp nhận</label>
                    <div class="form-control-plaintext">
                        {{ $request->received_by ?? '-' }}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Người được gán xử lý</label>
                    <div class="form-control-plaintext">
                        {{ $request->assigned_to ?? '-' }}
                    </div>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label text-muted">Ngày tạo</label>
                    <div class="form-control-plaintext">
                        {{ $request->created_at->format('d/m/Y H:i:s') }}
                    </div>
                </div>
                @if($request->received_at)
                <div class="col-md-6">
                    <label class="form-label text-muted">Ngày tiếp nhận</label>
                    <div class="form-control-plaintext">
                        {{ $request->received_at->format('d/m/Y H:i:s') }}
                    </div>
                </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label text-muted">Cập nhật lần cuối</label>
                    <div class="form-control-plaintext">
                        {{ $request->updated_at->format('d/m/Y H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
    .form-control-plaintext {
        padding: 0.375rem 0;
        margin-bottom: 0;
        line-height: 1.5;
        color: #212529;
        background-color: transparent;
        border: solid transparent;
        border-width: 1px 0;
    }
</style>
@endsection

