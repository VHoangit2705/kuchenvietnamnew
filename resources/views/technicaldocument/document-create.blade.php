@extends('layout.layout')

@section('content')
<script>
    window.ckeditorUploadUrl = '{{ route('warranty.document.ckeditor.upload') }}';
    window.ckeditorCsrfToken = '{{ csrf_token() }}';
</script>
<div class="container-fluid py-5 bg-light">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm">
                <div>
                    <h2 class="fw-bold text-primary mb-1"><i class="bi bi-upload me-2"></i>Thêm Tài Liệu Kỹ Thuật</h2>
                    <p class="text-muted mb-0 small">Tạo tài liệu mới và tải phiên bản đầu tiên</p>
                </div>
                <a href="{{ route('warranty.document.documents.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
                </a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            <form action="{{ route('warranty.document.documents.store') }}" method="post" enctype="multipart/form-data" id="formDocCreate">
                @csrf
                <x-technicaldocument.product-filter 
                    :categories="$categories" 
                    variant="simple" 
                    idPrefix="doc"
                    :enableFormSubmission="true"
                />
                <div class="row g-3 mb-3">

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" value="{{ old('title') }}" required placeholder="VD: Sơ đồ mạch máy XYZ">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả</label>
                    <textarea class="form-control" name="description" id="docDescription" rows="4">{{ old('description') }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">File tài liệu (phiên bản 1.0) <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="file" id="docCreateFile" accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm" required>
                    <small class="text-muted">Ảnh (JPG, PNG) &lt; 2MB — PDF &lt; 5MB — Video (MP4, WebM) &lt; 10MB.</small>
                </div>

                <div class="border-top pt-3 mt-3 bg-light p-3 rounded">
                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-paperclip me-2"></i>File Đính Kèm (Tùy chọn)</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold small">Tài liệu PDF đính kèm</label>
                            <input type="file" class="form-control" name="attachments_pdf[]" multiple accept=".pdf">
                            <div class="form-text small">Chọn nhiều file PDF</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold small">Video hướng dẫn đính kèm</label>
                            <input type="file" class="form-control" name="attachments_video[]" multiple accept="video/*">
                            <div class="form-text small">Chọn nhiều video (MP4)</div>
                        </div>
                    </div>
                </div>
                <div class="mb-3"></div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmitDoc"><i class="bi bi-upload me-1"></i>Lưu tài liệu</button>
                    <a href="{{ route('warranty.document.documents.index') }}" class="btn btn-outline-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>

<script>
// Configuration for filter module
window.docCreateRoutes = {
    getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
    getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
    getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}"
};
</script>
<script src="{{ asset('js/technicaldocument/filter.js') }}"></script>
<script src="{{ asset('js/technicaldocument/document-create.js') }}"></script>

<!-- CKEditor 5 -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script src="{{ asset('js/technicaldocument/ckeditor-upload-adapter.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        ClassicEditor
            .create(document.querySelector('#docDescription'), {
                extraPlugins: [ MyCustomUploadAdapterPlugin ],
                toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'imageUpload', '|', 'undo', 'redo' ]
            })
            .catch(error => {
                console.error(error);
            });
    });
</script>
<style>
    .ck-editor__editable_inline {
        min-height: 150px;
    }
</style>
@endsection
