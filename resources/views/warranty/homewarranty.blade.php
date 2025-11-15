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
                    <div class="d-flex align-items-center">
                        <input type="date" id="fromDate" name="fromDate" class="form-control"
                            value="{{ $fromDate->toDateString() }}">
                        <label for="toDate" class="mb-0 me-2 ms-1">đến</label>
                        <input type="date" id="toDate" name="toDate" class="form-control"
                            value="{{ $toDate->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-4 mb-1">
                    <div class="d-flex gap-2 h-100">
                            <button id="btnSearch" class="btn btn-primary flex-fill" style="height: 38px;">Tìm kiếm</button>
                            <button id="btnReset" class="btn btn-secondary flex-fill" onclick="resetFilters()" style="height: 38px;">
                                Xóa bộ lọc
                            </button>
                            @if (in_array(strtolower(session('position') ?? ''), ['admin', 'quản trị viên']))
                                <button type="button" 
                                        class="btn btn-warning flex-fill" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#anomalyAlertsModal" 
                                        onclick="loadAnomalyAlerts()"
                                        style="height: 38px;">
                                    <i class="bi bi-exclamation-triangle"></i> Cảnh báo
                                </button>
                            @endif
                        </div>
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

    <!-- Modal Cảnh báo bất thường -->
    @if (in_array(strtolower(session('position') ?? ''), ['admin', 'quản trị viên']))
    <div class="modal fade" id="anomalyAlertsModal" tabindex="-1" aria-labelledby="anomalyAlertsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="anomalyAlertsModalLabel">Cảnh báo nhân viên tiếp nhận bất thường</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="modalFilterDate" class="form-label">Lọc theo ngày:</label>
                            <input type="date" class="form-control" id="modalFilterDate" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="modalFilterBranch" class="form-label">Lọc theo chi nhánh:</label>
                            <select class="form-select" id="modalFilterBranch">
                                <option value="">Tất cả</option>
                                @if (session('brand') == 'kuchen')
                                    <option value="KUCHEN VINH">KUCHEN VINH</option>
                                    <option value="KUCHEN HÀ NỘI">KUCHEN HÀ NỘI</option>
                                    <option value="KUCHEN HCM">KUCHEN HCM</option>
                                @else
                                    <option value="HUROM VINH">HUROM VINH</option>
                                    <option value="HUROM HÀ NỘI">HUROM HÀ NỘI</option>
                                    <option value="HUROM HCM">HUROM HCM</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="modalFilterResolved" class="form-label">Trạng thái:</label>
                            <select class="form-select" id="modalFilterResolved">
                                <option value="">Tất cả</option>
                                <option value="0">Chưa xử lý</option>
                                <option value="1">Đã xử lý</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="loadAnomalyAlerts()">Tải lại</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Ngày</th>
                                    <th>Chi nhánh</th>
                                    <th>Nhân viên</th>
                                    <th>Số ca nhận</th>
                                    <th>Tổng ca kho</th>
                                    <th>Số NV trong kho</th>
                                    <th>Trung bình</th>
                                    <th>Ngưỡng</th>
                                    <th>Mức độ</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="modalAlertsTableBody">
                                <tr>
                                    <td colspan="11" class="text-center">Đang tải...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    @endif

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
            const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹü\-\(\,+;/)]+$/;
            
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
            const nameRegex = /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;
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
            const nameRegex = /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;
            hideError($input);
            if (value && !nameRegex.test(value)) {
                showError($input, "Tên kỹ thuật viên chỉ nhập chữ.");
            } else if (value.length > 80) {
                showError($input, "Tên kỹ thuật viên không vượt quá 80 ký tự.");
            }
        }


        function resetFilters() {
            $('#searchForm')[0].reset();
            $('#fromDate').val('{{ $fromDate->toDateString() }}');
            $('#toDate').val('{{ $toDate->toDateString() }}');
            $('#product_name').val('');
            $('#product-suggestions').addClass('d-none');
            let tab = localStorage.getItem('activeTab') || 'danhsach';
            loadTabData(tab, '');
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

    @if (in_array(strtolower(session('position') ?? ''), ['admin', 'quản trị viên']))
    <script>
        function loadAnomalyAlerts() {
            const date = $('#modalFilterDate').val();
            const branch = $('#modalFilterBranch').val();
            const resolved = $('#modalFilterResolved').val();

            $.ajax({
                url: '{{ route("warranty.anomaly.alerts") }}',
                method: 'GET',
                data: {
                    date: date,
                    branch: branch,
                    resolved: resolved !== '' ? resolved : null
                },
                success: function(response) {
                    if (response.success) {
                        renderAnomalyAlerts(response.data);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: response.message || 'Không thể tải danh sách cảnh báo.'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Đã xảy ra lỗi khi tải danh sách cảnh báo.'
                    });
                }
            });
        }

        function renderAnomalyAlerts(alerts) {
            const tbody = $('#modalAlertsTableBody');
            tbody.empty();

            if (alerts.length === 0) {
                tbody.append('<tr><td colspan="11" class="text-center">Không có cảnh báo nào.</td></tr>');
                return;
            }

            alerts.forEach(function(alert) {
                const date = new Date(alert.date).toLocaleDateString('vi-VN');
                const alertLevelClass = {
                    'low': 'badge bg-info',
                    'medium': 'badge bg-warning',
                    'high': 'badge bg-danger'
                };
                const alertLevelText = {
                    'low': 'Thấp',
                    'medium': 'Trung bình',
                    'high': 'Cao'
                };

                const row = `
                    <tr>
                        <td>${date}</td>
                        <td>${alert.branch}</td>
                        <td><strong>${alert.staff_name}</strong></td>
                        <td><span class="badge bg-primary">${alert.staff_count}</span></td>
                        <td>${alert.total_count}</td>
                        <td>${alert.staff_count_in_branch}</td>
                        <td>${parseFloat(alert.average_count).toFixed(2)}</td>
                        <td>${parseFloat(alert.threshold).toFixed(0)}</td>
                        <td><span class="${alertLevelClass[alert.alert_level]}">${alertLevelText[alert.alert_level]}</span></td>
                        <td>
                            ${alert.is_resolved 
                                ? '<span class="badge bg-success">Đã xử lý</span>' 
                                : '<span class="badge bg-danger">Chưa xử lý</span>'}
                        </td>
                        <td>
                            ${alert.is_resolved == false || alert.is_resolved == 0
                                ? `<button class="btn btn-sm btn-success" onclick="resolveAnomalyAlert(${alert.id})">Đánh dấu đã xử lý</button>` 
                                : (alert.has_active_block == true || alert.has_active_block == 1
                                    ? `<button class="btn btn-sm btn-warning" onclick="unblockStaff(${alert.id})">
                                        <i class="bi bi-unlock me-1"></i>Gỡ block
                                       </button>`
                                    : `<button class="btn btn-sm btn-danger" onclick="deleteAnomalyAlert(${alert.id})">
                                        <i class="bi bi-trash me-1"></i>Xóa
                                       </button>`)}
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        function resolveAnomalyAlert(id) {
            Swal.fire({
                title: 'Xác nhận',
                text: 'Bạn có chắc chắn muốn đánh dấu cảnh báo này đã được xử lý?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("warranty.anomaly.resolve", ":id") }}'.replace(':id', id),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Thành công',
                                    text: response.message,
                                    timer: 1500
                                });
                                loadAnomalyAlerts();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: 'Đã xảy ra lỗi khi xử lý.'
                            });
                        }
                    });
                }
            });
        }

        function unblockStaff(alertId) {
            Swal.fire({
                title: 'Xác nhận',
                text: 'Bạn có chắc chắn muốn gỡ block cho nhân viên này?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("warranty.anomaly.unblock", ":id") }}'.replace(':id', alertId),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Thành công',
                                    text: response.message,
                                    timer: 1500
                                });
                                loadAnomalyAlerts();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: xhr.responseJSON?.message || 'Đã xảy ra lỗi khi gỡ block.'
                            });
                        }
                    });
                }
            });
        }

        function deleteAnomalyAlert(alertId) {
            Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc chắn muốn xóa cảnh báo này? Hành động này không thể hoàn tác.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("warranty.anomaly.delete", ":id") }}'.replace(':id', alertId),
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Thành công',
                                    text: response.message,
                                    timer: 1500
                                });
                                loadAnomalyAlerts();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: xhr.responseJSON?.message || 'Đã xảy ra lỗi khi xóa cảnh báo.'
                            });
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            $('#modalFilterDate, #modalFilterBranch, #modalFilterResolved').on('change', function() {
                loadAnomalyAlerts();
            });

            // Load alerts khi modal được mở
            $('#anomalyAlertsModal').on('shown.bs.modal', function() {
                loadAnomalyAlerts();
            });
        });
    </script>
    @endif
@endsection