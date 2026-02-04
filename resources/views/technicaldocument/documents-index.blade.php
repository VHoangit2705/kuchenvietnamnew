@extends('layout.layout')

@section('content')
<div class="container-fluid py-5 bg-light min-vh-100">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm">
                <div>
                    <h2 class="fw-bold text-primary mb-1"><i class="bi bi-folder2-open me-2"></i>Quản Lý Tài Liệu Kỹ Thuật</h2>
                    <p class="text-muted mb-0 small">Danh sách tài liệu theo model sản phẩm</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('warranty.document') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-search me-1"></i>Tra cứu lỗi
                    </a>
                    <a href="{{ route('warranty.document.create') }}" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-plus-lg me-1"></i>Thêm mã lỗi / Hướng dẫn
                    </a>
                    <a href="{{ route('warranty.document.documents.create') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-upload me-1"></i>Thêm tài liệu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <form method="get" action="{{ route('warranty.document.documents.index') }}" class="row g-3 mb-4" id="formFilter">
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
                <h5 class="fw-bold mb-3">Model: {{ $productModel->model_code }}{{ $productModel->version ? ' (' . $productModel->version . ')' : '' }}</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Loại</th>
                                <th>Tiêu đề</th>
                                <th>Phiên bản</th>
                                <th>Trạng thái</th>
                                <th style="width: 200px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $doc)
                                <tr>
                                    <td>{{ $doc->id }}</td>
                                    <td><span class="badge bg-secondary">{{ $doc->doc_type }}</span></td>
                                    <td>{{ $doc->title }}</td>
                                    <td>{{ $doc->document_versions_count ?? 0 }}</td>
                                    <td><span class="badge {{ $doc->status === 'active' ? 'bg-success' : ($doc->status === 'deprecated' ? 'bg-warning' : 'bg-secondary') }}">{{ $doc->status }}</span></td>
                                    <td>
                                        <a href="{{ route('warranty.document.documents.show', $doc->id) }}" class="btn btn-sm btn-outline-secondary me-1">Xem chi tiết</a>
                                        <a href="{{ route('warranty.document.documents.edit', $doc->id) }}" class="btn btn-sm btn-outline-primary me-1">Sửa</a>
                                        @if($doc->documentVersions->isNotEmpty())
                                            <button type="button" class="btn btn-sm btn-info me-1 text-white btn-share-doc" 
                                                data-version-id="{{ $doc->documentVersions->sortByDesc('id')->first()->id }}"
                                                data-doc-title="{{ $doc->title }}">
                                                <i class="bi bi-share me-1"></i>Chia sẻ
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-doc" data-id="{{ $doc->id }}" data-title="{{ e($doc->title) }}">Xóa</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có tài liệu nào. Chọn model và bấm "Thêm tài liệu".</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">Chọn danh mục → Sản phẩm → Xuất xứ → Model rồi bấm "Xem danh sách" để xem tài liệu.</p>
            @endif
        </div>
    </div>
</div>

<!-- Modal Quản Lý Chia Sẻ -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-share me-2"></i>Chia sẻ tài liệu: <span id="shareDocTitle" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form tạo link mới -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Tạo liên kết chia sẻ mới</h6>
                        <form id="createShareForm">
                            <input type="hidden" name="document_version_id" id="shareVersionId">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small">Quyền hạn</label>
                                    <select class="form-select form-select-sm" name="permission">
                                        <option value="view">Chỉ xem</option>
                                        <option value="download">Được tải về</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Mật khẩu (Tùy chọn)</label>
                                    <input type="text" class="form-control form-control-sm" name="password" placeholder="Để trống nếu công khai">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Hết hạn (Tùy chọn)</label>
                                    <input type="datetime-local" class="form-control form-control-sm" name="expires_at">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-link-45deg me-1"></i>Tạo Link</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách link đã tạo -->
                <h6 class="fw-bold mb-2">Danh sách liên kết đang hoạt động</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Link Chia Sẻ</th>
                                <th>Quyền</th>
                                <th>Bảo mật</th>
                                <th>Hết hạn</th>
                                <th>Lượt xem</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="shareListBody">
                            <!-- Dữ liệu sẽ được load bằng JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var routes = {
        getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
        getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
        getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}",
        destroyDocument: "{{ url('baohanh/tailieukithuat/documents') }}",
        shareStore: "{{ route('warranty.document.share.store') }}",
        shareList: "{{ url('baohanh/tailieukithuat/share/list') }}",
        shareRevoke: "{{ url('baohanh/tailieukithuat/share/revoke') }}"
    };
    var csrf = '{{ csrf_token() }}';
    var currentModelId = "{{ request('model_id') }}";
    var filter = @json(isset($filter) ? $filter : ['category_id' => '', 'product_id' => '', 'xuat_xu' => '']);

    // --- Filter Logic ---
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

    // --- Delete Document Logic ---
    jQuery(document).on('click', '.btn-delete-doc', function () {
        var id = jQuery(this).data('id');
        var title = jQuery(this).data('title') || '';
        if (!confirm('Bạn có chắc muốn xóa tài liệu "' + title + '"?')) return;
        jQuery.ajax({
            url: routes.destroyDocument + '/' + id,
            type: 'DELETE',
            data: { _token: csrf },
            success: function () { location.reload(); },
            error: function (xhr) { alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra.'); }
        });
    });

    // --- Share Document Logic ---
    var shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
    
    // Open Modal
    jQuery(document).on('click', '.btn-share-doc', function() {
        var versionId = jQuery(this).data('version-id');
        var title = jQuery(this).data('doc-title');
        
        jQuery('#shareDocTitle').text(title);
        jQuery('#shareVersionId').val(versionId);
        
        loadShareList(versionId);
        shareModal.show();
    });

    // Load Share List
    function loadShareList(versionId) {
        jQuery('#shareListBody').html('<tr><td colspan="6" class="text-center text-muted">Đang tải...</td></tr>');
        jQuery.get(routes.shareList + '/' + versionId, function(data) {
            var html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="6" class="text-center text-muted">Chưa có liên kết chia sẻ nào.</td></tr>';
            } else {
                data.forEach(function(item) {
                    var statusBadge = item.status === 'active' 
                        ? (item.is_expired ? '<span class="badge bg-warning text-dark">Hết hạn</span>' : '<span class="badge bg-success">Hoạt động</span>')
                        : '<span class="badge bg-secondary">Đã hủy</span>';
                    
                    html += `<tr>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="${item.full_url}" readonly>
                                <button class="btn btn-outline-secondary btn-copy" type="button" data-url="${item.full_url}"><i class="bi bi-clipboard"></i></button>
                            </div>
                        </td>
                        <td>${item.permission === 'download' ? '<span class="badge bg-primary">Tải về</span>' : '<span class="badge bg-info text-dark">Xem</span>'}</td>
                        <td>${item.has_password ? '<i class="bi bi-lock-fill text-warning" title="Có mật khẩu"></i>' : '<i class="bi bi-globe text-success" title="Công khai"></i>'}</td>
                        <td class="small">${item.expires_at}</td>
                        <td class="text-center">${item.access_count}</td>
                        <td class="text-center">
                            ${item.status === 'active' ? `<button class="btn btn-sm btn-outline-danger btn-revoke" data-id="${item.id}">Thu hồi</button>` : '-'}
                        </td>
                    </tr>`;
                });
            }
            jQuery('#shareListBody').html(html);
        });
    }

    // Create Share Link
    jQuery('#createShareForm').on('submit', function(e) {
        e.preventDefault();
        var formData = jQuery(this).serializeArray();
        formData.push({name: '_token', value: csrf});
        
        jQuery.post(routes.shareStore, formData, function(res) {
            alert(res.message);
            jQuery('#createShareForm')[0].reset();
            jQuery('#shareVersionId').val(formData.find(x => x.name === 'document_version_id').value); // Restore ID
            loadShareList(jQuery('#shareVersionId').val());
        }).fail(function(xhr) {
            alert(xhr.responseJSON?.message || 'Lỗi khi tạo link.');
        });
    });

    // Revoke Link
    jQuery(document).on('click', '.btn-revoke', function() {
        if(!confirm('Bạn có chắc chắn muốn thu hồi liên kết này không? Người dùng sẽ không thể truy cập nữa.')) return;
        var id = jQuery(this).data('id');
        jQuery.post(routes.shareRevoke + '/' + id, {_token: csrf}, function(res) {
            loadShareList(jQuery('#shareVersionId').val());
        });
    });

    // Copy to Clipboard
    jQuery(document).on('click', '.btn-copy', function() {
        var url = jQuery(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            alert('Đã sao chép liên kết!');
        });
    });

})();
</script>
@endsection
