@extends('layout.layout')


@section('content')
<div class="container-fluid py-5 bg-light">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm">
                <div>
                    <h2 class="fw-bold text-primary mb-1"><i class="bi bi-pencil-square me-2"></i>Sửa Tài Liệu</h2>
                    <p class="text-muted mb-0 small">
                        @if($document->productModel)
                            Model: {{ $document->productModel->model_code }}{{ $document->productModel->version ? ' (' . $document->productModel->version . ')' : '' }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('warranty.document.documents.index', ['model_id' => $document->model_id]) }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4 rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-4 rounded-3 shadow-sm">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('warranty.document.documents.update', $document->id) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Cột trái: Thông tin chính -->
            <div class="col-lg-5 mb-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-2"></i>Thông tin tài liệu</h5>
                    </div>
                    <div class="card-body p-4">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="{{ old('title', $document->title) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mô tả</label>
                            <textarea class="form-control" name="description" rows="4">{{ old('description', $document->description) }}</textarea>
                        </div>

                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2 rounded-pill fw-bold"><i class="bi bi-check-lg me-1"></i>Lưu Thay Đổi</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Quản lý File -->
            <div class="col-lg-7">
                <!-- Phần 1: File Chính (Versioning) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-success"><i class="bi bi-file-earmark-check me-2"></i>File Chính (Có version)</h5>
                        <span class="badge bg-success rounded-pill">Core File</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="bg-light p-3 rounded mb-3 border">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label small text-muted">Phiên bản (Tự động)</label>
                                    <input type="text" class="form-control bg-white" value="Tự động tăng" disabled>
                                    <input type="hidden" name="version" value="">
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label small text-muted">Cập nhật file mới (nếu cần)</label>
                                    <input type="file" class="form-control" name="file">
                                    <div class="form-text small">Thay thế file chính hiện tại và tăng version.</div>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold small text-uppercase text-muted mb-2">Lịch sử phiên bản</h6>
                        <ul class="list-group list-group-flush rounded border">
                            @foreach($document->documentVersions->sortByDesc('id') as $ver)
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    @php
                                        $filePath = $ver->img_upload ?? $ver->video_upload ?? $ver->pdf_upload;
                                        $ext = $filePath ? pathinfo($filePath, PATHINFO_EXTENSION) : '';
                                    @endphp
                                    <div>
                                        <span class="badge bg-secondary me-2">v{{ $ver->version }}</span>
                                        <span class="small text-muted">{{ $ext ? strtoupper($ext) : '—' }} • {{ $ver->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    @if($filePath)
                                        <a href="{{ $storageUrl . '/' . ltrim($filePath, '/') }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-download me-1"></i>Tải
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                            @if($document->documentVersions->isEmpty())
                                <li class="list-group-item text-center text-muted small py-3">Chưa có phiên bản nào.</li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Phần 2: File Đính Kèm (Attachments) -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-info"><i class="bi bi-paperclip me-2"></i>File Đính Kèm</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-3">
                            <div class="col-12 mb-2">
                                <label class="form-label fw-semibold small text-primary"><i class="bi bi-image me-1"></i>Thêm Hình Ảnh</label>
                                <input type="file" class="form-control" name="attachments_image[]" multiple accept="image/*">
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label fw-semibold small text-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Thêm Tài Liệu PDF</label>
                                <input type="file" class="form-control" name="attachments_pdf[]" multiple accept=".pdf">
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label fw-semibold small text-success"><i class="bi bi-camera-video me-1"></i>Thêm Video Hướng Dẫn</label>
                                <input type="file" class="form-control" name="attachments_video[]" multiple accept="video/*">
                            </div>
                        </div>

                        <div class="row g-3">
                            @foreach($document->attachments as $att)
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100 border shadow-sm position-relative group-hover">
                                        <div class="position-absolute top-0 end-0 p-1" style="z-index: 10;">
                                            <a href="{{ route('warranty.document.documents.destroy_attachment', $att->id) }}" 
                                               class="btn btn-danger btn-sm rounded-circle shadow-sm"
                                               onclick="return confirm('Xóa file này?');"
                                               style="width: 24px; height: 24px; padding: 0; line-height: 22px;">
                                                &times;
                                            </a>
                                        </div>
                                        
                                        <div class="card-body p-2 text-center d-flex flex-column justify-content-center align-items-center" style="height: 120px; background: #f8f9fa;">
                                            @if(in_array($att->file_type, ['image', 'jpg', 'png', 'jpeg']))
                                                <img src="{{ $storageUrl . '/' . ltrim($att->file_path, '/') }}" class="img-fluid" style="max-height: 80px; object-fit: contain;">
                                            @elseif($att->file_type == 'pdf')
                                                <i class="bi bi-file-earmark-pdf text-danger fs-1"></i>
                                            @elseif(in_array($att->file_type, ['video', 'mp4']))
                                                <i class="bi bi-file-earmark-play text-primary fs-1"></i>
                                            @else
                                                <i class="bi bi-file-earmark-text text-secondary fs-1"></i>
                                            @endif
                                            <a href="{{ $storageUrl . '/' . ltrim($att->file_path, '/') }}" target="_blank" class="small text-decoration-none text-dark mt-2 text-truncate w-100 d-block" title="{{ $att->file_name }}">
                                                {{ $att->file_name ?? 'File' }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($document->attachments->isEmpty())
                            <p class="text-center text-muted small py-4 mb-0">Chưa có file đính kèm nào.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
