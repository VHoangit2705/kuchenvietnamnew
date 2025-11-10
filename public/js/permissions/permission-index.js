/**
 * Các hàm cho trang danh sách phân quyền
 * Dành cho resources/views/permissions/index.blade.php
 */

// Kiểm tra form tạo tài khoản
function validateUserForm() {
    if (typeof $ === 'undefined') return false;

    // Xóa tất cả các class lỗi trước
    $('.error').removeClass('is-invalid');

    // Kiểm tra các trường bắt buộc
    let isValid = validateRequiredFields([
        { selector: '#full_name', removeClass: 'is-invalid' },
        { selector: '#password', removeClass: 'is-invalid' },
        { selector: '#repassword', removeClass: 'is-invalid' },
        { selector: '#zone', removeClass: 'is-invalid' }
    ]);

    // Kiểm tra mật khẩu khớp
    if (isValid) {
        const password = $('#password').val().trim();
        const repassword = $('#repassword').val().trim();
        if (password !== repassword) {
            $('#password').addClass('is-invalid');
            $('#repassword').addClass('is-invalid');
            $('#password').focus();
            isValid = false;
        }
    }

    return isValid;
}

// Tạo tài khoản mới
function createUser(createUserRoute, indexRoute, loginRoute) {
    if (typeof $ === 'undefined') return;

    $('#btnCreateUser').on('click', function(e) {
        e.preventDefault();
        if (!validateUserForm()) {
            return;
        }

        makeAjaxRequest({
            url: createUserRoute,
            method: 'POST',
            data: {
                full_name: $('#full_name').val().trim(),
                password: $('#password').val().trim(),
                zone: $('#zone').val().trim()
            },
            loginRoute: loginRoute,
            successMessage: null,
            errorMessage: 'Đã xảy ra lỗi khi tạo tài khoản',
            onSuccess: function() {
                showSuccessAndRedirect('Tài khoản đã được tạo thành công', indexRoute);
            }
        });
    });
}

// Xử lý submit form phân quyền
function handlePermissionsForm(loginRoute) {
    if (typeof $ === 'undefined') return;

    $('#permissionsForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);

        makeAjaxRequest({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            loginRoute: loginRoute,
            successMessage: 'Cập nhật quyền thành công!',
            errorMessage: 'Đã xảy ra lỗi khi cập nhật quyền'
        });
    });
}

// Cập nhật chức vụ hiện tại dựa trên các role được chọn
function updateCurrentPosition() {
    if (typeof $ === 'undefined') return;

    $('.role-checkbox').on('change', function() {
        const checkedRoles = $('.role-checkbox:checked');
        let newPosition = '';

        if (checkedRoles.length > 0) {
            newPosition = checkedRoles.first().data('description');
        }

        $('#current-position').text(newPosition || 'Chưa có');
    });
}

// Khởi tạo trang danh sách
function initPermissionIndex(createUserRoute, indexRoute, loginRoute) {
    if (typeof $ === 'undefined') return;

    $(document).ready(function() {
        // Bật/tắt form tạo tài khoản
        $('#toggleFormUser').on('click', function(e) {
            e.preventDefault();
            $('#formCreateUser').toggleClass('d-none');
        });

        // Xử lý khi thay đổi dropdown tài khoản
        $('select[name="user_id"]').on('change', function() {
            const userId = $(this).val();
            if (userId) {
                $(this).closest('form').submit();
            } else {
                $('.role-checkbox').prop('checked', false);
                $('#current-position').text('Chưa có');
            }
        });

        // Khởi tạo tạo tài khoản
        createUser(createUserRoute, indexRoute, loginRoute);

        // Giữ session sống mỗi 5 phút
        setInterval(function() {
            $.get('/keep-alive');
        }, 5 * 60 * 1000);

        // Xử lý form phân quyền
        handlePermissionsForm(loginRoute);

        // Cập nhật chức vụ hiện tại
        updateCurrentPosition();

        // Chọn tất cả các role
        $('.card .btn-outline-success').on('click', function(e) {
            e.preventDefault();
            $('.role-checkbox').prop('checked', true);
            const firstDesc = $('.role-checkbox:checked').first().data('description');
            $('#current-position').text(firstDesc || 'Chưa có');
        });

        // Bỏ chọn tất cả các role
        $('.card .btn-outline-danger').on('click', function(e) {
            e.preventDefault();
            $('.role-checkbox').prop('checked', false);
            $('#current-position').text('Chưa có');
        });
    });
}