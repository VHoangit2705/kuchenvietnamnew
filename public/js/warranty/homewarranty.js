// JS cho homewarranty.blade.php
// Cấu hình được inject từ Blade: window.HOME_WARRANTY_CONFIG

let validationErrors = {};

// Hiển thị lỗi input
function showError($field, message) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;

    hideError($field);
    $field.addClass('is-invalid');
    $field.closest('.col-md-4').append(
        `<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`
    );

    validationErrors[fieldId] = true;
    updateButtonState();
}

// Ẩn lỗi input
function hideError($field) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;

    $field.removeClass('is-invalid');
    $field.closest('.col-md-4')
        .find(`.invalid-feedback[data-error-for="${fieldId}"]`)
        .remove();

    delete validationErrors[fieldId];
    updateButtonState();
}

function updateButtonState() {
    let hasErrors = Object.keys(validationErrors).length > 0;
    $('#btnSearch').prop('disabled', hasErrors);
}

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

function validateProductName() {
    const $input = $('#product_name');
    const value = $input.val().trim();
    hideError($input);
    const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹü\-\(\,+;/)]+$/;

    if (value && !validRegex.test(value)) {
        showError($input, "Tên sản phẩm chỉ nhập chữ và số.");
    } else if (value.length > 100) {
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

// global để dùng trong onclick trong Blade
function resetFilters() {
    const config = window.HOME_WARRANTY_CONFIG || {};
    $('#searchForm')[0].reset();
    $('#fromDate').val(config.defaultFromDate || '');
    $('#toDate').val(config.defaultToDate || '');
    $('#product_name').val('');
    $('#product-suggestions').addClass('d-none');
    let tab = localStorage.getItem('activeTab') || 'danhsach';
    loadTabData(tab, '');
}

function loadTabData(tab, formData) {
    const config = window.HOME_WARRANTY_CONFIG || {};
    const brand = (config.brand || '').toLowerCase();
    let baseUrl = config.routes?.listKuchen || '';
    if (brand === 'hurom') {
        baseUrl = config.routes?.listHurom || '';
    }
    if (!baseUrl) return;

    let url = baseUrl + '?tab=' + encodeURIComponent(tab);
    if (formData) {
        url += '&' + formData;
    }

    $.get(url, function (response) {
        if (typeof response === 'object' && response.tab && response.table) {
            $('#warrantyTabs').html(response.tab);
            $('#tabContent').html(response.table);

            $('#warrantyTabs .nav-link').removeClass('active');
            $('#warrantyTabs .nav-link[data-tab="' + tab + '"]').addClass('active');

            localStorage.setItem('activeTab', tab);
        }
    });
}

// Anomaly alerts (chỉ dùng nếu được bật)
function loadAnomalyAlerts() {
    const config = window.HOME_WARRANTY_CONFIG || {};
    if (!config.anomalyEnabled || !config.routes?.anomalyAlerts) return;

    const date = $('#modalFilterDate').val();
    const branch = $('#modalFilterBranch').val();
    const resolved = $('#modalFilterResolved').val();

    $.ajax({
        url: config.routes.anomalyAlerts,
        method: 'GET',
        data: {
            date: date,
            branch: branch,
            resolved: resolved !== '' ? resolved : null
        },
        success: function (response) {
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
        error: function () {
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
    const config = window.HOME_WARRANTY_CONFIG || {};
    if (!config.routes?.anomalyResolve) return;

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
                url: config.routes.anomalyResolve.replace(':id', id),
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
                        loadAnomalyAlerts();
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

function unblockStaff(alertId) {
    const config = window.HOME_WARRANTY_CONFIG || {};
    if (!config.routes?.anomalyUnblock) return;

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
                url: config.routes.anomalyUnblock.replace(':id', alertId),
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
                        loadAnomalyAlerts();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: response.message
                        });
                    }
                },
                error: function (xhr) {
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
    const config = window.HOME_WARRANTY_CONFIG || {};
    if (!config.routes?.anomalyDelete) return;

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
                url: config.routes.anomalyDelete.replace(':id', alertId),
                method: 'DELETE',
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
                        loadAnomalyAlerts();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: response.message
                        });
                    }
                },
                error: function (xhr) {
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

$(document).ready(function () {
    const config = window.HOME_WARRANTY_CONFIG || {};

    // Gắn validation
    $('#sophieu').on('input', validateSophieu);
    $('#seri').on('input', validateSeri);
    $('#product_name').on('input', validateProductName);
    $('#sdt').on('input', validateSdt);
    $('#khachhang').on('input', validateKhachhang);
    $('#kythuatvien').on('input', validateKythuatvien);
    $('#fromDate, #toDate').on('change', validateDates);

    // Click tab
    $('#warrantyTabs').on('click', '.nav-link', function (e) {
        e.preventDefault();
        let tab = $(this).data('tab');
        let formData = $('#searchForm').serialize();
        loadTabData(tab, formData);
    });

    // Submit search
    $('#searchForm').on('submit', function (e) {
        e.preventDefault();

        validateSophieu();
        validateSeri();
        validateProductName();
        validateSdt();
        validateKhachhang();
        validateKythuatvien();
        validateDates();

        if (Object.keys(validationErrors).length > 0) {
            $('.is-invalid').first().focus();
            return false;
        }

        let tab = localStorage.getItem('activeTab') || 'danhsach';
        let formData = $(this).serialize();
        loadTabData(tab, formData);

        $('#product_name').val('');
        $('#product-suggestions').addClass('d-none');
    });

    // Product suggestions
    const productList = config.products || [];
    $('#product_name').on('input', function () {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#product-suggestions');
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        const matchedProducts = productList.filter(p =>
            (p.product_name || '').toLowerCase().includes(keyword)
        );

        if (matchedProducts.length > 0) {
            matchedProducts.slice(0, 10).forEach(p => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    $(document).on('mousedown', '#product-suggestions button', function (e) {
        e.preventDefault();
        $('#product_name').val($(this).text());
        $('#product-suggestions').addClass('d-none');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#product_name, #product-suggestions').length) {
            $('#product-suggestions').addClass('d-none');
        }
    });

    // Khởi tạo tab lần đầu theo URL
    (function initTabsFromUrl() {
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
        loadTabData(tabFromUrl, formData);
        localStorage.setItem('activeTab', tabFromUrl);
    })();

    // Anomaly filters & modal
    if (config.anomalyEnabled) {
        $('#modalFilterDate, #modalFilterBranch, #modalFilterResolved').on('change', function () {
            loadAnomalyAlerts();
        });

        $('#anomalyAlertsModal').on('shown.bs.modal', function () {
            loadAnomalyAlerts();
        });
    }
});

