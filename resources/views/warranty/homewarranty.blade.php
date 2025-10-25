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
                                <option value="KUCHEN VINH" {{ request('chinhanh') == 'KUCHEN VINH' ? 'selected' : '' }}>
                                    KUCHEN VINH
                                </option>
                                <option value="KUCHEN HÀ NỘI" {{ request('chinhanh') == 'KUCHEN HÀ NỘI' ? 'selected' : '' }}>
                                    KUCHEN HÀ
                                    NỘI</option>
                                <option value="KUCHEN HCM" {{ request('chinhanh') == 'Kuchen HCM' ? 'selected' : '' }}>KUCHEN
                                    HCM
                                </option>
                            @else
                                <option value="HUROM VINH" {{ request('chinhanh') == 'HUROM VINH' ? 'selected' : '' }}>HUROM
                                    VINH
                                </option>
                                <option value="HUROM HÀ NỘI" {{ request('chinhanh') == 'HUROM HÀ NỘI' ? 'selected' : '' }}>
                                    HUROM HÀ
                                    NỘI</option>
                                <option value="HUROM HCM" {{ request('chinhanh') == 'HUROM HCM' ? 'selected' : '' }}>HUROM
                                    HCM
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
                <!-- Thêm tìm kiếm sản phẩm -->
                <div class="col-md-4 mb-1 position-relative">
                    <input type="text" id="product_name" name="product_name" class="form-control"
                        placeholder="Nhập tên sản phẩm" value="{{ request('product_name') }}">

                    <div id="product-suggestions" class="list-group position-absolute w-100 d-none"
                        style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="sdt" name="sdt" class="form-control"
                        placeholder="Nhập số điện thoại" value="{{ request('sdt') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="khachhang" name="khachhang" class="form-control"
                        placeholder="Nhập tên khách hàng" value="{{ request('khachhang') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="kythuatvien" name="kythuatvien" class="form-control"
                        placeholder="Nhập tên kỹ thuật viên" value="{{ request('kythuatvien') }}">
                </div>
                <!--Tìm kiếm theo khoảng thời gian tiếp nhận-->
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
                    <button class="btn btn-primary w-100">Tìm kiếm</button>
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
                e.preventDefault();
                let tab = localStorage.getItem('activeTab') || 'danhsach';
                let formData = $(this).serialize();
                loadTabData(tab, formData);
            });
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
