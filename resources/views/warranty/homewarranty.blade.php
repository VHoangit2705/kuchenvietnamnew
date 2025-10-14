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
                <div class="col-md-4 mb-1">
                    <input type="text" id="khachhang" name="khachhang" class="form-control"
                        placeholder="Nhập tên khách hàng" value="{{ request('khachhang') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="kythuatvien" name="kythuatvien" class="form-control"
                        placeholder="Nhập tên kỹ thuật viên" value="{{ request('kythuatvien') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 d-flex align-items-end">
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
        $(document).ready(function () {
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
            $('#warrantyTabs').on('click', '.nav-link', function (e) {
                e.preventDefault();
                let tab = $(this).data('tab');
                let formData = $('#searchForm').serialize();
                loadTabData(tab, formData);
            });

            // Xử lý form search
            $('#searchForm').on('submit', function (e) {
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
@endsection