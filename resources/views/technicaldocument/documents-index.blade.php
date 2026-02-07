@extends('layout.layout')

@section('content')
<div class="container-fluid py-5 bg-light min-vh-100">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-white p-4 rounded-4 shadow-sm border-start border-5 border-primary">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-center">

                    <div class="mb-3 mb-xl-0 w-100">
                        <h3 class="fw-bold text-uppercase text-primary mb-1">
                            <i class="bi bi-folder-check me-2"></i>Tài Liệu Kỹ Thuật
                        </h3>
                        <span class="badge bg-light text-secondary border rounded-pill px-3">
                            Quản lý Model & Mã lỗi
                        </span>
                    </div>

                    <div class="d-flex flex-wrap flex-md-nowrap gap-2 w-100 justify-content-xl-end">
                        {{-- Nút Tra cứu --}}
                        <a href="{{ route('warranty.document') }}"
                            class="btn btn-white border px-3 py-2 fw-bold text-secondary flex-fill">
                            <i class="bi bi-search"></i> Tra cứu
                        </a>

                        @if(Auth::user()->hasAnyRole(['Admin']))
                        {{-- Nút Quản lý Mã lỗi (Màu vàng cảnh báo/Info) --}}
                        <a href="{{ route('warranty.document.errors.index') }}"
                            class="btn btn-warning text-dark px-3 py-2 fw-bold bg-opacity-25 border-warning flex-fill">
                            <i class="bi bi-gear-wide-connected"></i> QL Mã lỗi
                        </a>

                        {{-- Nút Thêm Mã lỗi (Màu xanh lá) --}}
                        <a href="{{ route('warranty.document.create') }}"
                            class="btn btn-success px-3 py-2 fw-bold text-white flex-fill shadow-sm">
                            <i class="bi bi-plus-circle"></i> Tạo Mã lỗi
                        </a>

                        {{-- Nút Thêm Tài liệu (Màu chủ đạo - Lớn nhất) --}}
                        <a href="{{ route('warranty.document.documents.create') }}"
                            class="btn btn-primary px-4 py-2 fw-bold text-white flex-fill shadow">
                            <i class="bi bi-file-earmark-arrow-up-fill"></i> Tải lên TL
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <form method="get" action="{{ route('warranty.document.documents.index') }}" class="row g-3 mb-4"
                id="formFilter">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Danh mục</label>
                    <select class="form-select" id="filterCategory" name="category_id">
                        <option value="">Chọn danh mục</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ (isset($filter['category_id']) && $filter['category_id']==$c->
                            id) ? 'selected' : '' }}>{{ $c->name_vi }}</option>
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
                    <select class="form-select" id="filterModel" name="model_id" disabled>
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
            <h5 class="fw-bold mb-3">Model: {{ $productModel->model_code }}{{ $productModel->version ? ' (' .
                $productModel->version . ')' : '' }}</h5>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <x-technicaldocument.documents-list-table :documents="$documents" />
                </div>
            </div>
            @else
            <p class="text-muted mb-0">Chọn danh mục → Sản phẩm → Xuất xứ → Model rồi bấm "Xem danh sách" để xem tài
                liệu.</p>
            @endif
        </div>
    </div>
</div>

<!-- Modal Quản Lý Chia Sẻ -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-share me-2"></i>Chia sẻ tài liệu: <span
                        id="shareDocTitle" class="text-primary"></span></h5>
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
                                    <input type="text" class="form-control form-control-sm" name="password"
                                        placeholder="Để trống nếu công khai">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Hết hạn (Tùy chọn)</label>
                                    <input type="datetime-local" class="form-control form-control-sm" name="expires_at">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-sm w-100"><i
                                            class="bi bi-link-45deg me-1"></i>Tạo Link</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách link đã tạo -->
                <h6 class="fw-bold mb-2">Danh sách liên kết đang hoạt động</h6>
                <div class="table-responsive">
                    <x-technicaldocument.share-links-table />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configuration for modules
window.docIndexRoutes = {
    getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
    getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
    getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}",
    destroyDocument: "{{ url('baohanh/tailieukithuat/documents') }}",
    shareStore: "{{ route('warranty.document.share.store') }}",
    shareList: "{{ url('baohanh/tailieukithuat/share/list') }}",
    shareRevoke: "{{ url('baohanh/tailieukithuat/share/revoke') }}"
};

window.docIndexData = {
    csrf: '{{ csrf_token() }}',
    currentModelId: "{{ request('model_id') }}",
    filter: @json(isset($filter) ? $filter : ['category_id' => '', 'product_id' => '', 'xuat_xu' => ''])
};
</script>
<script src="{{ asset('js/technicaldocument/filter.js') }}"></script>
<script src="{{ asset('js/technicaldocument/documents-index.js') }}"></script>
@endsection
