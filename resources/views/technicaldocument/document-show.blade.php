@extends('layout.layout')

@section('content')
    <div class="container-fluid py-5 bg-light">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm">
                    <div>
                        <h2 class="fw-bold text-primary mb-1"><i class="bi bi-file-earmark-text me-2"></i>Chi tiết tài liệu
                        </h2>
                        <p class="text-muted mb-0 small">
                            @if($document->product)
                                Sản phẩm: {{ $document->product->product_name }} - Xuất xứ: {{ $document->xuat_xu }}
                            @endif
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('warranty.document.documents.edit', $document->id) }}"
                            class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-pencil me-1"></i>Sửa
                        </a>
                        <a href="{{ route('warranty.document.documents.index', ['product_id' => $document->product_id, 'xuat_xu' => $document->xuat_xu]) }}"
                            class="btn btn-outline-secondary rounded-pill px-4">
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
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                                        <span class="small">{{ $guide->title }}</span>
                                        @if($guide->commonError)
                                            <span
                                                class="badge bg-danger-subtle text-danger">{{ $guide->commonError->error_code }}</span>
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
                                    $filePath = $ver->img_upload ?? $ver->video_upload ?? $ver->pdf_upload;
                                    $hasFile = !empty($filePath);
                                    $fileUrl = $hasFile ? route('warranty.document.documents.file', [$document->id, 'version_id' => $ver->id]) : null;
                                    $downloadUrl = $hasFile ? route('warranty.document.documents.file', [$document->id, 'version_id' => $ver->id, 'download' => 1]) : null;
                                    // Get extension from path
                                    $ext = $hasFile ? strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) : '';
                                    $canView = $fileUrl && in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm']);
                                @endphp
                                <li
                                    class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                                    <span class="small">
                                        <span class="badge bg-light text-dark me-2">v{{ $ver->version }}</span>
                                        {{ $ext ? strtoupper($ext) : '—' }}
                                    </span>
                                    @if($fileUrl)
                                        <div class="btn-group btn-group-sm">
                                            @if($canView)
                                                <a href="{{ $fileUrl }}" target="_blank" rel="noopener"
                                                    class="btn btn-sm btn-outline-primary btn-view-file" data-url="{{ $fileUrl }}"
                                                    data-type="{{ $ext }}" title="Xem trong tab mới">
                                                    <i class="bi bi-eye me-1"></i>Xem
                                                </a>
                                            @endif
                                            <a href="{{ $downloadUrl }}" target="_blank" class="btn btn-sm btn-primary"
                                                title="Tải xuống">
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
            </div>

            <!-- File Đính Kèm Section -->
            @if($document->attachments->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-paperclip me-2"></i>File Đính Kèm</h5>
                    </div>
                    <div class="card-body p-4">
                        {{-- Images Grid --}}
                        @php
                            $images = $document->attachments->whereIn('file_type', ['image', 'jpg', 'jpeg', 'png', 'webp']);
                            $others = $document->attachments->whereNotIn('file_type', ['image', 'jpg', 'jpeg', 'png', 'webp']);
                        @endphp

                        @if($images->isNotEmpty())
                            <h6 class="fw-bold small text-muted mb-3">HÌNH ẢNH</h6>
                            <div class="row g-2 mb-4">
                                @foreach($images as $img)
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <a href="{{ $storageUrl . '/' . ltrim($img->file_path, '/') }}" target="_blank"
                                            class="d-block position-relative rounded overflow-hidden shadow-sm"
                                            style="padding-top: 100%;">
                                            <img src="{{ $storageUrl . '/' . ltrim($img->file_path, '/') }}"
                                                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                                alt="{{ $img->file_name }}">
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Others List (PDF, Video) --}}
                        @if($others->isNotEmpty())
                            <h6 class="fw-bold small text-muted mb-3">TÀI LIỆU & VIDEO</h6>
                            <ul class="list-group list-group-flush border rounded-3 overflow-hidden">
                                @foreach($others as $att)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div class="d-flex align-items-center overflow-hidden">
                                            @if(in_array($att->file_type, ['pdf']))
                                                <div class="bg-danger text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px;">
                                                    <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                </div>
                                            @elseif(in_array($att->file_type, ['video', 'mp4', 'webm']))
                                                <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px;">
                                                    <i class="bi bi-play-circle fs-5"></i>
                                                </div>
                                            @else
                                                <div class="bg-secondary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px;">
                                                    <i class="bi bi-file-earmark-text fs-5"></i>
                                                </div>
                                            @endif

                                            <div class="overflow-hidden">
                                                <a href="{{ $storageUrl . '/' . ltrim($att->file_path, '/') }}" target="_blank"
                                                    class="fw-semibold text-decoration-none text-dark d-block text-truncate">
                                                    {{ $att->file_name }}
                                                </a>
                                                <span class="small text-muted text-uppercase">{{ $att->file_type }}</span>
                                            </div>
                                        </div>
                                        <a href="{{ $storageUrl . '/' . ltrim($att->file_path, '/') }}" target="_blank"
                                            class="btn btn-sm btn-light rounded-pill ms-2">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            @php
                $firstViewable = $document->documentVersions->sortByDesc('id')->first(function ($v) {
                    return $v->img_upload || $v->video_upload || $v->pdf_upload;
                });
            @endphp
            @if($firstViewable)
                @php
                    $embedUrl = route('warranty.document.documents.file', [$document->id, 'version_id' => $firstViewable->id]);
                    $filePath = $firstViewable->img_upload ?? $firstViewable->video_upload ?? $firstViewable->pdf_upload;
                    $embedExt = $filePath ? strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) : '';

                    // Fallback determine type based on which column is filled if ext is missing
                    if (!$embedExt) {
                        if ($firstViewable->pdf_upload)
                            $embedExt = 'pdf';
                        elseif ($firstViewable->img_upload)
                            $embedExt = 'jpg'; // Generic image
                        elseif ($firstViewable->video_upload)
                            $embedExt = 'mp4'; // Generic video
                    }
                @endphp
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">
                            @if($firstViewable->pdf_upload) <i class="bi bi-file-earmark-pdf me-2"></i> @endif
                            @if($firstViewable->img_upload) <i class="bi bi-image me-2"></i> @endif
                            @if($firstViewable->video_upload) <i class="bi bi-camera-video me-2"></i> @endif
                            Xem trực tiếp (v{{ $firstViewable->version }})
                        </h5>
                        <a href="{{ $embedUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Mở tab mới
                        </a>
                    </div>
                    <div class="card-body p-0 bg-dark d-flex justify-content-center align-items-center"
                        style="min-height: 400px;">
                        @if($firstViewable->pdf_upload || $embedExt === 'pdf')
                            <iframe src="{{ $embedUrl }}#toolbar=1" class="w-100"
                                style="height: 70vh; min-height: 500px; border: none;" title="Xem PDF"></iframe>
                        @elseif($firstViewable->img_upload || in_array($embedExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                            <div class="text-center p-3">
                                <img src="{{ $embedUrl }}" alt="Xem ảnh" class="img-fluid" style="max-height: 70vh;">
                            </div>
                        @elseif($firstViewable->video_upload || in_array($embedExt, ['mp4', 'webm', 'mov']))
                            <div class="w-100 h-100">
                                <video src="{{ $embedUrl }}" controls class="w-100"
                                    style="max-height: 70vh; width: 100%; display: block;">
                                    Trình duyệt không hỗ trợ thẻ video.
                                </video>
                            </div>
                        @else
                            <div class="text-white text-center p-5">
                                <i class="bi bi-file-earmark-x fs-1 mb-3 d-block text-secondary"></i>
                                <p>Định dạng file không hỗ trợ xem trước.</p>
                                <a href="{{ $embedUrl }}?download=1" class="btn btn-light btn-sm mt-2">
                                    <i class="bi bi-download me-1"></i>Tải xuống
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
    </div>
@endsection