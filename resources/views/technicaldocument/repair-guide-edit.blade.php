@extends('layout.layout')

@section('content')
<div class="container-fluid py-5 bg-light">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm">
                <div>
                    <h2 class="fw-bold text-primary mb-1"><i class="bi bi-pencil-square me-2"></i>Sửa Hướng Dẫn Sửa</h2>
                    <p class="text-muted mb-0 small">
                        Mã lỗi: <strong>{{ $guide->commonError->error_code ?? '' }}</strong> — {{ $guide->commonError->error_name ?? '' }}
                        @if($guide->commonError->productModel)
                            · Model: {{ $guide->commonError->productModel->model_code }}{{ $guide->commonError->productModel->version ? ' (' . $guide->commonError->productModel->version . ')' : '' }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('warranty.document.create') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0">Nội dung hướng dẫn</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('warranty.document.repairGuide.update', $guide->id) }}" method="post" id="formEditGuide">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="{{ old('title', $guide->title) }}" required>
                            @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Các bước xử lý <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="steps" rows="6" required>{{ old('steps', $guide->steps) }}</textarea>
                                @error('steps')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Thời gian (phút)</label>
                                <input type="number" class="form-control" name="estimated_time" min="0" value="{{ old('estimated_time', $guide->estimated_time) }}">
                                <label class="form-label fw-semibold mt-3 text-danger">Lưu ý an toàn</label>
                                <textarea class="form-control" name="safety_note" rows="4">{{ old('safety_note', $guide->safety_note) }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Cập nhật</button>
                            <button type="button" class="btn btn-outline-danger" id="btnDeleteGuide"><i class="bi bi-trash me-1"></i>Xóa hướng dẫn</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Tài liệu đính kèm</h5>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush" id="attachedDocsList">
                        @foreach($guide->technicalDocuments as $doc)
                            @php $ver = $doc->documentVersions->sortByDesc('id')->first(); @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                                <div class="small">
                                    @if($ver && $ver->file_path)
                                        <a href="{{ $storageUrl . '/' . ltrim($ver->file_path, '/') }}" target="_blank" class="text-decoration-none">
                                            {{ $doc->title }}
                                        </a>
                                    @else
                                        {{ $doc->title }}
                                    @endif
                                    <span class="badge bg-secondary ms-1">{{ $doc->doc_type }}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-detach-doc" data-document-id="{{ $doc->id }}" title="Gỡ tài liệu">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    @if($guide->technicalDocuments->isEmpty())
                        <p class="text-muted small mb-0">Chưa có tài liệu đính kèm.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<form id="formDeleteGuide" action="{{ route('warranty.document.repairGuide.destroy', $guide->id) }}" method="post" class="d-none">
    @csrf
    @method('DELETE')
</form>
<script>
(function () {
    var csrf = '{{ csrf_token() }}';
    var guideId = {{ $guide->id }};
    var detachUrl = '{{ url('baohanh/tailieukithuat/repair-guides') }}/' + guideId + '/documents/';

    document.getElementById('btnDeleteGuide').addEventListener('click', function () {
        if (!confirm('Bạn có chắc muốn xóa hướng dẫn sửa này?')) return;
        document.getElementById('formDeleteGuide').submit();
    });

    document.querySelectorAll('.btn-detach-doc').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var docId = this.getAttribute('data-document-id');
            if (!confirm('Gỡ tài liệu này khỏi hướng dẫn?')) return;
            var xhr = new XMLHttpRequest();
            xhr.open('DELETE', detachUrl + docId);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    btn.closest('li').remove();
                } else {
                    alert('Không thể gỡ tài liệu.');
                }
            };
            xhr.send();
        });
    });
})();
</script>
@endsection
