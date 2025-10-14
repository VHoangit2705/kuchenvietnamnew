@extends('layout.layout')

@section('content')
<div class="container mt-4">
    <form id="searchForm">
        <!-- @csrf -->
        <div class="row">
            <div class="col-md-4 mb-1">
                <input type="text" id="madon" name="madon" class="form-control" placeholder="Mã đơn hàng" value="{{ request('madon') }}">
            </div>
            <div class="col-md-4 mb-1">
                <input type="text" id="sanpham" name="sanpham" class="form-control" placeholder="Sản phẩm" value="{{ request('sanpham') }}">
            </div>
            <div class="col-md-4 mb-1">
                <div class="d-flex align-items-center flex-grow-1">
                    <input type="date" id="tungay" name="tungay" class="form-control" value="{{ request('tungay') }}">
                    <label for="toDate" class="mb-0 me-2 ms-1">đến</label>
                    <input type="date" id="denngay" name="denngay" class="form-control" value="{{ request('denngay') }}">
                </div>
            </div>
            <div class="col-md-4 mb-1">
                <select id="trangthai" name="trangthai" class="form-control">
                    <option value="">Trạng thái điều phối</option>
                    <option value="0">Chưa điều phối</option>
                    <option value="1">Đã điều phối</option>
                    <option value="2">Đã hoàn thành</option>
                </select>
            </div>
            <div class="col-lg-4 mb-1">
                <select id="phanloai" name="phanloai" class="form-control">
                    <option value="">Phân loại lắp đặt</option>
                    <option value="collaborator">Cộng tác viên lắp đặt</option>
                    <option value="agency">Đại lý lắp đặt</option>
                </select>
            </div>
            <div class="col-lg-4 mb-1 d-flex gap-2">
                <button class="btn btn-primary flex-fill">Tìm kiếm</button>
                <a href="#" id="reportCollaboratorInstall" class="btn btn-success flex-fill">Thống kê</a>
            </div>
        </div>
    </form>
</div>
<div class="container-fluid mt-3">
    <div class="d-flex" style="overflow-x: auto; white-space: nowrap;">
        @include('collaboratorinstall.tableheader', ['counts' => $counts, 'activeTab' => $tab ?? ''])
    </div>
    <!-- Nội dung tab -->
    <div id="tabContent">
        @include('collaboratorinstall.tablecontent')
    </div>
</div>

<script>
    $(document).ready(function() {
        window.loadTabData = function(tab, formData) {
            let url = "{{ route('dieuphoi.index') }}?tab=" + tab + "&" + formData;
            $.get(url, function(response) {
                if (typeof response === 'object' && response.tab && response.table) {
                    $('#collaborator_tab').html(response.tab);
                    $('#tabContent').html(response.table);

                    // Highlight tab active
                    $('#collaborator_tab .nav-link').removeClass('active');
                    $('#collaborator_tab .nav-link[data-tab="' + tab + '"]').addClass('active');

                    localStorage.setItem('activeTab', tab);
                }
            });
        }

        // Xử lý click tab
        $('#collaborator_tab').on('click', '.nav-link', function(e) {
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
        
        Report();
    });
    
    function Report() {
        $('#reportCollaboratorInstall').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Đang xuất file...',
                text: 'Vui lòng chờ trong giây lát',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            const queryParams = new URLSearchParams({
                start_date: $('#tungay').val(),
                end_date: $('#denngay').val()
            });
            fetch(`{{ route('collaborator.export') }}?${queryParams.toString()}`)
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
                            link.download = "KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT.xlsx";
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    hasError = true;
                    Swal.fire({
                        icon: 'error',
                        text: 'Lỗi server.'
                    });
                })
        });
    }
</script>
@endsection