<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa phân quyền</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .permission-group {
            margin-bottom: 1rem;
        }

        .group-header {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .toggle-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-check {
            margin: 0;
        }

        .child-permissions {
            max-height: 0;
            transition: max-height 0.5s ease;
            margin-left: 50px;
        }

        .child-permissions.open {
            max-height: 500px;
        }

        .child-permissions .form-check {
            margin: 5px 0;
        }

        label.fw-bold {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container-fluid" style="height: 80vh;">
        <div class=" mt-4 row">
            <div class="mb-3 col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Thông tin nhóm quyền</h6>
                        <div>
                            <a href="#" onclick="window.history.back()" class="btn btn-outline-secondary btn-sm">Quay lại</a>
                            <button id="btnSave" class="btn btn-outline-primary btn-sm">✓ Lưu</button>
                        </div>
                    </div>
                    <div class="card-body mt-1">
                        <input type="text" class="form-control" hidden name="role_id" id="role_id" value="{{ $role->id }}">
                        <div class="mb-3">
                            <label class="form-label" for="role_name">Tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="role_name" id="role_name" value="{{ $role->name }}" placeholder="Tên nhóm quyền">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="role_description">Mô tả <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="5" name="role_description" id="role_description" placeholder="Mô tả nhóm quyền">{{ $role->description }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3 col-12 col-lg-6">
                <div class="card h-100" style="max-height: 95vh; overflow-y: auto;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Các quyền được thực hiện</h6>
                        <div>
                            <a href="#" class="btn btn-outline-success btn-sm">Chọn tất cả</a>
                            <a href="#" class="btn btn-outline-danger btn-sm">Bỏ chọn tất cả</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="row">
                                @foreach ($listPermissions as $description => $permissions)
                                <div class="permission-group mb-2">
                                    <div class="group-header d-flex align-items-center gap-2" onclick="togglePermissions(this)">
                                        <span class="toggle-btn"><i class="fas fa-plus"></i></span>
                                        <div class="form-check m-0">
                                            <label class="form-check-label">{{ $description }}</label>
                                        </div>
                                    </div>

                                    <div class="child-permissions ps-4 mt-2" style="display: none;">
                                        @foreach ($permissions as $permission)
                                        <div class="form-check">
                                            <input class="form-check-input child-checkbox" type="checkbox" name="permissions[]" id="permission_{{ $permission->id }}"
                                                value="{{ $permission->id }}" {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="permission_{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // kèm csrf token vào header của tất cả các request ajax
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            // Chọn tất cả
            $('.btn-outline-success').on('click', function(e) {
                e.preventDefault();
                $('input.child-checkbox').prop('checked', true);
                $('.child-permissions').slideDown();
                $('.permission-group .toggle-btn i').removeClass('fa-plus').addClass('fa-minus');
            });

            // Bỏ chọn tất cả
            $('.btn-outline-danger').on('click', function(e) {
                e.preventDefault();
                $('input.child-checkbox').prop('checked', false);
            });

            // Mở nhóm có ít nhất 1 checkbox được check
            $('.permission-group').each(function() {
                const group = $(this);
                const checkedBoxes = group.find('input.child-checkbox:checked');
                if (checkedBoxes.length > 0) {
                    // Mở phần child permissions
                    const childPermissions = group.find('.child-permissions');
                    childPermissions.show(); // hoặc slideDown()

                    // Đổi icon + thành -
                    childPermissions.show();
                    const icon = group.find('.toggle-btn i');
                    icon.removeClass('fa-plus').addClass('fa-minus');
                }
            });

            SaveRole();
        });

        function togglePermissions(header) {
            const group = header.closest('.permission-group');
            const children = group.querySelector('.child-permissions');
            const icon = header.querySelector('.toggle-btn i');

            if (children.style.display === 'none') {
                children.style.display = 'block';
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            } else {
                children.style.display = 'none';
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            }
        }

        function toggleGroupCheckbox(groupCheckbox) {
            const group = groupCheckbox.closest('.permission-group');
            const childCheckboxes = group.querySelectorAll('.child-checkbox');
            childCheckboxes.forEach(cb => cb.checked = groupCheckbox.checked);
        }

        function Validate() {
            $roleName = $('#role_name').val().trim();
            $roleDescription = $('#role_description').val().trim();
            $('#role_name').removeClass('is-invalid');
            $('#role_description').removeClass('is-invalid');
            let isvalid = true;
            if (!$roleName) {
                $('#role_name').addClass('is-invalid');
                $('#role_name').focus();
                isvalid = false;
            }
            if (!$roleDescription) {
                $('#role_description').addClass('is-invalid');
                $('#role_description').focus();
                isvalid = false;
            }
            return isvalid;
        }

        function SaveRole() {
            $('#btnSave').on('click', function(e) {
                e.preventDefault();
                if (!Validate()) {
                    return;
                }
                const roleId = $('#role_id').val().trim();
                const roleName = $('#role_name').val().trim();
                const roleDescription = $('#role_description').val().trim();
                const permissions = [];
                // Lấy các checkbox được tick
                $('input.child-checkbox:checked').each(function() {
                    permissions.push($(this).val());
                });
                $.ajax({
                    url: '{{ route("roles.store") }}',
                    method: 'POST',
                    data: {
                        role_id: roleId,
                        role_name: roleName,
                        role_description: roleDescription,
                        permissions: permissions,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: 'Cập nhật thành công!',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi!',
                                text: response.message || 'Có lỗi xảy ra',
                            });
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        if (xhr.status === 419) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Phiên làm việc đã hết hạn',
                                text: 'Vui lòng đăng nhập lại để tiếp tục.',
                                confirmButtonText: 'Đăng nhập'
                            }).then(() => {
                                var loginUrl = '{{ route("login.form") }}';
                                if (!loginUrl) loginUrl = '/login';
                                window.location.href = loginUrl;
                            });
                            return;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: 'Đã xảy ra lỗi khi lưu nhóm quyền.',
                        });
                    }
                });
            });
        }
    </script>
</body>

</html>