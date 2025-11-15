/**
 * Các hàm cho trang quản lý nhóm quyền
 * Dành cho resources/views/permissions/roles.blade.php
 */

// Kiểm tra form tạo nhóm quyền
function validateRoleCreationForm() {
    return validateRequiredFields([
        { selector: '#role_name_new', removeClass: 'is-invalid' },
        { selector: '#role_description_new', removeClass: 'is-invalid' }
    ]);
}

// Tạo nhóm quyền mới
function createRole(createRoute) {
    if (typeof $ === 'undefined') return;

    $('#btnCreate').on('click', function(e) {
        e.preventDefault();
        if (!validateRoleCreationForm()) {
            return;
        }

        makeAjaxRequest({
            url: createRoute,
            method: 'POST',
            data: {
                role_name: $('#role_name_new').val().trim(),
                role_description: $('#role_description_new').val().trim()
            },
            successMessage: null,
            errorMessage: 'Đã xảy ra lỗi khi lưu nhóm quyền.',
            onSuccess: function() {
                location.reload();
            },
            onError: function(response) {
                if (response && response.message) {
                    alert(response.message);
                }
            }
        });
    });
}

// Xóa nhóm quyền
function deleteRole(deleteRouteTemplate) {
    if (typeof $ === 'undefined') return;

    $('.btn-delete-role').click(function(e) {
        e.preventDefault();
        const roleId = $(this).data('id');

        if (confirm('Bạn có chắc chắn muốn xoá nhóm quyền này?')) {
            makeAjaxRequest({
                url: deleteRouteTemplate.replace(':id', roleId),
                method: 'DELETE',
                data: {},
                successMessage: null,
                errorMessage: 'Đã xảy ra lỗi, không thể xoá.',
                onSuccess: function() {
                    alert('Xoá thành công!');
                    location.reload();
                }
            });
        }
    });
}

// Khởi tạo trang quản lý nhóm quyền
function initPermissionRoles(createRoute, deleteRouteTemplate) {
    if (typeof $ === 'undefined') return;

    $(document).ready(function() {
        // Bật/tắt form tạo mới
        $('#btnToggleForm').on('click', function(e) {
            e.preventDefault();
            $('#formCreateRole').toggleClass('d-none');
        });

        // Khởi tạo xóa nhóm quyền
        deleteRole(deleteRouteTemplate);

        // Khởi tạo tạo nhóm quyền
        createRole(createRoute);
    });
}
