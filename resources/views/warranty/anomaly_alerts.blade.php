@extends('layout.layout')

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Cảnh báo nhân viên tiếp nhận bất thường</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="filterDate" class="form-label">Lọc theo ngày:</label>
                    <input type="date" class="form-control" id="filterDate" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label for="filterBranch" class="form-label">Lọc theo chi nhánh:</label>
                    <select class="form-select" id="filterBranch">
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
                    <label for="filterResolved" class="form-label">Trạng thái:</label>
                    <select class="form-select" id="filterResolved">
                        <option value="">Tất cả</option>
                        <option value="0">Chưa xử lý</option>
                        <option value="1">Đã xử lý</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="loadAlerts()">Tải lại</button>
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
                    <tbody id="alertsTableBody">
                        <tr>
                            <td colspan="11" class="text-center">Đang tải...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    window.ANOMALY_ALERTS_CONFIG = {
        fetchUrl: '{{ route("warranty.anomaly.alerts") }}',
        resolveUrlBase: '{{ url("/baohanh/anomaly-alerts") }}',
        csrfToken: '{{ csrf_token() }}'
    };
</script>
<script src="{{ asset('js/warranty/anomaly_alerts.js') }}"></script>
@endsection

