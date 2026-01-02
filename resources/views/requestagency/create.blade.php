@extends('layout.layout')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light">
            <h4 class="mb-0">Yêu cầu lắp đặt</h4>
            <small class="text-muted">Nhập thông tin đơn hàng để yêu cầu kỹ thuật viên</small>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('requestagency.store') }}" id="requestForm">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="order_code" class="form-label">
                            Mã đơn hàng <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                            class="form-control @error('order_code') is-invalid @enderror" 
                            id="order_code" 
                            name="order_code" 
                            value="{{ old('order_code') }}" 
                            placeholder="Ví dụ: DH123456"
                            required>
                        @error('order_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="product_name" class="form-label">
                            Tên sản phẩm <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                            class="form-control @error('product_name') is-invalid @enderror" 
                            id="product_name" 
                            name="product_name" 
                            value="{{ old('product_name') }}" 
                            placeholder="Ví dụ: Bếp từ Kuchen KI-123"
                            required>
                        @error('product_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="customer_name" class="form-label">
                            Họ tên khách hàng <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                            class="form-control @error('customer_name') is-invalid @enderror" 
                            id="customer_name" 
                            name="customer_name" 
                            value="{{ old('customer_name') }}" 
                            placeholder="Nhập họ và tên khách hàng"
                            required>
                        @error('customer_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="customer_phone" class="form-label">
                            Số điện thoại khách hàng <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                            class="form-control @error('customer_phone') is-invalid @enderror" 
                            id="customer_phone" 
                            name="customer_phone" 
                            value="{{ old('customer_phone') }}" 
                            placeholder="0XXXXXXXXX"
                            required>
                        @error('customer_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="installation_address" class="form-label">
                            Địa chỉ lắp đặt <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            class="form-control @error('installation_address') is-invalid @enderror" 
                            id="installation_address" 
                            name="installation_address" 
                            rows="3" 
                            placeholder="Số nhà, đường, phường/xã, quận/huyện..."
                            required>{{ old('installation_address') }}</textarea>
                        @error('installation_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="notes" class="form-label">Ghi chú thêm</label>
                        <textarea 
                            class="form-control @error('notes') is-invalid @enderror" 
                            id="notes" 
                            name="notes" 
                            rows="3" 
                            placeholder="Ví dụ: Lắp đặt vào buổi chiều, gọi trước khi đến...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="agency_name" class="form-label">Tên đại lý</label>
                        <input type="text" 
                            class="form-control @error('agency_name') is-invalid @enderror" 
                            id="agency_name" 
                            name="agency_name" 
                            value="{{ old('agency_name') }}">
                        @error('agency_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="agency_phone" class="form-label">Số điện thoại đại lý</label>
                        <input type="text" 
                            class="form-control @error('agency_phone') is-invalid @enderror" 
                            id="agency_phone" 
                            name="agency_phone" 
                            value="{{ old('agency_phone') }}">
                        @error('agency_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Gửi yêu cầu
                        </button>
                        <a href="{{ route('requestagency.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Hủy
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validation số điện thoại
document.getElementById('customer_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

document.getElementById('agency_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

@if($errors->any())
    toastr.error('Vui lòng kiểm tra lại thông tin đã nhập!');
@endif
</script>
@endsection

