@extends('layout.layout')

@section('content')
<form method="GET" action="{{ route('baocao') }}">
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

<div class="container-fluid d-flex flex-column justify-content-start mt-3">
    <div class="table-container" style="overflow-x: auto;">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr class="text-center">
                    <th class="align-middle">STT</th>
                    <th class="align-middle" style="max-width: 70px;">Mã serial</th>
                    <th class="align-middle" style="min-width: 200px;">Tên sản phẩm</th>
                    <th class="align-middle" style="min-width: 150px;">Chi nhánh</th>
                    <th class="align-middle" style="min-width: 100px;">Khách hàng</th>
                    <th class="align-middle" style="min-width: 80px;">Điện thoại</th>
                    <th class="align-middle" style="min-width: 100px;">Kỹ thuật viên</th>
                    <th class="align-middle" style="min-width: 120px;">Ngày tiếp nhận</th>
                    <th class="align-middle" style="min-width: 120px;">Lỗi ban đầu</th>
                    <th class="align-middle" style="min-width: 120px;">Ngày xuất kho</th>
                    <th class="align-middle" style="min-width: 100px;">Bảo hành</th>
                    <th class="align-middle" style="min-width: 100px;">Linh kiện</th>
                    <th class="align-middle" style="min-width: 80px;">Đơn giá</th>
                    <th class="align-middle" style="min-width: 60px;">SL</th>
                    <th class="align-middle" style="min-width: 100px;">Thành tiền</th>
                    <!--<th class="align-middle" style="min-width: 120px;">KH thanh toán</th>-->
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $item)
                @php
                $warrantyEnd = \Carbon\Carbon::parse($item->warranty_end);
                $receivedDate = \Carbon\Carbon::parse($item->received_date);
                $isInWarranty = $warrantyEnd->gte($receivedDate);
                @endphp
                <tr>
                    <td class="shorten-text text-center">{{ $loop->iteration }}</td>
                    <td class="shorten-text" data-bs-toggle="tooltip">
                        @if(Str::contains($item->product, ['Máy giặt', 'Máy sấy', 'Máy rửa bát', 'Tủ lạnh']) && !empty($item->serial_thanmay))
                        {{ $item->serial_thanmay }}
                        @else
                        {{ $item->serial_number }}
                        @endif
                    </td>
                    <td class="shorten-text" data-bs-toggle="tooltip" title="{{ $item->product }}">
                        <a href="{{ route('warranty.detail', ['id' => $item->id]) }}" target="_blank" class="text-decoration-none text-primary">
                            {{ \Illuminate\Support\Str::limit($item->product, 40, '...') }}
                        </a>
                    </td>
                    <td class="shorten-text">{{ $item->branch }}</td>
                    <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->full_name }}</td>
                    <td class="shorten-text text-center">{{ $item->phone_number }}</td>
                    <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->staff_received }}</td>
                    <td class="shorten-text text-center" data-bs-toggle="tooltip">
                        {{ \Carbon\Carbon::parse($item->received_date)->format('d/m/Y') }}
                    </td>
                    <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->initial_fault_condition }}</td>
                    <td class="shorten-text text-center" data-bs-toggle="tooltip">
                        {{ \Carbon\Carbon::parse($item->shipment_date)->format('d/m/Y') }}
                    </td>
                    <td class="shorten-text text-center {{ $isInWarranty ? 'text-success' : 'text-danger' }}">
                        {{ $isInWarranty ? 'Còn hạn BH' : 'Hết hạn BH' }}
                    </td>
                    <td class="shorten-text" data-bs-toggle="tooltip" title="{{$item->replacement }}">
                        {{ \Illuminate\Support\Str::limit($item->replacement, 40, '...') }}
                    </td>
                    <td class="shorten-text text-center">{{number_format($item->replacement_price, 0, ',', '.') }}</td>
                    <td class="shorten-text text-center">{{$item->quantity }}</td>
                    <td class="shorten-text text-center">
                        {{number_format($item->replacement_price * $item->quantity, 0, ',', '.')}}
                    </td>
                    <!--<td class="shorten-text text-center">{{ number_format($item->total, 0, ',', '.') }}</td>-->
                </tr>
                @empty
                <tr>
                    <td colspan="14" class="text-center">Không có dữ liệu</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<style>
    .table-container {
        padding: 0;
        overflow-y: auto;
    }

    .table-container .table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .is-invalid {
        border-color: red;
    }

    .autocomplete-suggestions {
        position: absolute;
        z-index: 100;
        background: #fff;
        border: 1px solid #ccc;
        width: calc(100% - 20px);
        max-height: 150px;
        overflow-y: auto;
        display: none;
        box-sizing: border-box;
    }
</style>

<script>
    window.replacementList = {!! json_encode($linhkien) !!};
    window.exportReportRoute = '{{ route('xuatbaocao') }}';
    window.reportParams = @json(request()->all());
</script>
<script src="{{ asset('js/report/validation.js') }}"></script>
<script src="{{ asset('js/report/ui.js') }}"></script>
<script src="{{ asset('js/report/replacement_suggest.js') }}"></script>
<script src="{{ asset('js/report/export.js') }}"></script>
<script src="{{ asset('js/report/reset.js') }}"></script>
<script src="{{ asset('js/report/index.js') }}"></script>
@endsection