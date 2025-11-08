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
        runAllValidations();
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
        //Ngăn xuất liên tục trong 2 phút
        const COOLDOWN_PERIOD_MS = 2 * 60 * 1000; // 2 phút
        const LAST_EXPORT_KEY = 'lastExportTimestamp_reportWarranty';
        const lastExportTime = localStorage.getItem(LAST_EXPORT_KEY);
        const currentTime = Date.now();
        if (lastExportTime) {
            const timeDiff = currentTime - parseInt(lastExportTime, 10);
            if (timeDiff < COOLDOWN_PERIOD_MS) {
                const timeLeftSeconds = Math.ceil((COOLDOWN_PERIOD_MS - timeDiff) / 1000);
                const minutes = Math.floor(timeLeftSeconds / 60);
                const seconds = timeLeftSeconds % 60;
                swal.fire({
                    icon: 'warning',
                    title: 'Vui lòng chờ',
                    text: `Bạn vừa xuất file. Vui lòng chờ ${minutes} phút ${seconds} giây trước khi xuất tiếp.`,
                });
                return; // Dừng thực thi
            }
        }
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
                if (contentType && contentType.includes("application/json")) {
                    return response.json().then(json => {
                        Swal.fire({
                            icon: 'error',
                            text: json.message
                        });
                    });
                } else {
                    return response.blob().then(blob => {
                        // Chỉ lưu timestamp khi tải file thành công
                        localStorage.setItem(LAST_EXPORT_KEY, currentTime.toString());
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

    // Gợi ý linh kiện thay thế
    $(document).ready(function() {
            const replacementList = {!! json_encode($linhkien) !!};
            $('#replacement').on('input', function() {
                const keyword = $(this).val().toLowerCase().trim();
                const $suggestionsBox = $('#replacement-suggestions');
                $suggestionsBox.empty();

                if (!keyword) {
                    $suggestionsBox.addClass('d-none');
                    return;
                }
                const matchedReplacement = replacementList.filter(p =>
                    p.product_name.toLowerCase().includes(keyword)
                );

                if (matchedReplacement.length > 0) {
                    matchedReplacement.slice(0, 10).forEach(p => {
                        $suggestionsBox.append(
                            `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                        );
                    });
                    $suggestionsBox.removeClass('d-none');
                } else {
                    $suggestionsBox.addClass('d-none');
                }
            });

            $(document).on('mousedown', '#replacement-suggestions button', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#replacement').val($(this).text());
                $('#replacement-suggestions').addClass('d-none').empty();
            });
        });


    $(document).on('click', function(e) {
    // Logic cho ô linh kiện (client-side)
    if (!$(e.target).closest('#replacement, #replacement-suggestions').length) {
        $('#replacement-suggestions').addClass('d-none').empty();
    }
});

    // Validation form
    // 1. Cờ theo dõi trạng thái lỗi của form
    let validationErrors = {};
    // 2. Hàm hiển thị lỗi
    function showError($field, message) {
        let fieldId = $field.attr('id');
        if (!fieldId) return;
        hideError($field); // Xóa lỗi cũ trước khi hiển thị lỗi mới
        // Thêm class is-invalid của Bootstrap và hiển thị thông báo
        $field.addClass('is-invalid');
        $field.closest('.col-12').append(`<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`);
        validationErrors[fieldId] = true; // Gắn cờ lỗi
        updateButtonState();
    }
    // 3. Hàm ẩn lỗi
    function hideError($field) {
        let fieldId = $field.attr('id');
        if (!fieldId) return;
        $field.removeClass('is-invalid');
        $field.closest('.col-12').find(`.invalid-feedback[data-error-for="${fieldId}"]`).remove();
        delete validationErrors[fieldId]; // Bỏ cờ lỗi
        updateButtonState();
    }
    // 4. Hàm cập nhật trạng thái nút "Lọc" VÀ "Xuất Excel"
    function updateButtonState() {
        let hasErrors = Object.keys(validationErrors).length > 0;
        $('#btnSearch').prop('disabled', hasErrors);
        $('#exportExcel').toggleClass('disabled', hasErrors);
    }
    // 5. Các hàm validation cho từng trường
    function validateProduct() {
        const $input = $('#product');
        const value = $input.val().trim();
        hideError($input);
        if (value && !/^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(\,/)]+$/.test(value)) {
            showError($input, "Chỉ được nhập chữ và số.");
        } else if (value.length > 100) {
            showError($input, "Tối đa 100 ký tự.");
        }
    }
    function validateReplacement() {
        const $input = $('#replacement');
        const value = $input.val().trim();
        hideError($input);
        // Regex cho phép chữ, số và các ký tự đặc biệt được yêu cầu
        const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ:,.;/()_+\-=%*]*$/;
        if (value && !validRegex.test(value)) {
            showError($input, "Chứa ký tự không hợp lệ.");
        } else if (value.length > 100) {
            showError($input, "Tối đa 100 ký tự.");
        }
    }
    function validateStaff() {
        const $input = $('#staff_received');
        const value = $input.val().trim();
        hideError($input);
        // Regex cho phép chữ cái (bao gồm tiếng Việt) và khoảng trắng
        const nameRegex = /^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụüÜưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ]+$/;
        if (value && !nameRegex.test(value)) {
            showError($input, "Chỉ được nhập chữ.");
        } else if (value.length > 50) {
            showError($input, "Tối đa 50 ký tự.");
        }
    }
    function validateDates() {
        const $fromDate = $('#fromDate');
        const $toDate = $('#toDate');
        const fromDate = $fromDate.val();
        const toDate = $toDate.val();
        const today = new Date().toISOString().split('T')[0];
        // Xóa lỗi cũ của cả 2 trường date trước khi kiểm tra lại
        hideError($fromDate);
        hideError($toDate);
        if (fromDate && toDate) {
            if (fromDate > toDate) {
                showError($toDate, "'Đến ngày' phải lớn hơn hoặc bằng 'Từ ngày'.");
                return false; // Có lỗi
            }
            if (toDate > today) {
                showError($toDate, "'Đến ngày' không được lớn hơn ngày hiện tại.");
                return false; // Có lỗi
            }
        }
        return true; // Không có lỗi
    }
    // 6. Hàm chạy tất cả các validation
    function runAllValidations() {
        validateProduct();
        validateReplacement();
        validateStaff();
        validateDates();
    }
    // 7. Gắn sự kiện
    $(document).ready(function() {
        // Gắn sự kiện validation cho các trường input
        $('#product').on('input', validateProduct);
        $('#replacement').on('input', validateReplacement);
        $('#staff_received').on('input', validateStaff);
        $('#fromDate, #toDate').on('change', validateDates);
        // Xử lý khi submit form
        $('form').on('submit', function(e) {
            // Chạy tất cả các hàm validation một lần cuối
            runAllValidations();
            // Kiểm tra lại cờ lỗi tổng thể
            if (Object.keys(validationErrors).length > 0) {
                e.preventDefault(); // Ngăn form submit
                // Focus vào ô lỗi đầu tiên để người dùng dễ sửa
                const firstErrorId = Object.keys(validationErrors)[0];
                $('#' + firstErrorId).focus();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi dữ liệu',
                    text: 'Vui lòng kiểm tra lại các trường nhập liệu.',
                });
            }
            // Nếu không có lỗi, form sẽ được submit bình thường
        });
    });
</script>
@endsection