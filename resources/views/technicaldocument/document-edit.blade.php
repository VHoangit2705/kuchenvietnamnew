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

    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0">Thông tin tài liệu</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <form action="{{ route('warranty.document.documents.update', $document->id) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Loại tài liệu</label>
                            <select class="form-select" name="doc_type">
                                <option value="manual" {{ old('doc_type', $document->doc_type) === 'manual' ? 'selected' : '' }}>Manual / Hướng dẫn</option>
                                <option value="wiring" {{ old('doc_type', $document->doc_type) === 'wiring' ? 'selected' : '' }}>Sơ đồ mạch</option>
                                <option value="repair" {{ old('doc_type', $document->doc_type) === 'repair' ? 'selected' : '' }}>Sửa chữa</option>
                                <option value="image" {{ old('doc_type', $document->doc_type) === 'image' ? 'selected' : '' }}>Hình ảnh</option>
                                <option value="video" {{ old('doc_type', $document->doc_type) === 'video' ? 'selected' : '' }}>Video</option>
                                {{-- <option value="bulletin" {{ old('doc_type', $document->doc_type) === 'bulletin' ? 'selected' : '' }}>Bulletin</option> --}}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="{{ old('title', $document->title) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mô tả</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description', $document->description) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Trạng thái</label>
                            <select class="form-select" name="status">
                                <option value="active" {{ old('status', $document->status) === 'active' ? 'selected' : '' }}>active</option>
                                <option value="inactive" {{ old('status', $document->status) === 'inactive' ? 'selected' : '' }}>inactive</option>
                                <option value="deprecated" {{ old('status', $document->status) === 'deprecated' ? 'selected' : '' }}>deprecated</option>
                            </select>
                        </div>

                        <div class="border-top pt-3 mt-3 bg-light p-3 rounded">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-cloud-upload me-2"></i>Cập nhật phiên bản mới</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label small text-muted">Phiên bản mới (Ví dụ: 1.1)</label>
                                    <input type="text" class="form-control" name="version" placeholder="Nhập số..." value="{{ old('version') }}">
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label small text-muted">File tài liệu mới</label>
                                    <input type="file" class="form-control" name="file">
                                    <div class="form-text small">Chấp nhận: pdf, jpg, png, mp4...</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Cập nhật</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0">Các phiên bản file</h5>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush">
                        @foreach($document->documentVersions->sortByDesc('id') as $ver)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                                <span class="small">v{{ $ver->version }} — {{ $ver->file_type ?? '' }}</span>
                                @if($ver->file_path)
                                    <a href="{{ $storageUrl . '/' . ltrim($ver->file_path, '/') }}" target="_blank" class="btn btn-sm btn-outline-primary">Tải</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if($document->documentVersions->isEmpty())
                        <p class="text-muted small mb-0">Chưa có phiên bản file.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
