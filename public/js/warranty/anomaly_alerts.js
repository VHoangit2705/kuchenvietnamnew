// Cấu hình được inject từ Blade: window.ANOMALY_ALERTS_CONFIG
// {
//   fetchUrl: '...',
//   resolveUrlBase: '...',
//   csrfToken: '...'
// }

$(document).ready(function () {
    if (!window.ANOMALY_ALERTS_CONFIG) {
        console.error('ANOMALY_ALERTS_CONFIG is not defined');
        return;
    }

    loadAlerts();

    $('#filterDate, #filterBranch, #filterResolved').on('change', function () {
        loadAlerts();
    });
});

function loadAlerts() {
    const config = window.ANOMALY_ALERTS_CONFIG || {};

    const date = $('#filterDate').val();
    const branch = $('#filterBranch').val();
    const resolved = $('#filterResolved').val();

    $.ajax({
        url: config.fetchUrl,
        method: 'GET',
        data: {
            date: date,
            branch: branch,
            resolved: resolved !== '' ? resolved : null
        },
        success: function (response) {
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
        error: function () {
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

    if (!alerts || alerts.length === 0) {
        tbody.append('<tr><td colspan="11" class="text-center">Không có cảnh báo nào.</td></tr>');
        return;
    }

    alerts.forEach(function (alert) {
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
    const config = window.ANOMALY_ALERTS_CONFIG || {};

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
                url: `${config.resolveUrlBase}/${id}/resolve`,
                method: 'POST',
                data: {
                    _token: config.csrfToken
                },
                success: function (response) {
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
                error: function () {
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

