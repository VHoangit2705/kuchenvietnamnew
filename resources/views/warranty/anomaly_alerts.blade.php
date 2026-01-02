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
    $(document).ready(function() {
        loadAlerts();
    });

    function loadAlerts() {
        const date = $('#filterDate').val();
        const branch = $('#filterBranch').val();
        const resolved = $('#filterResolved').val();

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
                    renderAlerts(response.data);
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

    function renderAlerts(alerts) {
        const tbody = $('#alertsTableBody');
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
                        ${!alert.is_resolved 
                            ? `<button class="btn btn-sm btn-success" onclick="resolveAlert(${alert.id})">Đánh dấu đã xử lý</button>` 
                            : '-'}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function resolveAlert(id) {
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
                    url: `/baohanh/anomaly-alerts/${id}/resolve`,
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
                            loadAlerts();
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

    $('#filterDate, #filterBranch, #filterResolved').on('change', function() {
        loadAlerts();
    });
</script>
@endsection

