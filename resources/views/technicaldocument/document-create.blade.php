@extends('layout.layout')

@section('content')
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
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                        <select class="form-select" id="docCategory" required>
                            <option value="">Chọn danh mục</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name_vi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sản phẩm <span class="text-danger">*</span></label>
                        <select class="form-select" id="docProduct" disabled>
                            <option value="">Chọn sản phẩm</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Xuất xứ <span class="text-danger">*</span></label>
                        <select class="form-select" id="docOrigin" disabled>
                            <option value="">Xuất xứ</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Model <span class="text-danger">*</span></label>
                        <select class="form-select" name="model_id" id="docModel" required disabled>
                            <option value="">Chọn model</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Loại tài liệu <span class="text-danger">*</span></label>
                        <select class="form-select" name="doc_type" required>
                            <option value="manual">Manual / Hướng dẫn</option>
                            <option value="wiring">Sơ đồ mạch</option>
                            <option value="repair">Sửa chữa</option>
                            <option value="image">Hình ảnh</option>
                            <option value="video">Video</option>
                            <option value="bulletin">Bulletin</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" value="{{ old('title') }}" required placeholder="VD: Sơ đồ mạch máy XYZ">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả</label>
                    <textarea class="form-control" name="description" rows="2">{{ old('description') }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">File tài liệu (phiên bản 1.0) <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="file" accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm" required>
                    <small class="text-muted">PDF, JPG, PNG, MP4. Tối đa 20MB.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-upload me-1"></i>Lưu tài liệu</button>
                    <a href="{{ route('warranty.document.documents.index') }}" class="btn btn-outline-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var routes = {
        getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
        getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
        getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}"
    };
    function loadProducts(catId) {
        if (!catId) { jQuery('#docProduct').html('<option value="">Chọn sản phẩm</option>').prop('disabled', true); jQuery('#docOrigin, #docModel').prop('disabled', true); return; }
        jQuery.get(routes.getProductsByCategory, { category_id: catId }, function (res) {
            var o = '<option value="">Chọn sản phẩm</option>';
            (res || []).forEach(function (p) { o += '<option value="' + p.id + '">' + (p.name || p.product_name || '') + '</option>'; });
            jQuery('#docProduct').html(o).prop('disabled', false);
            jQuery('#docOrigin').html('<option value="">Xuất xứ</option>').prop('disabled', true);
            jQuery('#docModel').html('<option value="">Chọn model</option>').prop('disabled', true);
        });
    }
    function loadOrigins(pid) {
        if (!pid) { jQuery('#docOrigin').html('<option value="">Xuất xứ</option>').prop('disabled', true); jQuery('#docModel').prop('disabled', true); return; }
        jQuery.get(routes.getOriginsByProduct, { product_id: pid }, function (res) {
            var o = '<option value="">Xuất xứ</option>';
            (res || []).forEach(function (x) { o += '<option value="' + (x.xuat_xu || '') + '">' + (x.xuat_xu || '') + '</option>'; });
            jQuery('#docOrigin').html(o).prop('disabled', false);
            jQuery('#docModel').html('<option value="">Chọn model</option>').prop('disabled', true);
        });
    }
    function loadModels(pid, origin) {
        if (!pid || !origin) { jQuery('#docModel').html('<option value="">Chọn model</option>').prop('disabled', true); return; }
        jQuery.get(routes.getModelsByOrigin, { product_id: pid, xuat_xu: origin }, function (res) {
            var o = '<option value="">Chọn model</option>';
            (res || []).forEach(function (m) { o += '<option value="' + m.id + '">' + (m.model_code || '') + (m.version ? ' (' + m.version + ')' : '') + '</option>'; });
            jQuery('#docModel').html(o).prop('disabled', false);
        });
    }
    jQuery('#docCategory').on('change', function () { loadProducts(jQuery(this).val()); });
    jQuery('#docProduct').on('change', function () { loadOrigins(jQuery(this).val()); });
    jQuery('#docOrigin').on('change', function () { loadModels(jQuery('#docProduct').val(), jQuery(this).val()); });
})();
</script>
@endsection
