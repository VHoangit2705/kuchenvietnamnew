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
                                <span
                                    class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill">
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
                            <a href="{{ route('warranty.document.errors.create', ['product_id' => request('product_id'), 'xuat_xu' => request('xuat_xu')]) }}"
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
                <form method="get" action="{{ route('warranty.document.errors.index') }}" class="row g-3 mb-4"
                    id="formFilter">
                    <x-technicaldocument.product-filter variant="simple" idPrefix="filter" enableFormSubmission="true"
                        :categories="$categories" :selectedCategoryId="$filter['category_id'] ?? ''"
                        :selectedProductId="$filter['product_id'] ?? ''" :selectedOrigin="$filter['xuat_xu'] ?? ''" />

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" id="btnFilter">Xem danh sách</button>
                    </div>
                </form>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($productModel)
                    <h5 class="fw-bold mb-3">Sản phẩm: <span
                            class="text-primary">{{ $productModel->product->product_name ?? '' }}</span> - Xuất xứ: <span
                            class="text-primary">{{ $productModel->xuat_xu }}</span></h5>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="table-responsive">
                            <x-technicaldocument.error-management-table :errors="$errors" />
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0 text-center py-5 bg-light rounded border border-dashed">
                        <i class="bi bi-arrow-up-circle fs-3 d-block mb-2"></i>
                        Vui lòng chọn danh mục -> sản phẩm -> xuất xứ để xem danh sách lỗi.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Configuration for modules
        window.errorIndexRoutes = {
            getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
            getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
            getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}",
            destroyError: "{{ url('baohanh/tailieukithuat/errors') }}"
        };

        window.errorIndexData = {
            csrf: '{{ csrf_token() }}',
            product_id: "{{ request('product_id') }}",
            xuat_xu: "{{ request('xuat_xu') }}",
            filter: @json(isset($filter) ? $filter : ['category_id' => '', 'product_id' => '', 'xuat_xu' => ''])
        };
    </script>
    <script src="{{ asset('js/technicaldocument/filter.js') }}"></script>
    <script src="{{ asset('js/technicaldocument/error-index.js') }}"></script>
@endsection