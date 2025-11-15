@extends('layout.layout')

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4 mb-1">
                <input type="number" id="sophieu" name="sophieu" class="form-control" 
                placeholder="Nhập số phiếu" value="{{ request('sophieu') }}">
            </div>
            <div class="col-md-4 mb-1 position-relative"> <input type="text" id="tensp" name="tensp" class="form-control" 
                placeholder="Nhập tên sản phẩm" value="{{ request('productSearch') }}"
                autocomplete="off"> <div id="tensp-suggestions" class="list-group position-absolute w-100 d-none" style="z-index: 1000;">
            </div>
            </div>
            <div class="col-md-4 mb-1">
                <div class="d-flex align-items-center flex-grow-1">
                    <input type="date" id="tungay" name="tungay" class="form-control"
                        value="{{ $startOfMonth}}">
                    <label for="toDate" class="mb-0 me-2 ms-1">đến</label>
                    <input type="date" id="denngay" name="denngay" class="form-control"
                        value="{{ $toDay}}">
                </div>
            </div>
        <div class="col-md-4 mb-1 d-flex gap-2">
            <button id="searchCard" class="btn btn-primary flex-grow-1">Tìm kiếm</button>
            <button id="resetFilters" class="btn btn-outline-secondary flex-grow-1">Xóa bộ lọc</button>
        </div>
        </div>
    </div>
    
    <div class="container-fluid d-flex flex-column justify-content-start">
        <div class="mb-1 d-flex justify-content-between">
            <a href="#" class="btn btn-primary" id="openform">Thêm mới</a>
            <a href="#" class="btn btn-success" id="exportActiveWarranty">Thống kê số lượng kích hoạt</a>
        </div>
        <div class="table-container" style="overflow-x: auto;">
            <div id="tableContent">
                @include('printwarranty.tablebody', ['lstWarrantyCard' => $lstWarrantyCard])
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-center mt-3">
        @if ($lstWarrantyCard->total() > 50)
        <nav aria-label="Page navigation">
            <ul class="pagination">
                {{-- Nút Trang đầu --}}
                @if ($lstWarrantyCard->currentPage() > 1)
                <li class="page-item">
                    <a class="page-link" href="{{ $lstWarrantyCard->url(1) }}">Trang đầu</a>
                </li>
                @endif
    
                {{-- Nút Trang trước --}}
                @if ($lstWarrantyCard->onFirstPage())
                <li class="page-item disabled"><span class="page-link">«</span></li>
                @else
                <li class="page-item"><a class="page-link" href="{{ $lstWarrantyCard->previousPageUrl() }}">«</a></li>
                @endif
    
                {{-- Số trang --}}
                @for ($i = max(1, $lstWarrantyCard->currentPage() - 1); $i <= min($lstWarrantyCard->currentPage() + 1, $lstWarrantyCard->lastPage()); $i++)
                    <li class="page-item {{ $i == $lstWarrantyCard->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $lstWarrantyCard->url($i) }}">{{ $i }}</a>
                    </li>
                @endfor
    
                {{-- Nút Trang tiếp --}}
                @if ($lstWarrantyCard->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $lstWarrantyCard->nextPageUrl() }}">»</a></li>
                @else
                <li class="page-item disabled"><span class="page-link">»</span></li>
                @endif
                {{-- Nút Trang cuối --}}
                @if ($lstWarrantyCard->currentPage() < $lstWarrantyCard->lastPage())
                    <li class="page-item">
                        <a class="page-link" href="{{ $lstWarrantyCard->url($lstWarrantyCard->lastPage()) }}">Trang cuối</a>
                    </li>
                @endif
            </ul>
        </nav>
        @endif
    </div>
    
    <!-- Modal thêm phiếu -->
    <div class="modal fade" id="warrantyModal" tabindex="-1" aria-labelledby="warrantyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warrantyModalLabel">Thêm Phiếu Bảo Hành</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 position-relative">
                        <input type="text" class="form-control" hidden id="product_id" name="product_id" value="">
                        <label for="product" class="form-label">Tên sản phẩm</label>
                        <input type="text" class="form-control" id="product" name="product">
                        <div id="product_suggestions" class="list-group position-absolute w-100 d-none"></div>
                        <div class="error product_error text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3" id="radTypeSerial">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="serial_option" id="auto_serial" value="0" checked>
                            <label class="form-check-label" for="auto_serial">In Tem Bảo Hành</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="serial_option" id="import_serial" value="1">
                            <label class="form-check-label" for="import_serial">Nhập Mã Serial Nhà Máy</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="serial_option" id="import_excel" value="2">
                            <label class="form-check-label" for="import_excel">Import Excel</label>
                        </div>
                    </div>
                    <div class="mb-3" id="quantityInput">
                        <label for="quantity" class="form-label">Số lượng</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1">
                        <div class="error quantity_error text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3" id="serialRangeInput">
                        <label for="serial_range" class="form-label">Dải serial</label>
                        <textarea class="form-control text-uppercase" id="serial_range" name="serial_range" rows="6" placeholder="ví dụ: ES202505200001-ES202505200005, ES202505200010"></textarea>
                        <div class="error serial_range_error text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3" id="fileExcel">
                        <label class="form-label"><strong>File mẫu: <a href="{{ asset('storage/app/public/filemau.xlsx') }}" class="text-success" download>filemau.xlsx</a></strong></label>
                        <input type="file" class="form-control" id="serial_file" name="serial_file" accept=".xls,.csv,.xlsx" />
                        <div class="error serial_file_error text-danger small mt-1"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success submit-btn" data-action="add">Lưu & Thêm</button>
                    <button type="button" class="btn btn-success submit-btn" data-action="close">Lưu & Đóng</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Load validation functions trước -->
    <script src="{{ asset('js/printwarranty/validations.js') }}"></script>
    
    <!-- Load các module chức năng -->
    <script src="{{ asset('js/printwarranty/ui.js') }}"></script>
    <script src="{{ asset('js/printwarranty/submit.js') }}"></script>
    <script src="{{ asset('js/printwarranty/serial_input.js') }}"></script>
    <script src="{{ asset('js/printwarranty/file.js') }}"></script>
    <script src="{{ asset('js/printwarranty/search.js') }}"></script>
    <script src="{{ asset('js/printwarranty/export.js') }}"></script>
    
    <!-- Khởi tạo dữ liệu và main file -->
    <script>
        // Khởi tạo dữ liệu cho JavaScript
        productList = {!! json_encode($products) !!};
        mainProductList = @json(collect($products)->pluck('product_name'));
        routes = {
            create: '{{ route("warrantycard.create") }}',
            search: '{{ route("warrantycard.search") }}',
            partial: '{{ route("warrantycard.partial") }}',
            exportActiveWarranty: '{{ route("baocaokichhoatbaohanh") }}'
        };
        
        // Khởi tạo khi document ready
        $(document).ready(function() {
            initPrintWarranty();
        });
    </script>
    <script src="{{ asset('js/printwarranty/index.js') }}"></script>
@endsection