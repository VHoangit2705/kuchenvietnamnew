@extends('layout.public_layout')

@section('content')
<div class="container-fluid py-0 px-0 h-100 d-flex flex-column">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4">
        <div class="container-fluid">
            <div>
                <a class="navbar-brand fw-bold text-primary d-block" href="#">
                    <i class="bi bi-file-earmark-text me-2"></i>{{ $document->title }} 
                    <span class="badge bg-secondary ms-2 small">v{{ $version->version }}</span>
                </a>
                <div class="small text-muted mt-1">
                    <span class="me-3"><i class="bi bi-tag me-1"></i>Loại: {{ $document->doc_type }}</span>
                    <span class="me-3"><i class="bi bi-info-circle me-1"></i>Trạng thái: {{ $document->status }}</span>
                    @if($document->description)
                        <span class="d-none d-md-inline"><i class="bi bi-card-text me-1"></i>{{ Str::limit($document->description, 50) }}</span>
                    @endif
                </div>
            </div>
            
            <div class="d-flex align-items-center">
                @if($share->permission === 'download')
                    <a href="{{ route('document.share.download', $share->share_token) }}" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-download me-2"></i>Tải xuống
                    </a>
                @endif
                <div class="ms-3 text-muted small">
                    <i class="bi bi-eye me-1"></i> {{ $share->access_count }} lượt xem
                </div>
            </div>
        </div>
    </nav>

    <div class="flex-grow-1 bg-light p-3 d-flex justify-content-center align-items-center" style="min-height: 80vh;">
        @if(in_array($version->file_type, ['pdf']))
            <iframe src="{{ $fileUrl }}" class="w-100 h-100 rounded shadow-sm" style="min-height: 80vh; border: none;"></iframe>
        @elseif(in_array($version->file_type, ['jpg', 'jpeg', 'png', 'gif']))
            <img src="{{ $fileUrl }}" class="img-fluid rounded shadow-sm" alt="{{ $document->title }}" style="max-height: 85vh;">
        @elseif(in_array($version->file_type, ['mp4', 'webm']))
            <video controls class="w-100 rounded shadow-sm" style="max-height: 85vh;">
                <source src="{{ $fileUrl }}" type="video/{{ $version->file_type }}">
                Trình duyệt của bạn không hỗ trợ thẻ video.
            </video>
        @else
            <div class="text-center p-5">
                <i class="bi bi-file-earmark-binary display-1 text-muted"></i>
                <h4 class="mt-3">Không thể xem trước file này.</h4>
                @if($share->permission === 'download')
                    <a href="{{ route('document.share.download', $share->share_token) }}" class="btn btn-primary mt-3">
                        <i class="bi bi-download me-1"></i>Tải về ngay
                    </a>
                @else
                    <p class="text-danger mt-2">Bạn không có quyền tải file này.</p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
