@extends('layout.layout')

@section('content')
<div class="container-fluid mt-3">
    <div class="row g-4">
        <div class="col-12 col-md-12">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white position-relative">
                    <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" onclick="goBackOrReload()" title="Quay lại"
                        style="height: 15px; filter: brightness(0) invert(1); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                    <h5 class="mb-0 ms-5">Kiểm tra thông tin bảo hành</h5>
                </div>
                <div class="card-body">
                    <label for="serial_number " class="form-label">Nhập mã đơn hàng trên phiếu bảo hành hoặc seri trên thân sản phẩm (dành cho tem bảo hành mới) (<span style="color: red;">*</span>)</label>
                    <input id="serial_number" name="serial_number" type="text" class="form-control mb-3" placeholder="Nhập mã tem bảo hành">
                    <div class="error text-danger small mt-1"></div>
                    <p>- Đối với các đơn hàng được mua trước ngày 25/11/2024 đang áp dụng mẫu tem bảo hành cũ. Vui lòng nhấn tạo phiếu bảo hành và bỏ qua bước "Tra cứu" này và tạo trực tiếp phiếu bảo hành ngay nút dưới.</p>
                    <p>- CHÚ Ý: Sản phẩm <b>Robot hút bụi lau nhà KU PPR3006</b> kỹ thuật viên khi tra cứu bảo hành vui lòng nhập theo cú pháp: 2025050500 + (3 chữ số cuối của mã serial sản phẩm)</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" id="btn-check">Tra cứu</button>
                        <button onclick="window.location.href='{{ route('warranty.formcard') }} '" class="btn btn-warning text-dark">&#43; Tạo phiếu bảo hành</button>
                        <button class="btn btn-success" id="btn_check_qick">Quét mã QR</button>
                    </div>
                    <video id="video" autoplay playsinline></video>
                </div>
            </div>
            
        </div>
    </div>
    <!-- Thông tin khách hàng -->
    <div id="customer-info" class="row g-4 mt-2" style="display: none; padding-bottom: 40px;"></div>
</div>

<link rel="stylesheet" href="{{ asset('css/warranty/checkwarranty.css') }}">
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/umd/index.min.js"></script>
<script>
    // Pass data to JavaScript
    window.warrantyFindRoute = @json(route('warranty.find'));
    window.warrantyFindQrRoute = @json(route('warranty.findqr'));
    window.warrantyFormCardRoute = @json(route('warranty.formcard'));
    window.csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('js/validate_input/checkwarranty.js') }}"></script>
<script src="{{ asset('js/warranty/checkwarranty.js') }}"></script>

@endsection