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
                            <span class="d-none d-md-inline"><i
                                    class="bi bi-card-text me-1"></i>{{ Str::limit($document->description, 50) }}</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    @if($share->permission === 'download')
                        <a href="{{ route('document.share.download', $share->share_token) }}"
                            class="btn btn-primary rounded-pill px-4">
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
            @if($extension === 'pdf')
                <iframe src="{{ $fileUrl }}" class="w-100 h-100 rounded shadow-sm"
                    style="min-height: 80vh; border: none;"></iframe>
            @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                <img src="{{ $fileUrl }}" class="img-fluid rounded shadow-sm" alt="{{ $document->title }}"
                    style="max-height: 85vh;">
            @elseif(in_array($extension, ['mp4', 'webm', 'mov', 'avi']))
                <video controls class="w-100 rounded shadow-sm" style="max-height: 85vh;">
                    <source src="{{ $fileUrl }}" type="video/{{ $extension }}">
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

    {{-- File Đính Kèm Section --}}
    @if($document->attachments->isNotEmpty())
        <div class="container-fluid px-4 py-3 bg-white border-top">
            <h5 class="fw-bold mb-3 small text-muted text-uppercase">File đính kèm khác</h5>

            {{-- Images Grid --}}
            @php
                $images = $document->attachments->whereIn('file_type', ['image', 'jpg', 'jpeg', 'png', 'webp']);
                $others = $document->attachments->whereNotIn('file_type', ['image', 'jpg', 'jpeg', 'png', 'webp']);
                $storageUrl = rtrim(asset('storage'), '/');
            @endphp

            @if($images->isNotEmpty())
                <div class="row g-2 mb-3">
                    @foreach($images as $img)
                        <div class="col-4 col-md-2">
                            <a href="{{ $storageUrl . '/' . ltrim($img->file_path, '/') }}" target="_blank"
                                class="d-block position-relative rounded overflow-hidden shadow-sm" style="padding-top: 100%;">
                                <img src="{{ $storageUrl . '/' . ltrim($img->file_path, '/') }}"
                                    class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" alt="{{ $img->file_name }}">
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Others List (PDF, Video) --}}
            @if($others->isNotEmpty())
                <ul class="list-group list-group-flush border rounded overflow-hidden">
                    @foreach($others as $att)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <div class="d-flex align-items-center overflow-hidden">
                                @if(in_array($att->file_type, ['pdf']))
                                    <i class="bi bi-file-earmark-pdf fs-5 text-danger me-2"></i>
                                @elseif(in_array($att->file_type, ['video', 'mp4', 'webm', 'mov']))
                                    <i class="bi bi-play-circle fs-5 text-primary me-2"></i>
                                @else
                                    <i class="bi bi-file-earmark-text fs-5 text-secondary me-2"></i>
                                @endif
                                <a href="{{ $storageUrl . '/' . ltrim($att->file_path, '/') }}" target="_blank"
                                    class="text-decoration-none text-dark text-truncate small">
                                    {{ $att->file_name }}
                                </a>
                            </div>
                            @if($share->permission === 'download')
                                <a href="{{ $storageUrl . '/' . ltrim($att->file_path, '/') }}" download
                                    class="btn btn-sm btn-light rounded-circle">
                                    <i class="bi bi-download"></i>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
    </div>
@endsection