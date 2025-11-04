@extends('layout.layout')

@section('content')
<form method="GET" action="{{ route('baocao') }}">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4 mb-1 position-relative">
                <input type="text" id="product" name="product" class="form-control" value="{{ request('product') }}"
                    placeholder="Nhập tên hoặc mã seri sản phẩm">
                <div id="suggestions-product-name" class="autocomplete-suggestions"></div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 mb-1 position-relative">
                <input type="text" id="replacement" name="replacement" class="form-control"
                    value="{{ request('replacement') }}" placeholder="Nhập linh kiện sản phẩm">
                <div id="suggestions-product-part" class="autocomplete-suggestions"></div>
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
                <input type="text" id="staff_received" name="staff_received" class="form-control"
                    value="{{ request('staff_received') }}" placeholder="Nhập tên kỹ thuật viên">
                <div id="suggestions-product-staff" class="autocomplete-suggestions"></div>
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
                <button type="submit" class="btnsearch btn btn-primary fw-bold me-2">
                    <img src="{{ asset('icons/filter.png') }}" alt="Filter Icon" style="width: 16px; height: 16px;">
                    Lọc
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

<script type="text/javascript">
    $(document).ready(function() {
        resizeTableContainer();
        // limitButtonClicks('btnsearch', 6);
        validateDates();
        setupAutoComplete('#product', '#suggestions-product-name', "{{ route('baocao.sanpham') }}");
        setupAutoComplete('#replacement', '#suggestions-product-part', "{{ route('baocao.linhkien') }}");
        setupAutoComplete('#staff_received', '#suggestions-product-staff', "{{ route('baocao.nhanvien') }}");
    });
    // Ẩn dữ liệu khi quá dài và hover hiện tooltip
    document.addEventListener("DOMContentLoaded", function() {
        const cells = document.querySelectorAll('.shorten-text');
        cells.forEach(cell => {
            const originalText = cell.textContent.trim();
            if (originalText.length > 50) {
                const words = originalText.split(' ');
                let shortText = '';
                let count = 0;
                for (let word of words) {
                    if ((shortText + word).length > 50) break;
                    shortText += word + ' ';
                }
                shortText = shortText.trim() + '...';
                cell.textContent = shortText;
                cell.setAttribute('title', originalText);
                cell.setAttribute('data-bs-toggle', 'tooltip');
            }
        });
        // Kích hoạt tooltip của Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });

    function resizeTableContainer() {
        const windowHeight = $(window).height();
        const containerHeight = $('.container').outerHeight(true); // bao gồm margin
        const newHeight = windowHeight - containerHeight;
        $('.table-container').height(newHeight - 10);
    }

    // Button Xuất Excel
    $('#exportExcel').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Đang xuất file...',
            text: 'Vui lòng chờ trong giây lát',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        var params = @json(request() -> all());

        fetch("{{ route('xuatbaocao') }}?" + new URLSearchParams(params))
            .then(response => {
                Swal.close();
                const contentType = response.headers.get("Content-Type");
                if (contentType.includes("application/json")) {
                    hasError = true;
                    return response.json().then(json => {
                        Swal.fire({
                            icon: 'error',
                            text: json.message
                        });
                    });
                } else {
                    return response.blob().then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = "bao_cao_bao_hanh.xlsx";
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });
                }
            })
            .catch(error => {
                hasError = true;
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    text: 'Lỗi server.'
                });
                console.error(error);
            })
    });

    // validate dữ liệu tìm kiếm
    function validateDates() {
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        const today = new Date().toISOString().split('T')[0]; // format: yyyy-mm-dd

        $('#fromDate, #toDate').removeClass('is-invalid');
        // Kiểm tra nếu nhập 1 trong 2 thì phải nhập cả 2
        if ((fromDate && !toDate) || (!fromDate && toDate)) {
            toastr.warning("Vui lòng nhập cả 'Tiếp nhận từ ngày' và 'Đến ngày'.");
            if (!fromDate) $('#fromDate').addClass('is-invalid');
            if (!toDate) $('#toDate').addClass('is-invalid');
            return false;
        }
        // Nếu có đủ cả 2 thì kiểm tra logic ngày
        if (fromDate && toDate) {
            if (fromDate > toDate) {
                toastr.warning("'Tiếp nhận từ ngày' phải nhỏ hơn hoặc bằng 'Đến ngày'.");
                $('#fromDate').addClass('is-invalid');
                return false;
            }
            if (toDate > today) {
                toastr.warning("'Đến ngày' không được lớn hơn ngày hiện tại.");
                $('#toDate').addClass('is-invalid');
                return false;
            }
        }
        return true;
    }

    //Gợi ý từ
    function setupAutoComplete(inputSelector, suggestionBoxSelector, requestUrl) {
        $(inputSelector).on('keyup', function() {
            let query = $(this).val();
            if (query.length === 0) {
                $(suggestionBoxSelector).hide();
                return;
            }
            if (query.length >= 5) {
                $.ajax({
                    url: requestUrl,
                    type: 'GET',
                    data: {
                        query: query
                    },
                    success: function(data) {
                        $(suggestionBoxSelector).empty();
                        if (data.length > 0) {
                            $(suggestionBoxSelector).show();
                            data.forEach(function(item) {
                                $(suggestionBoxSelector).append(
                                    '<div class="suggestion-item" style="padding: 8px; cursor: pointer;">' + item + '</div>'
                                );
                            });
                            // Gán lại sự kiện click cho từng item
                            $(suggestionBoxSelector + ' .suggestion-item').on('click', function() {
                                $(inputSelector).val($(this).text());
                                $(suggestionBoxSelector).hide();
                            });
                        } else {
                            $(suggestionBoxSelector).hide();
                        }
                    }
                });
            }
        });
    }

    // Ẩn gợi ý nếu click ngoài input và danh sách gợi ý
    $(document).on('click', function(event) {
        if (!$(event.target).closest('#product').length && !$(event.target).closest('#suggestions-product-name').length) {
            $('#suggestions-product-name').hide();
        }
        if (!$(event.target).closest('#replacement').length && !$(event.target).closest('#suggestions-product-part').length) {
            $('#suggestions-product-part').hide();
        }
        if (!$(event.target).closest('#staff_received').length && !$(event.target).closest('#suggestions-product-staff').length) {
            $('#suggestions-product-staff').hide();
        }
    });
</script>
@endsection