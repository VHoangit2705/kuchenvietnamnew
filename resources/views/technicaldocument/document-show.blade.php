@extends('layout.layout')

@section('content')
<div class="container-fluid py-5 bg-light">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm">
                <div>
                    <h2 class="fw-bold text-primary mb-1"><i class="bi bi-file-earmark-text me-2"></i>Chi tiết tài liệu</h2>
                    <p class="text-muted mb-0 small">
                        @if($document->productModel)
                            Model: {{ $document->productModel->model_code }}{{ $document->productModel->version ? ' (' . $document->productModel->version . ')' : '' }}
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('warranty.document.documents.edit', $document->id) }}" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-pencil me-1"></i>Sửa
                    </a>
                    <a href="{{ route('warranty.document.documents.index', ['model_id' => $document->model_id]) }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
                    </a>
                </div>
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
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-secondary" style="width: 140px;">Loại</td>
                            <td><span class="badge bg-secondary">{{ $document->doc_type }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Tiêu đề</td>
                            <td class="fw-semibold">{{ $document->title }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Trạng thái</td>
                            <td>
                                <span class="badge {{ $document->status === 'active' ? 'bg-success' : ($document->status === 'deprecated' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                    {{ $document->status }}
                                </span>
                            </td>
                        </tr>
                        @if($document->description)
                        <tr>
                            <td class="text-secondary align-top">Mô tả</td>
                            <td class="small">{{ $document->description }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($document->repairGuides && $document->repairGuides->isNotEmpty())
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0">Gắn với hướng dẫn sửa</h5>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush">
                        @foreach($document->repairGuides as $guide)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                                <span class="small">{{ $guide->title }}</span>
                                @if($guide->commonError)
                                    <span class="badge bg-danger-subtle text-danger">{{ $guide->commonError->error_code }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0">Các phiên bản file</h5>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush">
                        @foreach($document->documentVersions->sortByDesc('id') as $ver)
                            @php
                                $fileUrl = $ver->file_path ? route('warranty.document.documents.file', [$document->id, 'version_id' => $ver->id]) : null;
                                $downloadUrl = $ver->file_path ? route('warranty.document.documents.file', [$document->id, 'version_id' => $ver->id, 'download' => 1]) : null;
                                $ext = strtolower($ver->file_type ?? '');
                                $canView = $fileUrl && in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                                <span class="small">
                                    <span class="badge bg-light text-dark me-2">v{{ $ver->version }}</span>
                                    {{ $ver->file_type ?? '—' }}
                                </span>
                                @if($fileUrl)
                                    <div class="btn-group btn-group-sm">
                                        @if($canView)
                                            <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary btn-view-file" data-url="{{ $fileUrl }}" data-type="{{ $ext }}" title="Xem trong tab mới">
                                                <i class="bi bi-eye me-1"></i>Xem
                                            </a>
                                        @endif
                                        <a href="{{ $downloadUrl }}" target="_blank" class="btn btn-sm btn-primary" title="Tải xuống">
                                            <i class="bi bi-download me-1"></i>Tải
                                        </a>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if($document->documentVersions->isEmpty())
                        <p class="text-muted small mb-0">Chưa có phiên bản file.</p>
                    @endif
                </div>
            </div>

            @php
                $firstViewable = $document->documentVersions->sortByDesc('id')->first(function ($v) {
                    $ext = strtolower($v->file_type ?? '');
                    return in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
                });
            @endphp
            @if($firstViewable && $firstViewable->file_path)
                @php
                    $embedUrl = route('warranty.document.documents.file', [$document->id, 'version_id' => $firstViewable->id]);
                    $embedExt = strtolower($firstViewable->file_type ?? '');
                @endphp
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="bi bi-eye me-2"></i>Xem trực tiếp (v{{ $firstViewable->version }})</h5>
                        <a href="{{ $embedUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Mở tab mới</a>
                    </div>
                    <div class="card-body p-0 bg-dark">
                        @if($embedExt === 'pdf')
                            <iframe src="{{ $embedUrl }}#toolbar=1" class="w-100" style="height: 70vh; min-height: 400px; border: none;" title="Xem PDF"></iframe>
                        @elseif(in_array($embedExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                            <div class="text-center p-3">
                                <img src="{{ $embedUrl }}" alt="Xem ảnh" class="img-fluid" style="max-height: 70vh;">
                            </div>
                        @elseif(in_array($embedExt, ['mp4', 'webm']))
                            <div class="p-3">
                                <video src="{{ $embedUrl }}" controls class="w-100" style="max-height: 70vh;">Trình duyệt không hỗ trợ video.</video>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
