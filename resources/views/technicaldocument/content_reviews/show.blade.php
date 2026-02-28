@extends('layout.layout')

@section('content')
    <div class="container-fluid py-5 bg-light min-vh-100">
        <div class="row mb-5">
            <div class="col-md-9 mx-auto">
                <div class="d-flex align-items-center mb-4">
                    <a href="{{ route('warranty.document.content_reviews.index') }}" class="btn btn-outline-secondary rounded-circle me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h2 class="fw-bold text-dark mb-0">Chi tiết Duyệt Nội Dung</h2>
                        <p class="text-muted mb-0">{{ $product->product_name }} ({{ $product->model }})</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="fw-bold mb-0">Nội dung sản phẩm</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="fw-bold text-primary mb-2">Mô tả sản phẩm</h6>
                                    <div class="p-3 bg-light rounded-3 border">
                                        {!! $product->product_details->description ?? '<i class="text-muted">Chưa có dữ liệu</i>' !!}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="fw-bold text-primary mb-2">Thông số kỹ thuật</h6>
                                    <div class="p-3 bg-light rounded-3 border">
                                        {!! $product->product_details->tech_specs ?? '<i class="text-muted">Chưa có dữ liệu</i>' !!}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="fw-bold text-primary mb-2">Tính năng nổi bật</h6>
                                    <div class="p-3 bg-light rounded-3 border">
                                        {!! $product->product_details->features ?? '<i class="text-muted">Chưa có dữ liệu</i>' !!}
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <h6 class="fw-bold text-primary mb-2">Hướng dẫn sử dụng</h6>
                                    <div class="p-3 bg-light rounded-3 border">
                                        {!! $product->product_details->user_guide ?? '<i class="text-muted">Chưa có dữ liệu</i>' !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                            <div class="card-header bg-white border-bottom py-3">
                                <h5 class="fw-bold mb-0">Hành động duyệt</h5>
                            </div>
                            <div class="card-body p-4">
                                <form id="reviewForm">
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Trạng thái hiện tại</label>
                                        <div>
                                            @php $review = $product->product_content_review; @endphp
                                            @if($review && $review->status === 'approved')
                                                <span class="badge bg-success text-white px-3 py-2 rounded-pill">Đã duyệt</span>
                                            @elseif($review && $review->status === 'rejected')
                                                <span class="badge bg-danger text-white px-3 py-2 rounded-pill">Bị từ chối</span>
                                            @else
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Chờ duyệt</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($review && $review->status === 'rejected' && $review->reject_reason)
                                        <div class="alert alert-danger mb-4">
                                            <strong>Lý do từ chối trước đó:</strong><br>
                                            {{ $review->reject_reason }}
                                        </div>
                                    @endif

                                    <div class="mb-4" id="rejectReasonSection" style="display: none;">
                                        <label class="form-label fw-bold">Lý do từ chối <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="reject_reason" rows="3" placeholder="Nhập lý do từ chối..."></textarea>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="button" onclick="submitReview('approve')" class="btn btn-success py-2 rounded-pill fw-bold" id="btnApprove">
                                            <i class="bi bi-check-circle me-2"></i>Duyệt nội dung
                                        </button>
                                        <button type="button" onclick="toggleRejectReason()" class="btn btn-outline-danger py-2 rounded-pill fw-bold" id="btnToggleReject">
                                            <i class="bi bi-x-circle me-2"></i>Từ chối
                                        </button>
                                        <button type="button" onclick="submitReview('reject')" class="btn btn-danger py-2 rounded-pill fw-bold" id="btnReject" style="display: none;">
                                            Xác nhận từ chối
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleRejectReason() {
            const section = document.getElementById('rejectReasonSection');
            const btnToggle = document.getElementById('btnToggleReject');
            const btnReject = document.getElementById('btnReject');
            const btnApprove = document.getElementById('btnApprove');

            if (section.style.display === 'none') {
                section.style.display = 'block';
                btnReject.style.display = 'block';
                btnApprove.style.display = 'none';
                btnToggle.innerHTML = '<i class="bi bi-arrow-counterclockwise me-2"></i>Hủy bỏ';
            } else {
                section.style.display = 'none';
                btnReject.style.display = 'none';
                btnApprove.style.display = 'block';
                btnToggle.innerHTML = '<i class="bi bi-x-circle me-2"></i>Từ chối';
            }
        }

        function submitReview(action) {
            const form = document.getElementById('reviewForm');
            const formData = new FormData(form);
            formData.append('action', action);

            if (action === 'reject' && !formData.get('reject_reason')) {
                Swal.fire('Lỗi', 'Vui lòng nhập lý do từ chối', 'error');
                return;
            }

            const confirmText = action === 'approve' ? 'Duyệt nội dung này?' : 'Từ chối nội dung này?';

            Swal.fire({
                title: 'Xác nhận',
                text: confirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Đang xử lý...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch("{{ route('warranty.document.content_reviews.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Thành công', data.message, 'success').then(() => {
                                window.location.href = "{{ route('warranty.document.content_reviews.index') }}";
                            });
                        } else {
                            Swal.fire('Lỗi', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Lỗi', 'Có lỗi kết nối hệ thống', 'error');
                    });
                }
            });
        }
    </script>

    <style>
        .p-3.bg-light.rounded-3.border img {
            max-width: 100%;
            height: auto;
        }
    </style>
@endsection
