/**
 * Layout JavaScript functions
 * Các hàm JavaScript được sử dụng trong layout chính
 */

/**
 * Điều hướng về trang trước hoặc trang mặc định
 * @param {string} defaultUrl - URL mặc định nếu không có referrer
 */
function goBackOrReload(defaultUrl) {
    if (document.referrer) {
        if (defaultUrl) {
            window.location.href = defaultUrl;
        } else {
            window.location.href = document.referrer;
        }
    } else if (defaultUrl) {
        window.location.href = defaultUrl;
    }
}

/**
 * Hiển thị loading overlay
 */
function OpenWaitBox() {
    $('#loadingOverlay').removeClass('d-none');
}

/**
 * Ẩn loading overlay
 */
function CloseWaitBox() {
    $('#loadingOverlay').addClass('d-none');
}

/**
 * Hiển thị thông báo với Swal (legacy function)
 * @param {string} icon - Icon type
 * @param {string} title - Tiêu đề
 * @param {number} timeout - Thời gian tự đóng (ms)
 * @param {boolean} confirm - Hiển thị nút xác nhận
 */
function Notification(icon, title, timeout, confirm) {
    Swal.fire({
        icon: icon,
        title: title,
        timer: timeout,
        showConfirmButton: confirm
    });
}

/**
 * Cập nhật query string parameter trong URL
 * @param {string} uri - URL hiện tại
 * @param {string} key - Key của parameter
 * @param {string} value - Giá trị mới
 * @returns {string} URL đã được cập nhật
 */
function updateQueryStringParameter(uri, key, value) {
    let re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    let separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + encodeURIComponent(value) + '$2');
    } else {
        return uri + separator + key + "=" + encodeURIComponent(value);
    }
}

/**
 * Setup AJAX configuration và error handler
 */
function setupAjaxConfig() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    // Bắt lỗi AJAX toàn cục
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 401) {
            const loginUrl = window.loginRoute || '/login';
            if (typeof showSwalMessage === 'function') {
                showSwalMessage('warning', 'Thông báo', xhr.responseJSON?.message || 'Phiên đăng nhập đã hết hạn.').then(() => {
                    window.location.href = loginUrl;
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Thông báo',
                    text: xhr.responseJSON?.message || 'Phiên đăng nhập đã hết hạn.',
                }).then(() => {
                    window.location.href = loginUrl;
                });
            }
        }
    });
}

/**
 * Hiển thị thông báo cảnh báo từ server
 * @param {Object} config - Cấu hình
 * @param {string} config.url - URL để lấy thông báo
 * @param {string} config.brand - Brand hiện tại
 * @param {string} config.kuchenRoute - Route cho brand kuchen
 * @param {string} config.huromRoute - Route cho brand hurom
 * @param {number} config.timeLimit - Thời gian giới hạn giữa các lần hiển thị (ms, default: 4 hours)
 */
function ThongBao(config = {}) {
    const {
        url = window.thongBaoRoute || '',
        brand = window.userBrand || null,
        kuchenRoute = window.warrantyKuchenRoute || '',
        huromRoute = window.warrantyHuromRoute || '',
        timeLimit = 4 * 60 * 60 * 1000 // 4 giờ mặc định
    } = config;

    if (!brand || !url) {
        return;
    }

    let lastTime = localStorage.getItem('lastThongBaoTime');
    let now = Date.now();

    if (lastTime && (now - lastTime) < timeLimit) {
        return;
    }

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const swalConfig = {
                    icon: "warning",
                    title: 'Cảnh Báo!',
                    text: response.message,
                    showCancelButton: true,
                    confirmButtonText: 'Xem ngay',
                    cancelButtonText: 'Xác nhận',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                };

                if (typeof showSwalMessage === 'function') {
                    // Sử dụng showSwalMessage nếu có
                    Swal.fire(swalConfig).then((result) => {
                        if (result.isConfirmed) {
                            handleThongBaoConfirm(brand, kuchenRoute, huromRoute, response.nhanvien);
                        }
                    });
                } else {
                    Swal.fire(swalConfig).then((result) => {
                        if (result.isConfirmed) {
                            handleThongBaoConfirm(brand, kuchenRoute, huromRoute, response.nhanvien);
                        }
                    });
                }
                
                localStorage.setItem('lastThongBaoTime', now);
            }
        },
        error: function(xhr, status, error) {
            console.error("Đã xảy ra lỗi:", error);
        }
    });
}

/**
 * Xử lý khi người dùng click "Xem ngay" trong thông báo
 * @param {string} brand - Brand hiện tại
 * @param {string} kuchenRoute - Route cho brand kuchen
 * @param {string} huromRoute - Route cho brand hurom
 * @param {string} nhanvien - Tên nhân viên
 */
function handleThongBaoConfirm(brand, kuchenRoute, huromRoute, nhanvien) {
    const tab = 'quahan';
    let baseUrl = kuchenRoute;
    if (brand === 'hurom') {
        baseUrl = huromRoute;
    }
    window.location.href = baseUrl + "?tab=" + tab + "&kythuatvien=" + encodeURIComponent(nhanvien || '');
}

/**
 * Setup keep-alive để giữ session sống
 * @param {Object} options - Tùy chọn
 * @param {string} options.url - URL để gửi keep-alive request (default: '/keep-alive')
 * @param {number} options.interval - Khoảng thời gian giữa các request (ms, default: 5 phút)
 */
function setupKeepAlive(options = {}) {
    const {
        url = '/keep-alive',
        interval = 5 * 60 * 1000 // 5 phút mặc định
    } = options;

    setInterval(() => {
        if (document.visibilityState === 'visible') {
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).catch(error => {
                console.warn('Keep-alive request failed:', error);
            });
        }
    }, interval);
}

/**
 * Initialize layout functions
 * @param {Object} config - Cấu hình
 */
function initLayout(config = {}) {
    // Setup AJAX
    if (typeof jQuery !== 'undefined') {
        setupAjaxConfig();
        
        // Setup thông báo
        if (config.thongBao) {
            ThongBao(config.thongBao);
        }
    }
    
    // Setup keep-alive
    if (config.keepAlive !== false) {
        setupKeepAlive(config.keepAliveConfig);
    }
}

