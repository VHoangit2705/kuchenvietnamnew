@extends('layout.layout')

@section('content')
    <div class="container-fluid py-5 bg-light min-vh-100">
        <div class="row mb-5">
            <div class="col-md-8 mx-auto text-center">
                <h1 class="fw-bold text-primary mb-2">Danh Sách Sản Phẩm Lên Kệ</h1>
                <p class="text-muted">Kiểm tra Seri và Tài liệu kỹ thuật trước khi chuyển bước</p>
                <a href="{{ route('warranty.document') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i>Quay lại tra cứu
                </a>
            </div>
        </div>

        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-4 py-3">Sản phẩm</th>
                                <th class="py-3">Model sản phẩm</th>
                                <th class="py-3 text-center">Bước hiện tại</th>
                                <th class="py-3 text-center">Trạng thái gửi</th>
                                <th class="py-3 text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td class="px-4">
                                        <div class="fw-bold text-dark">{{ $product->product_name }}</div>
                                        <div class="small text-muted">ID: {{ $product->id }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill border border-secondary-subtle">
                                            {{ $product->model }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $workflow = $product->product_workflow;
                                            $isSent = $workflow && $workflow->current_step >= 3;
                                        @endphp
                                        @if($workflow)
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="rounded-circle border @if($isSent) border-success text-success @else border-info text-info @endif d-flex align-items-center justify-content-center fw-bold mb-1" style="width: 32px; height: 32px; font-size: 0.85rem; background-color: @if($isSent) #f0fff4 @else #f0f7ff @endif;">
                                                    {{ $workflow->current_step }}
                                                </div>
                                                <div class="small text-muted fw-semibold" style="text-transform: capitalize;">{{ $workflow->status }}</div>
                                            </div>
                                        @else
                                            <span class="text-muted small italic">Chưa bắt đầu</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isSent)
                                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill border border-success-subtle">
                                                <i class="bi bi-check-circle-fill me-1"></i> Đã gửi lên phòng đào tạo
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill border border-warning-subtle">
                                                <i class="bi bi-clock-history me-1"></i> Chờ gửi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isSent)
                                            <button class="btn btn-light text-muted fw-bold px-3 py-2 border" disabled>
                                                <i class="bi bi-check2-all me-2"></i>Đã hoàn tất
                                            </button>
                                        @else
                                            <button 
                                                onclick="sendToTraining({{ $product->id }})" 
                                                class="btn border-0 text-success fw-bold px-3 py-2 btn-send-training shadow-sm" 
                                                style="background-color: #fffc5b;"
                                                data-product-id="{{ $product->id }}"
                                            >
                                                <i class="bi bi-plus-square-fill me-2"></i>Gửi đến phòng đào tạo
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="bi bi-inbox-fill text-muted display-4 opacity-25"></i>
                                        </div>
                                        <h5 class="text-secondary">Không có sản phẩm nào thỏa mãn điều kiện</h5>
                                        <p class="text-muted font-italic">Sản phẩm phải có số Seri và Tài liệu kỹ thuật để xuất hiện ở đây.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($products->hasPages())
                    <div class="card-footer bg-white py-3">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function sendToTraining(productId) {
            const btn = document.querySelector(`.btn-send-training[data-product-id="${productId}"]`);
            const originalContent = btn.innerHTML;
            
            Swal.fire({
                title: 'Xác nhận gửi sản phẩm đến phòng đào tạo?',
                text: "Bạn có chắc chắn muốn gửi sản phẩm này đến phòng đào tạo?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Đồng ý gửi!',
                cancelButtonText: 'Hủy',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';

                    fetch("{{ route('warranty.document.sendToTraining') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ product_id: productId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: data.message || 'Có lỗi xảy ra'
                            });
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Không thể kết nối đến máy chủ'
                        });
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    });
                }
            });
        }
    </script>
@endsection
