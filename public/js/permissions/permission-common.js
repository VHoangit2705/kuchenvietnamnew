/**
 * Các hàm quản lý phân quyền dùng chung
 * Các tiện ích chia sẻ cho các trang liên quan đến phân quyền
 */

// Thiết lập CSRF token cho tất cả các request AJAX
function setupCSRFToken() {
    if (typeof $ !== "undefined") {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            },
        });
    }
}

// Bật/tắt hiển thị nhóm quyền
function togglePermissions(header) {
    const group = header.closest(".permission-group");
    const children = group.querySelector(".child-permissions");
    const icon = header.querySelector(".toggle-btn i");

    if (children.style.display === "none") {
        children.style.display = "block";
        icon.classList.remove("fa-plus");
        icon.classList.add("fa-minus");
    } else {
        children.style.display = "none";
        icon.classList.remove("fa-minus");
        icon.classList.add("fa-plus");
    }
}

// Bật/tắt nhóm quyền (cách triển khai thay thế sử dụng classes)
function togglePermissionsClass(iconSpan) {
    const content = iconSpan.closest(".group-header").nextElementSibling;
    const icon = iconSpan.querySelector("i");
    content.classList.toggle("open");
    icon.classList.toggle("fa-plus");
    icon.classList.toggle("fa-minus");
}

// Chọn tất cả các checkbox
function selectAllCheckboxes(selector) {
    if (typeof $ !== "undefined") {
        $(selector).prop("checked", true);
        $(".child-permissions").slideDown();
        $(".permission-group .toggle-btn i")
            .removeClass("fa-plus")
            .addClass("fa-minus");
    }
}

// Bỏ chọn tất cả các checkbox
function deselectAllCheckboxes(selector) {
    if (typeof $ !== "undefined") {
        $(selector).prop("checked", false);
    }
}

// Tự động mở các nhóm có ít nhất một checkbox được chọn
function autoOpenCheckedGroups() {
    if (typeof $ !== "undefined") {
        $(".permission-group").each(function () {
            const group = $(this);
            const checkedBoxes = group.find("input.child-checkbox:checked");
            if (checkedBoxes.length > 0) {
                const childPermissions = group.find(".child-permissions");
                childPermissions.show();
                const icon = group.find(".toggle-btn i");
                icon.removeClass("fa-plus").addClass("fa-minus");
            }
        });
    }
}

// Xử lý lỗi 419 khi CSRF token hết hạn
function handleCSRFExpiration(loginUrl) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: "warning",
            title: "Phiên làm việc đã hết hạn",
            text: "Vui lòng đăng nhập lại để tiếp tục.",
            confirmButtonText: "Đăng nhập",
        }).then(() => {
            if (!loginUrl) loginUrl = "/login";
            window.location.href = loginUrl;
        });
    } else {
        alert("Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.");
        window.location.href = loginUrl || "/login";
    }
}

// Hiển thị thông báo thành công
function showSuccessMessage(message, timer = 1500) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: "success",
            title: "Thành công!",
            text: message,
            timer: timer,
            showConfirmButton: false,
        });
    } else {
        alert(message);
    }
}

// Hiển thị thông báo lỗi
function showErrorMessage(message) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: "error",
            title: "Lỗi!",
            text: message,
            confirmButtonText: "OK",
        });
    } else {
        alert(message);
    }
}

// Kiểm tra một trường bắt buộc
function validateField(fieldSelector, errorMessage) {
    if (typeof $ === "undefined") return false;

    const $field = $(fieldSelector);
    const value = $field.val().trim();
    $field.removeClass("is-invalid");

    if (!value) {
        $field.addClass("is-invalid");
        $field.focus();
        if (errorMessage) {
            showErrorMessage(errorMessage);
        }
        return false;
    }
    return true;
}

// Kiểm tra nhiều trường bắt buộc
function validateRequiredFields(fields) {
    if (typeof $ === "undefined") return false;

    let isValid = true;
    const fieldArray = Array.isArray(fields) ? fields : Object.keys(fields);

    fieldArray.forEach(function (field) {
        const selector = typeof field === "string" ? field : field.selector;
        const removeClass = field.removeClass || "is-invalid";
        const $field = $(selector);
        const value = $field.val().trim();

        $field.removeClass(removeClass);

        if (!value) {
            $field.addClass(removeClass);
            if (isValid) {
                $field.focus();
            }
            isValid = false;
        }
    });

    return isValid;
}

// Thực hiện request AJAX với xử lý lỗi/thành công chung
function makeAjaxRequest(options) {
    if (typeof $ === "undefined") return;

    const defaults = {
        url: "",
        method: "POST",
        data: {},
        successCallback: null,
        errorCallback: null,
        loginRoute: "/login",
        successMessage: null,
        errorMessage: "Đã xảy ra lỗi",
        onSuccess: null,
        onError: null,
    };

    const config = Object.assign({}, defaults, options);

    // Thêm CSRF token nếu chưa có
    if (!config.data._token) {
        config.data._token = $('meta[name="csrf-token"]').attr("content");
    }

    $.ajax({
        url: config.url,
        method: config.method,
        data: config.data,
        dataType: "json",
        success: function (response) {
            // Kiểm tra nếu response là object và có thuộc tính success
            if (
                typeof response === "object" &&
                response !== null &&
                response.success === true
            ) {
                if (config.successMessage) {
                    showSuccessMessage(config.successMessage);
                }
                if (config.onSuccess) {
                    config.onSuccess(response);
                } else if (config.successCallback) {
                    config.successCallback(response);
                }
            } else if (
                typeof response === "object" &&
                response !== null &&
                response.success === false
            ) {
                const errorMsg = response.message || config.errorMessage;
                showErrorMessage(errorMsg);
                if (config.onError) {
                    config.onError(response);
                } else if (config.errorCallback) {
                    config.errorCallback(response);
                }
            } else {
                if (config.successMessage) {
                    showSuccessMessage(config.successMessage);
                }
                if (config.onSuccess) {
                    config.onSuccess(response);
                } else if (config.successCallback) {
                    config.successCallback(response);
                }
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            if (xhr.status === 419) {
                handleCSRFExpiration(config.loginRoute);
                return;
            }
            showErrorMessage(config.errorMessage);
            if (config.onError) {
                config.onError(xhr);
            } else if (config.errorCallback) {
                config.errorCallback(xhr);
            }
        },
    });
}

// Hiển thị thông báo thành công và chuyển hướng
function showSuccessAndRedirect(message, redirectUrl, timer = 1500) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: "success",
            title: "Thành công!",
            text: message,
            timer: timer,
            showConfirmButton: false,
        }).then(() => {
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        });
    } else {
        alert(message);
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    }
}

// Lấy CSRF token
function getCSRFToken() {
    if (typeof $ !== "undefined") {
        return $('meta[name="csrf-token"]').attr("content");
    }
    return "";
}

// Khởi tạo các chức năng chung
if (typeof $ !== "undefined") {
    $(document).ready(function () {
        setupCSRFToken();
    });
}
