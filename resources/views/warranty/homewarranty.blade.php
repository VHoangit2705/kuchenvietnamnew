@extends('layout.layout')

@section('content')
    <div class="container mt-4">
        <form id="searchForm">
            <!-- @csrf -->
            <div class="row">
                <div class="col-md-4 mb-1">
                    @if (session('position') != 'Kỹ thuật viên')
                        <select class="form-select" name="chinhanh">
                            <option value="">Tất cả chi nhánh</option>
                            @if (session('brand') == 'kuchen')
                                <option value="KUCHEN VINH" {{ request('chinhanh') == 'KUCHEN VINH' ? 'selected' : '' }}>KUCHEN VINH
                                </option>
                                <option value="KUCHEN HÀ NỘI" {{ request('chinhanh') == 'KUCHEN HÀ NỘI' ? 'selected' : '' }}>KUCHEN HÀ
                                    NỘI</option>
                                <option value="KUCHEN HCM" {{ request('chinhanh') == 'Kuchen HCM' ? 'selected' : '' }}>KUCHEN HCM
                                </option>
                            @else
                                <option value="HUROM VINH" {{ request('chinhanh') == 'HUROM VINH' ? 'selected' : '' }}>HUROM VINH
                                </option>
                                <option value="HUROM HÀ NỘI" {{ request('chinhanh') == 'HUROM HÀ NỘI' ? 'selected' : '' }}>HUROM HÀ
                                    NỘI</option>
                                <option value="HUROM HCM" {{ request('chinhanh') == 'HUROM HCM' ? 'selected' : '' }}>HUROM HCM
                                </option>
                            @endif
                        </select>
                    @else
                        <select class="form-select" name="branch_display" disabled>
                            <option selected>{{ $userBranch }}</option>
                        </select>
                        <input type="hidden" name="chinhanh" value="{{ $userBranch }}">
                    @endif
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="sophieu" name="sophieu" class="form-control" placeholder="Nhập số phiếu"
                        value="{{ request('sophieu') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="seri" name="seri" class="form-control" placeholder="Nhập số seri"
                        value="{{ request('seri') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="sdt" name="sdt" class="form-control" placeholder="Nhập số điện thoại"
                        value="{{ request('sdt') }}">
                </div>
                <div class="col-md-4 mb-1 position-relative">
                    <input type="text" id="product_name" name="product_name" class="form-control"
                        placeholder="Nhập tên sản phẩm" value="">
                    <div id="product-suggestions" class="list-group position-absolute w-100 d-none"
                        style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="khachhang" name="khachhang" class="form-control"
                        placeholder="Nhập tên khách hàng" value="{{ request('khachhang') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="kythuatvien" name="kythuatvien" class="form-control"
                        placeholder="Nhập tên kỹ thuật viên" value="{{ request('kythuatvien') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <div class="d-flex align-items-center flex-grow-1">
                        <input type="date" id="fromDate" name="fromDate" class="form-control"
                            value="{{ $fromDate->toDateString() }}">
                        <label for="toDate" class="mb-0 me-2 ms-1">đến</label>
                        <input type="date" id="toDate" name="toDate" class="form-control"
                            value="{{ $toDate->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-4 mb-1">
                    <button id="btnSearch" class="btn btn-primary w-100">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>
    <div class="container-fluid mt-3">
        <div class="d-flex" style="overflow-x: auto; white-space: nowrap;">
            @include('components.tabheader', ['counts' => $counts, 'activeTab' => $tab ?? ''])
        </div>
        <!-- Nội dung tab -->
        <div id="tabContent">
            @include('components.tabcontent')
        </div>
        @include('components.status_modal')
    </div>

    <script>

        // 1. Cờ theo dõi trạng thái lỗi của form
        let validationErrors = {};

        // 2. Hàm hiển thị lỗi
        function showError($field, message) {
            let fieldId = $field.attr('id');
            if (!fieldId) return;

            hideError($field); // Xóa lỗi cũ trước khi hiển thị lỗi mới

            // Thêm class is-invalid của Bootstrap và hiển thị thông báo
            $field.addClass('is-invalid');
            $field.closest('.col-md-4').append(`<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`);

            validationErrors[fieldId] = true; // Gắn cờ lỗi
            updateButtonState();
        }

        // 3. Hàm ẩn lỗi
        function hideError($field) {
            let fieldId = $field.attr('id');
            if (!fieldId) return;

            $field.removeClass('is-invalid');
            $field.closest('.col-md-4').find(`.invalid-feedback[data-error-for="${fieldId}"]`).remove();

            delete validationErrors[fieldId]; // Bỏ cờ lỗi
            updateButtonState();
        }

        // 4. Hàm cập nhật trạng thái nút "Tìm kiếm"
        function updateButtonState() {
            let hasErrors = Object.keys(validationErrors).length > 0;
            $('#btnSearch').prop('disabled', hasErrors);
        }

        // 5. Các hàm validation cho từng trường
        function validateSophieu() {
            const $input = $('#sophieu');
            const value = $input.val();
            hideError($input);
            if (value && !/^\d+$/.test(value)) {
                showError($input, "Số phiếu chỉ được nhập số.");
            } else if (value.length > 10) {
                showError($input, "Số phiếu không vượt quá 10 ký tự.");
            }
        }

        // Hàm validation cho số seri
        function validateSeri() {
            const $input = $('#seri');
            const value = $input.val();
            hideError($input);
            if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
                showError($input, "Seri chỉ nhập chữ và số.");
            } else if (value.length > 25) {
                showError($input, "Số seri không vượt quá 25 ký tự.");
            }
        }

        // Hàm validation cho tên sản phẩm
        function validateProductName() {
            const $input = $('#product_name');
            const value = $input.val().trim();
            hideError($input);
            const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹü\-\(\,+;/)]+$/;
            
            if (value && !validRegex.test(value)) {
                showError($input, "Tên sản phẩm chỉ nhập chữ và số.");
            } 
            else if (value.length > 100) {
                showError($input, "Tên sản phẩm không vượt quá 100 ký tự.");
            }
        }

        function validateSdt() {
            const $input = $('#sdt');
            const value = $input.val();
            hideError($input);
            if (value && !/^0\d{9}$/.test(value)) {
                showError($input, "SĐT phải bắt đầu bằng 0 và có đúng 10 chữ số.");
            }
        }

        function validateKhachhang() {
            const $input = $('#khachhang');
            const value = $input.val().trim();
            const nameRegex = /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;
            hideError($input);
            if (value && !nameRegex.test(value)) {
                showError($input, "Tên khách hàng chỉ nhập chữ.");
            } else if (value.length > 80) {
                showError($input, "Tên khách hàng không vượt quá 80 ký tự.");
            }
        }

        function validateKythuatvien() {
            const $input = $('#kythuatvien');
            const value = $input.val().trim();
            const nameRegex = /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;
            hideError($input);
            if (value && !nameRegex.test(value)) {
                showError($input, "Tên kỹ thuật viên chỉ nhập chữ.");
            } else if (value.length > 80) {
                showError($input, "Tên kỹ thuật viên không vượt quá 80 ký tự.");
            }
        }


        $(document).ready(function() {
            window.loadTabData = function(tab, formData) {
                let url = "{{ route('warranty.kuchen') }}?tab=" + tab + "&" + formData;
                let brand = "{{ session('brand') }}";
                if (brand === 'hurom') {
                    url = "{{ route('warranty.hurom') }}?tab=" + tab + "&" + formData;
                }
                $.get(url, function(response) {
                    if (typeof response === 'object' && response.tab && response.table) {
                        $('#warrantyTabs').html(response.tab);
                        $('#tabContent').html(response.table);

                        // Highlight tab active
                        $('#warrantyTabs .nav-link').removeClass('active');
                        $('#warrantyTabs .nav-link[data-tab="' + tab + '"]').addClass('active');

                        localStorage.setItem('activeTab', tab);
                    }
                });
            }

            // Xử lý click tab
            $('#warrantyTabs').on('click', '.nav-link', function(e) {
                e.preventDefault();
                let tab = $(this).data('tab');
                let formData = $('#searchForm').serialize();
                loadTabData(tab, formData);
            });
            
        // Xử lý form search
        $('#searchForm').on('submit', function(e) {
            e.preventDefault(); // Dừng form lại

            // Chạy tất cả các hàm validation một lần cuối
            runAllValidations();

            // Kiểm tra cờ lỗi tổng thể
            if (Object.keys(validationErrors).length > 0) {
                $('.is-invalid').first().focus(); // Focus vào ô lỗi đầu tiên
                return false; // Dừng lại nếu ngày tháng bị lỗi
            }

            // Nếu không có lỗi, tiếp tục chạy
            let tab = localStorage.getItem('activeTab') || 'danhsach';
            let formData = $(this).serialize();
            loadTabData(tab, formData);

            // Xóa nội dung của ô nhập tên sản phẩm sau khi tìm kiếm
            $('#product_name').val('');

            //Ẩn gợi ý
            $('#product-suggestions').addClass('d-none');
        });

            // Gắn sự kiện validation cho các trường
            $('#sophieu').on('input', validateSophieu);
            $('#seri').on('input', validateSeri);
            $('#product_name').on('input', validateProductName);
            $('#sdt').on('input', validateSdt);
            $('#khachhang').on('input', validateKhachhang);
            $('#kythuatvien').on('input', validateKythuatvien);
            $('#fromDate, #toDate').on('change', validateDates);

            // Hàm chạy tất cả validation
            function runAllValidations() {
                validateSophieu();
                validateSeri();
                validateProductName();
                validateSdt();
                validateKhachhang();
                validateKythuatvien();
                validateDates();
            }

            // Hàm xử lý ngày sau phải lớn hơn ngày trước
            function validateDates() {
                const $fromDate = $('#fromDate');
                const $toDate = $('#toDate');
                const fromDate = $fromDate.val();
                const toDate = $toDate.val();
                const today = new Date().toISOString().split('T')[0];

                hideError($fromDate);
                hideError($toDate);

                if (fromDate && toDate) {
                    if (fromDate > toDate) {
                        showError($toDate, "'Đến ngày' phải lớn hơn hoặc bằng 'Từ ngày'.");
                        return false;
                    }
                    if (toDate > today) {
                        showError($toDate, "'Đến ngày' không được lớn hơn ngày hiện tại.");
                        return false;
                    }
                }
                return true;
            }
        });

        $(document).ready(function() {
            let urlParams = new URLSearchParams(window.location.search);
            let tabFromUrl = urlParams.get('tab') || 'danhsach';
            let formData = '';
            if (urlParams.get('kythuatvien')) {
                formData = 'kythuatvien=' + encodeURIComponent(urlParams.get('kythuatvien'));
                $('#kythuatvien').val('');
            } else if ($('#searchForm').length) {
                formData = $('#searchForm').serialize();
            }
            let page = urlParams.get('page');
            if (page) {
                formData += (formData ? '&' : '') + 'page=' + page;
            }
            // Load đúng tab
            loadTabData(tabFromUrl, formData);
            localStorage.setItem('activeTab', tabFromUrl);
        });
    </script>
    <script>
        $(document).ready(function() {

            // 1. Lấy danh sách sản phẩm từ Controller
            const productList = {!! json_encode($products ?? []) !!};

            // 2. Lắng nghe sự kiện gõ phím
            $('#product_name').on('input', function() {
                const keyword = $(this).val().toLowerCase().trim();
                const $suggestionsBox = $('#product-suggestions');
                $suggestionsBox.empty(); // Xóa gợi ý cũ

                if (!keyword) {
                    $suggestionsBox.addClass('d-none'); // Ẩn nếu không có chữ
                    return;
                }

                // 3. Lọc sản phẩm
                const matchedProducts = productList.filter(p =>
                    p.product_name.toLowerCase().includes(keyword)
                );

                // 4. Hiển thị gợi ý
                if (matchedProducts.length > 0) {
                    matchedProducts.slice(0, 10).forEach(p => {
                        // Thêm class 'list-group-item list-group-item-action' để giống style bootstrap
                        $suggestionsBox.append(
                            `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                        );
                    });
                    $suggestionsBox.removeClass('d-none'); // Hiển thị box
                } else {
                    $suggestionsBox.addClass('d-none'); // Ẩn nếu không khớp
                }
            });

            // 5. Xử lý khi click vào một gợi ý
            $(document).on('mousedown', '#product-suggestions button', function(e) {
                e.preventDefault(); // Ngăn input bị mất focus
                $('#product_name').val($(this).text()); // Điền tên SP vào ô input
                $('#product-suggestions').addClass('d-none'); // Ẩn box
            });

            // 6. Ẩn gợi ý khi click ra ngoài
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#product_name, #product-suggestions').length) {
                    $('#product-suggestions').addClass('d-none');
                }
            });
        });
    </script>
@endsection