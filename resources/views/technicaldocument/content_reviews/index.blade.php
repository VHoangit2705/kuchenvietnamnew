@extends('layout.layout')

@section('content')
    <div class="container-fluid py-5 bg-light min-vh-100">
        <div class="row mb-5">
            <div class="col-md-8 mx-auto text-center">
                <h1 class="fw-bold text-primary mb-2">Duyệt Nội Dung Sản Phẩm</h1>
                <p class="text-muted">Kiểm tra thông tin do phòng Đào tạo cập nhật</p>
                <a href="{{ route('warranty.document') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>

        <div class="container-fluid px-md-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-4 py-3">Sản phẩm / Model</th>
                                <th class="py-3 text-center">Trạng thái</th>
                                <th class="py-3 text-center">Nội dung Đào tạo</th>
                                <th class="py-3">Thông tin duyệt</th>
                                <th class="py-3 text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                @php
                                    $workflow = $product->product_workflow;
                                    $details = $product->product_details;
                                    $review = $product->product_content_review;
                                    $hasContent = $details && ($details->description || $details->tech_specs || $details->features);
                                @endphp
                                <tr>
                                    <td class="px-4">
                                        <div class="fw-bold text-dark">{{ $product->product_name }}</div>
                                        <div class="small">
                                            <span class="badge bg-secondary-subtle text-secondary px-2 rounded-pill border border-secondary-subtle">
                                                {{ $product->model }}
                                            </span>
                                            <span class="text-muted ms-1">ID: {{ $product->id }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($review && $review->status === 'approved')
                                            <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm">
                                                <i class="bi bi-check-circle-fill me-1"></i> Đã duyệt
                                            </span>
                                        @elseif($review && $review->status === 'rejected')
                                            <span class="badge bg-danger text-white px-3 py-2 rounded-pill shadow-sm" title="{{ $review->reject_reason }}">
                                                <i class="bi bi-x-circle-fill me-1"></i> Bị từ chối
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm">
                                                <i class="bi bi-clock-history me-1"></i> Đang chờ
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($hasContent)
                                            <span class="text-success fw-bold small"><i class="bi bi-file-earmark-check-fill me-1"></i> Đã nạp</span>
                                        @else
                                            <span class="text-danger fw-bold small"><i class="bi bi-file-earmark-x-fill me-1"></i> Trống</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($review && $review->status !== 'pending')
                                            <div class="text-dark fw-bold"><i class="bi bi-person-fill me-1"></i> {{ $review->reviewer->name ?? 'N/A' }}</div>
                                            <div class="text-muted"><i class="bi bi-calendar-event me-1"></i> {{ $review->reviewed_at ? $review->reviewed_at->format('d/m/Y H:i') : '' }}</div>
                                            @if($review->status === 'rejected' && $review->reject_reason)
                                                <div class="text-danger mt-1"><strong>Lý do:</strong> {{ Str::limit($review->reject_reason, 50) }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted italic">Chưa có thông tin</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('warranty.document.content_reviews.show', $product->id) }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-eye-fill me-1"></i> Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="bi bi-inbox-fill text-muted display-4 opacity-25"></i>
                                        </div>
                                        <h5 class="text-secondary">Không có sản phẩm nào chờ duyệt</h5>
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
@endsection
