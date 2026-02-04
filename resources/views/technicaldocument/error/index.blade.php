@extends('layout.layout')

@section('content')
<div class="container-fluid py-5 bg-light min-vh-100">
    <div class="row mb-4">
    <div class="col-12">
        <div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-primary">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                
                {{-- Phần Tiêu đề --}}
                <div>
                    <h3 class="fw-bold text-uppercase text-primary mb-1">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Quản Lý Mã Lỗi
                    </h3>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill">
                            Technical Errors
                        </span>
                        <span class="text-muted small border-start ps-2 ms-1">Danh sách mã lỗi theo model</span>
                    </div>
                </div>

                {{-- Phần Nút bấm --}}
                <div class="d-flex flex-wrap gap-2">
                    {{-- Nút Quay lại --}}
                    <a href="{{ route('warranty.document.documents.index') }}" 
                       class="btn btn-light text-secondary fw-bold border px-4 py-2 shadow-sm hover-shadow">
                        <i class="bi bi-arrow-return-left me-2"></i>Quay lại Tài liệu
                    </a>

                    {{-- Nút Thêm mới (Quan trọng nhất) --}}
                    <a href="{{ route('warranty.document.errors.create', ['model_id' => request('model_id')]) }}" 
                       class="btn btn-primary fw-bold px-4 py-2 shadow {{ !$productModel ? 'disabled' : '' }}">
                        <i class="bi bi-plus-circle-fill me-2"></i>Thêm Mã Lỗi Mới
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <!-- Filter Section -->
            <form method="get" action="{{ route('warranty.document.errors.index') }}" class="row g-3 mb-4" id="formFilter">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Danh mục</label>
                    <select class="form-select" id="filterCategory" name="category_id">
                        <option value="">Chọn danh mục</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ (isset($filter['category_id']) && $filter['category_id'] == $c->id) ? 'selected' : '' }}>{{ $c->name_vi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Sản phẩm</label>
                    <select class="form-select" id="filterProduct" name="product_id" disabled>
                        <option value="">Chọn sản phẩm</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Xuất xứ</label>
                    <select class="form-select" id="filterOrigin" disabled>
                        <option value="">Xuất xứ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Model</label>
                    <select class="form-select" id="filterModel" name="model_id">
                        <option value="">Chọn model</option>
                        @if($productModel)
                            @foreach($productModel ? [] : [] as $m)
                                <option value="{{ $m->id }}">{{ $m->model_code }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" id="btnFilter">Xem danh sách</button>
                </div>
            </form>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($productModel)
                <h5 class="fw-bold mb-3">Model: <span class="text-primary">{{ $productModel->model_code }}</span> {{ $productModel->version ? ' (' . $productModel->version . ')' : '' }}</h5>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            {{-- Header: Đồng bộ màu xanh chủ đạo --}}
            <thead class="bg-primary text-white">
                <tr>
                    <th class="py-3 ps-4" style="width: 150px;">Mã Lỗi</th>
                    <th class="py-3" style="min-width: 200px;">Tên Lỗi</th>
                    <th class="py-3 text-center" style="width: 150px;">Mức độ</th>
                    <th class="py-3" style="min-width: 250px;">Mô tả kỹ thuật</th>
                    <th class="py-3 text-end pe-4" style="width: 120px;">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse($errors as $error)
                    <tr>
                        {{-- Mã lỗi: Font Monospace để giống giao diện code --}}
                        <td class="ps-4">
                            <span class="font-monospace text-white fw-bold text-primary bg-primary bg-opacity-10 px-2 py-1 rounded border border-primary border-opacity-25">
                                {{ $error->error_code }}
                            </span>
                        </td>
                        
                        {{-- Tên lỗi: Đậm --}}
                        <td class="fw-bold text-dark">
                            {{ $error->error_name }}
                        </td>
                        
                        {{-- Mức độ: Badge đẹp hơn, có icon --}}
                        <td class="text-center">
                            @if($error->severity == 'critical')
                                <span class="badge text-white rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2">
                                    <i class="bi bi-x-octagon-fill me-1"></i>Nghiêm trọng
                                </span>
                            @elseif($error->severity == 'common')
                                <span class="badge text-white rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Phổ biến
                                </span>
                            @else
                                <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">
                                    <i class="bi bi-info-circle-fill me-1"></i>Bình thường
                                </span>
                            @endif
                        </td>
                        
                        {{-- Mô tả --}}
                        <td class="text-muted small">
                            {{ Str::limit($error->description, 60) }}
                        </td>

                        {{-- Thao tác: Nút tròn chuẩn, icon căn giữa --}}
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                {{-- Nút Sửa --}}
                                <a href="{{ route('warranty.document.errors.edit', $error->id) }}" 
                                   class="btn btn-outline-primary rounded-circle d-flex justify-content-center align-items-center" 
                                   style="width: 32px; height: 32px;"
                                   title="Chỉnh sửa" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                {{-- Nút Xóa --}}
                                <button type="button" 
                                        class="btn btn-outline-danger rounded-circle d-flex justify-content-center align-items-center btn-delete-error" 
                                        style="width: 32px; height: 32px;"
                                        data-id="{{ $error->id }}" 
                                        data-code="{{ $error->error_code }}"
                                        title="Xóa mã lỗi" data-bs-toggle="tooltip">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center text-muted">
                                <i class="bi bi-bug fs-1 opacity-50 mb-2"></i>
                                <p class="mb-0 fw-bold">Chưa có mã lỗi nào</p>
                                <small>Danh sách mã lỗi cho model này đang trống.</small>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
            @else
                <p class="text-muted mb-0 text-center py-5 bg-light rounded border border-dashed">
                    <i class="bi bi-arrow-up-circle fs-3 d-block mb-2"></i>
                    Vui lòng chọn danh mục -> sản phẩm -> model để xem danh sách lỗi.
                </p>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    var routes = {
        getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
        getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
        getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}",
        destroyError: "{{ url('baohanh/tailieukithuat/errors') }}"
    };
    var csrf = '{{ csrf_token() }}';
    var currentModelId = "{{ request('model_id') }}";
    var filter = @json(isset($filter) ? $filter : ['category_id' => '', 'product_id' => '', 'xuat_xu' => '']);

    // Reuse Filter Logic (Clean Code: should be extracted to a common JS file)
    function loadProducts(categoryId, selectProductId) {
        if (!categoryId) { jQuery('#filterProduct').html('<option value="">Chọn sản phẩm</option>').prop('disabled', true); jQuery('#filterOrigin, #filterModel').html('<option value="">...</option>').prop('disabled', true); return; }
        jQuery.get(routes.getProductsByCategory, { category_id: categoryId }, function (res) {
            var opts = '<option value="">Chọn sản phẩm</option>';
            (res || []).forEach(function (p) {
                var sel = (selectProductId && p.id == selectProductId) ? ' selected' : '';
                opts += '<option value="' + p.id + '"' + sel + '>' + (p.name || p.product_name || '') + '</option>';
            });
            jQuery('#filterProduct').html(opts).prop('disabled', false);
            jQuery('#filterOrigin').html('<option value="">Xuất xứ</option>').prop('disabled', true);
            jQuery('#filterModel').html('<option value="">Chọn model</option>').prop('disabled', true);
            if (selectProductId) loadOrigins(selectProductId, filter.xuat_xu);
        });
    }
    function loadOrigins(productId, selectOrigin) {
        if (!productId) { jQuery('#filterOrigin').html('<option value="">Xuất xứ</option>').prop('disabled', true); jQuery('#filterModel').html('<option value="">Chọn model</option>').prop('disabled', true); return; }
        jQuery.get(routes.getOriginsByProduct, { product_id: productId }, function (res) {
            var opts = '<option value="">Xuất xứ</option>';
            (res || []).forEach(function (o) {
                var x = o.xuat_xu || '';
                var sel = (selectOrigin && x === selectOrigin) ? ' selected' : '';
                opts += '<option value="' + x + '"' + sel + '>' + x + '</option>';
            });
            jQuery('#filterOrigin').html(opts).prop('disabled', false);
            jQuery('#filterModel').html('<option value="">Chọn model</option>').prop('disabled', true);
            if (selectOrigin) loadModels(productId, selectOrigin);
        });
    }
    function loadModels(productId, origin) {
        if (!productId || !origin) { jQuery('#filterModel').html('<option value="">Chọn model</option>').prop('disabled', true); return; }
        jQuery.get(routes.getModelsByOrigin, { product_id: productId, xuat_xu: origin }, function (res) {
            var opts = '<option value="">Chọn model</option>';
            (res || []).forEach(function (m) {
                var sel = (currentModelId && m.id == currentModelId) ? ' selected' : '';
                opts += '<option value="' + m.id + '"' + sel + '>' + (m.model_code || '') + (m.version ? ' (' + m.version + ')' : '') + '</option>';
            });
            jQuery('#filterModel').html(opts).prop('disabled', false);
        });
    }

    jQuery('#filterCategory').on('change', function () { loadProducts(jQuery(this).val()); });
    jQuery('#filterProduct').on('change', function () { loadOrigins(jQuery(this).val()); });
    jQuery('#filterOrigin').on('change', function () { loadModels(jQuery('#filterProduct').val(), jQuery(this).val()); });

    (function initFilter() {
        var cat = jQuery('#filterCategory').val();
        if (cat) {
            if (filter.product_id) {
                loadProducts(cat, filter.product_id);
            } else {
                loadProducts(cat);
            }
        }
    })();

    // Delete Logic
    jQuery(document).on('click', '.btn-delete-error', function () {
        var id = jQuery(this).data('id');
        var code = jQuery(this).data('code');
        if (!confirm('Bạn có chắc muốn xóa mã lỗi "' + code + '"?')) return;
        
        jQuery.ajax({
            url: routes.destroyError + '/' + id,
            type: 'DELETE',
            data: { _token: csrf },
            success: function () { location.reload(); },
            error: function (xhr) { alert('Có lỗi xảy ra khi xóa.'); }
        });
    });
})();
</script>
@endsection
