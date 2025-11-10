/**
 * Các hàm cho trang chỉnh sửa phân quyền
 * Dành cho resources/views/permissions/edit.blade.php
 */

// Kiểm tra form nhóm quyền
function validateRoleForm() {
    return validateRequiredFields([
        { selector: '#role_name', removeClass: 'is-invalid' },
        { selector: '#role_description', removeClass: 'is-invalid' }
    ]);
}

// Lưu nhóm quyền cùng với các quyền
function saveRole(storeRoute, loginRoute) {
    if (typeof $ === 'undefined') return;
    
    $('#btnSave').on('click', function(e) {
        e.preventDefault();
        if (!validateRoleForm()) {
            return;
        }
        
        const roleId = $('#role_id').val().trim();
        const roleName = $('#role_name').val().trim();
        const roleDescription = $('#role_description').val().trim();
        const permissions = [];
        
        // Lấy tất cả các checkbox đã được chọn
        $('input.child-checkbox:checked').each(function() {
            permissions.push($(this).val());
        });
        
        makeAjaxRequest({
            url: storeRoute,
            method: 'POST',
            data: {
                role_id: roleId,
                role_name: roleName,
                role_description: roleDescription,
                permissions: permissions
            },
            loginRoute: loginRoute,
            successMessage: 'Cập nhật thành công!',
            errorMessage: 'Đã xảy ra lỗi khi lưu nhóm quyền.'
        });
    });
}

// Khởi tạo trang chỉnh sửa
function initPermissionEdit(storeRoute, loginRoute) {
    if (typeof $ === 'undefined') return;
    
    $(document).ready(function() {
        // Chọn tất cả
        $('.btn-outline-success').on('click', function(e) {
            e.preventDefault();
            selectAllCheckboxes('input.child-checkbox');
        });

        // Bỏ chọn tất cả
        $('.btn-outline-danger').on('click', function(e) {
            e.preventDefault();
            deselectAllCheckboxes('input.child-checkbox');
        });

        // Tự động mở các nhóm có item được chọn
        autoOpenCheckedGroups();

        // Khởi tạo lưu nhóm quyền
        saveRole(storeRoute, loginRoute);
    });
}

