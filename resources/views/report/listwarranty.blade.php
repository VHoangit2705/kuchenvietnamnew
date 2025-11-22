@extends('layout.layout')

@section('content')
<form method="GET" action="{{ route('baocao') }}" id="reportFilterForm">
    <input type="hidden" name="tab" value="{{ $activeTab }}" id="activeTabInput">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4 mb-1 position-relative">
                <input type="text" id="product" name="product" class="form-control" value="{{ request('product') }}" placeholder="Nhập tên hoặc mã seri sản phẩm" autocomplete="off">
            </div>

            <div class="col-12 col-md-6 col-lg-4 mb-1 position-relative">
                <div style="position: relative;">
                    <input type="text" id="replacement" name="replacement" class="form-control"
                        placeholder="Nhập linh kiện thay thế" autocomplete="off">
                    <div id="replacement-suggestions" class="list-group position-absolute w-100 d-none"
                    style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 mb-1">
                <select id="warrantySelect" name="warrantySelect" class="form-select flex-grow-1">
                    <option value="tatca" {{ request('warrantySelect') == 'tatca' ? 'selected' : '' }}>Loại bảo hành
                    </option>
                    <option value="conbaohanh" {{ request('warrantySelect') == 'conbaohanh' ? 'selected' : '' }}>Còn hạn
                        bảo hành</option>
                    <option value="hetbaohanh" {{ request('warrantySelect') == 'hetbaohanh' ? 'selected' : '' }}>Hết hạn
                        bảo hành</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-4 mb-1">
                @if (session('position') != 'Kỹ thuật viên')
                <select class="form-select" id="branch" name="branch">
                    <option value="">Tất cả chi nhánh</option>
                    @if (session('brand') == 'kuchen')
                    <option value="KUCHEN VINH" {{ request('branch') == 'KUCHEN VINH' ? 'selected' : '' }}>KUCHEN VINH </option>
                    <option value="KUCHEN HÀ NỘI" {{ request('branch') == 'KUCHEN HÀ NỘI' ? 'selected' : '' }}>KUCHEN HÀ NỘI</option>
                    <option value="KUCHEN HCM" {{ request('branch') == 'Kuchen HCM' ? 'selected' : '' }}>KUCHEN HCM </option>
                    @else
                    <option value="HUROM VINH" {{ request('branch') == 'HUROM VINH' ? 'selected' : '' }}>HUROM VINH </option>
                    <option value="HUROM HÀ NỘI" {{ request('branch') == 'HUROM HÀ NỘI' ? 'selected' : '' }}>HUROM HÀ NỘI</option>
                    <option value="HUROM HCM" {{ request('branch') == 'HUROM HCM' ? 'selected' : '' }}>HUROM HCM </option>
                    @endif
                </select>
                @else
                <select class="form-select" name="branch_display" disabled>
                    <option selected>{{ $userBranch }}</option>
                </select>
                <input type="hidden" name="branch" value="{{ $userBranch }}">
                @endif
            </div>

            <div class="col-12 col-md-6 col-lg-4 mb-1 position-relative">
                <input type="text" id="staff_received" name="staff_received" class="form-control" value="{{ request('staff_received') }}" placeholder="Nhập tên kỹ thuật viên">
            </div>

            <div class="col-12 col-md-6 col-lg-4 mb-1">
                <div class="d-flex align-items-center flex-grow-1">
                    <input type="date" id="fromDate" name="fromDate" class="form-control"
                        value="{{ $fromDate->toDateString() }}">
                    <label for="toDate" class="mb-0 me-2 ms-1">đến</label>
                    <input type="date" id="toDate" name="toDate" class="form-control"
                        value="{{ $toDate->toDateString() }}">
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 mb-1">
                <select id="solution" name="solution" class="form-select flex-grow-1">
                    <option value="tatca" selected="">Trường hợp bảo hành</option>
                    <option value="sửa chữa tại chỗ" {{ request('solution') == 'sửa chữa tại chỗ' ? 'selected' : '' }}>Sửa chữa tại chỗ (lỗi nhẹ)</option>
                    <option value="kiểm tra chuyên sâu" {{ request('solution') == 'kiểm tra chuyên sâu' ? 'selected' : '' }}>Giữ lại để kiểm tra chuyên sâu</option>
                    <option value="thay thế linh kiện" {{ request('solution') == 'thay thế linh kiện' ? 'selected' : '' }}>Thay thế linh kiện/hardware</option>
                    <option value="đổi mới sản phẩm" {{ request('solution') == 'đổi mới sản phẩm' ? 'selected' : '' }}>Đổi mới sản phẩm</option>
                    <option value="gửi về trung tâm bảo hành" {{ request('solution') == 'gửi về trung tâm bảo hành' ? 'selected' : '' }}>Gửi về trung tâm bảo hành NSX</option>
                    <option value="từ chối bảo hành" {{ request('solution') == 'từ chối bảo hành' ? 'selected' : '' }}>Từ chối bảo hành</option>
                    <option value="khách hàng không muốn bảo hành" {{ request('solution') == 'khách hàng không muốn bảo hành' ? 'selected' : '' }}>Khách hàng không muốn bảo hành</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-4 mb-1">
                <button type="submit" id="btnSearch" class="btnsearch btn btn-primary fw-bold me-2">
                    <img src="{{ asset('icons/filter.png') }}" alt="Filter Icon" style="width: 16px; height: 16px;">
                    Lọc
                </button>
                <button type="button" id="resetFiltersReport" class="btn btn-outline-secondary fw-bold me-2">
                    Xóa bộ lọc
                </button>
                <a href="#" id="exportExcel" class="btn btn-success fw-bold">
                    Xuất Excel
                </a>
            </div>
        </div>
    </div>
</form>

{{-- Tab header --}}
<div id="reportTabHeader">
    @include('components.tab_header_report_warranty')
</div>

<div class="container-fluid d-flex flex-column justify-content-start mt-3">
    <div class="table-container" style="overflow-x: auto;">
        <table class="table table-striped table-hover" id="reportTableContent">
            @if($activeTab == 'work_process')
                {{-- Bảng thống kê quá trình làm việc --}}
                @include('report.table_content_report.table_content_work_process')
            @else
                {{-- Bảng thống kê ca bảo hành --}}
                @include('report.table_content_report.table_content')
            @endif
        </table>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/report_warranty.css') }}">

<script>
    window.replacementList = {!! json_encode($linhkien) !!};
    window.exportReportRoute = '{{ route('xuatbaocao') }}';
    window.reportRoute = '{{ route('baocao') }}';
    window.reportParams = @json(request()->all());
</script>
<script src="{{ asset('js/report/validation.js') }}"></script>
<script src="{{ asset('js/report/ui.js') }}"></script>
<script src="{{ asset('js/report/replacement_suggest.js') }}"></script>
<script src="{{ asset('js/report/export.js') }}"></script>
<script src="{{ asset('js/report/reset.js') }}"></script>
<script src="{{ asset('js/report/index.js') }}"></script>
@endsection