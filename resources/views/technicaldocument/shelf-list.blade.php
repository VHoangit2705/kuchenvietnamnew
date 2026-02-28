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

        <div class="container-fluid px-md-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-4 py-3">Sản phẩm</th>
                                <th class="py-3">Model sản phẩm</th>
                                <th class="py-3 text-center">Tiến độ quy trình</th>
                                <th class="py-3 text-center">Trạng thái hiện tại</th>
                                <th class="py-3 text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                @php
                                    $workflow = $product->product_workflow;
                                    $hasSerials = $product->warranty_cards->isNotEmpty();
                                    $hasDocs = $product->technical_documents->isNotEmpty();
                                    $isSent = $workflow && $workflow->current_step >= 3;
                                @endphp
                                <tr class="@if(!$hasSerials || (!$isSent && !$hasDocs)) table-warning-light @endif">
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
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="d-flex flex-column align-items-center mx-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold mb-1 shadow-sm" 
                                                    style="width: 28px; height: 28px; font-size: 0.75rem; @if($hasSerials) background-color: #d1e7dd; color: #0f5132; @else background-color: #f8d7da; color: #842029; border: 1px dashed #f5c2c7; @endif">
                                                    @if($hasSerials)<i class="bi bi-check"></i>@else 1 @endif
                                                </div>
                                                <span class="x-small text-muted">Seri</span>
                                            </div>

                                            <i class="bi bi-arrow-right mb-3 @if($hasSerials) text-success @else text-muted opacity-50 @endif" style="font-size: 1rem;"></i>

                                            <div class="d-flex flex-column align-items-center mx-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold mb-1 shadow-sm" 
                                                    style="width: 28px; height: 28px; font-size: 0.75rem; @if($hasDocs) background-color: #d1e7dd; color: #0f5132; @else background-color: #fff3cd; color: #664d03; border: 1px dashed #ffecb5; @endif">
                                                    @if($hasDocs)<i class="bi bi-check"></i>@else 2 @endif
                                                </div>
                                                <span class="x-small text-muted">TLKT</span>
                                            </div>

                                            <i class="bi bi-arrow-right mb-3 @if($hasDocs) text-success @else text-muted opacity-50 @endif" style="font-size: 1rem;"></i>

                                            <div class="d-flex flex-column align-items-center mx-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold mb-1 shadow-sm" 
                                                    style="width: 28px; height: 28px; font-size: 0.75rem; @if($isSent) background-color: #cfe2ff; color: #084298; @else background-color: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; @endif">
                                                    3
                                                </div>
                                                <span class="x-small text-muted">Đào tạo</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($isSent)
                                            <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm">
                                                <i class="bi bi-check-circle-fill me-1"></i> Đã gửi Đào tạo
                                            </span>
                                        @elseif(!$hasSerials)
                                            <span class="badge bg-danger text-white px-3 py-2 rounded-pill animate__animated animate__flash animate__infinite shadow-sm">
                                                <i class="bi bi-exclamation-octagon-fill me-1"></i> Thiếu Số Seri
                                            </span>
                                        @elseif(!$hasDocs)
                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm">
                                                <i class="bi bi-file-earmark-medical-fill me-1"></i>Thiếu Tài liệu
                                            </span>
                                        @else
                                            <span class="badge bg-info text-white px-3 py-2 rounded-pill shadow-sm">
                                                <i class="bi bi-hand-thumbs-up-fill me-1"></i> Sẵn sàng lên kệ
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isSent)
                                            <button class="btn btn-light text-muted fw-bold px-3 py-2 border rounded-pill" disabled style="font-size: 0.9rem;">
                                                <i class="bi bi-check2-all me-2"></i>Đã xong
                                            </button>
                                        @elseif($hasSerials && $hasDocs)
                                            <button 
                                                onclick="sendToTraining({{ $product->id }})" 
                                                class="btn border-0 text-success fw-bold px-3 py-2 btn-send-training shadow-sm rounded-pill" 
                                                style="background-color: #fffc5b; font-size: 0.9rem;"
                                                data-product-id="{{ $product->id }}"
                                            >
                                                <i class="bi bi-send-fill me-2"></i>Gửi Đào tạo
                                            </button>
                                        @else
                                            <button class="btn btn-secondary text-white-50 fw-bold px-3 py-2 border-0 rounded-pill opacity-50" disabled style="font-size: 0.9rem;">
                                                <i class="bi bi-lock-fill me-2"></i>Chưa đủ ĐK
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

        // Tự động hiển thị modal cảnh báo nếu có sản phẩm chưa hoàn thiện
        @if($countMissingSerials > 0 || $countMissingDocs > 0)
            document.addEventListener('DOMContentLoaded', function() {
                var myModal = new bootstrap.Modal(document.getElementById('autoWarningModal'));
                myModal.show();
            });
        @endif
    </script>

    <!-- Modal Cảnh báo tự động -->
    <div class="modal fade" id="autoWarningModal" tabindex="-1" aria-labelledby="autoWarningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white border-0 rounded-top-4 py-3">
                    <h5 class="modal-title fw-bold" id="autoWarningModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>CẢNH BÁO QUY TRÌNH LÊN KỆ SẢN PHẨM
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="bi bi-clipboard-x text-danger display-1 opacity-25"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-3">Phát hiện sản phẩm chưa hoàn thiện!</h5>
                    <p class="text-muted mb-4">
                        Hiện đang có các sản phẩm chưa đủ điều kiện để lên kệ. Vui lòng kiểm tra và hoàn thiện các bước sau:
                    </p>
                    
                    <div class="d-grid gap-2 mb-2">
                        @if($countMissingSerials > 0)
                            <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center mb-2">
                                <i class="bi bi-hash fs-4 me-3"></i>
                                <div class="text-start">
                                    <div class="fw-bold">Thiếu Số Seri</div>
                                    <div class="small">{{ $countMissingSerials }} sản phẩm đang chờ nạp seri.</div>
                                </div>
                            </div>
                        @endif

                        @if($countMissingDocs > 0)
                            <div class="alert alert-warning border-0 rounded-3 d-flex align-items-center">
                                <i class="bi bi-file-earmark-medical fs-4 me-3"></i>
                                <div class="text-start">
                                    <div class="fw-bold">Thiếu Tài liệu kỹ thuật</div>
                                    <div class="small">{{ $countMissingDocs }} sản phẩm chưa có HDSD/Thông số.</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 justify-content-center">
                    <button type="button" class="btn btn-dark rounded-pill px-5 fw-bold" data-bs-dismiss="modal">Tôi đã hiểu, kiểm tra ngay</button>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPT TỰ ĐỘNG GỬI THÔNG BÁO SAU 5 GIÂY --}}
    @if(session('notify_type') && session('notify_id'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notifyType = "{{ session('notify_type') }}";
            const notifyId = "{{ session('notify_id') }}";
            
            console.log('Hệ thống sẽ gửi thông báo sau 5 giây...');
            
            setTimeout(function() {
                fetch("{{ route('notifications.trigger') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        type: notifyType,
                        id: notifyId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ ' + data.message);
                    } else {
                        console.error('❌ Lỗi: ' + data.message);
                    }
                })
                .catch(error => console.error('❌ Lỗi kết nối:', error));
            }, 5000); // 5000ms = 5 giây
        });
    </script>
    @endif
@endsection
