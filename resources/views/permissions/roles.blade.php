<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Phân quyền hệ thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <div class="col-12 col-lg-12">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Phân Quyền Hệ Thống</h6>
                    <a href="#" id="btnToggleForm" class="btn btn-outline-secondary btn-sm">+ Thêm quyền</a>
                </div>
                <div class="p-3 d-none" id="formCreateRole">
                    <div class="mb-3">
                        <label class="form-label" for="role_name_new">Tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="role_name_new" id="role_name_new" value="" placeholder="Tên nhóm quyền">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role_description_new">Mô tả <span class="text-danger">*</span></label>
                        <textarea class="form-control" rows="2" name="role_description_new" id="role_description_new" placeholder="Mô tả nhóm quyền"></textarea>
                    </div>
                    <div class="mb-2 d-flex justify-content-center">
                        <a href="#" id="btnCreate" class="btn btn-outline-primary btn-sm">Thêm mới</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive col-12">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 100px;"><input type="checkbox"></th>
                                    <th>Quyền</th>
                                    <th>Mô tả</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($listRoles as $role)
                                <tr>
                                    <td><input type="checkbox"></td>
                                    <td>
                                        <div>{{ $role->name }}</div>
                                        <div class="text-muted small">
                                            ID: {{ $role->id }} |
                                            <a href="{{ route('permission.detail', ['manhom' => $role->id]) }}" class="text-decoration-none">Chỉnh sửa</a> |
                                            <a href="#" class="text-decoration-none text-danger btn-delete-role" data-id="{{ $role->id }}">Xoá</a>
                                        </div>
                                    </td>
                                    <td>{{ $role->description }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#btnToggleForm').on('click', function(e) {
                e.preventDefault();
                $('#formCreateRole').toggleClass('d-none');
            });

            $('.btn-delete-role').click(function(e) {
                e.preventDefault();
                let roleId = $(this).data('id');
                if (confirm('Bạn có chắc chắn muốn xoá nhóm quyền này?')) {
                    $.ajax({
                        url: '{{ route("permission.delete", ":id") }}'.replace(':id', roleId),
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            alert('Xoá thành công!');
                            location.reload();
                        },
                        error: function(xhr) {
                            alert('Đã xảy ra lỗi, không thể xoá.');
                            console.error(xhr.responseText);
                        }
                    });
                }
            });

            CreateRole();
        });

        function Validate() {
            $roleName = $('#role_name_new').val().trim();
            $roleDescription = $('#role_description_new').val().trim();
            $('#role_name_new').removeClass('is-invalid');
            $('#role_description_new').removeClass('is-invalid');
            let isvalid = true;
            if (!$roleName) {
                $('#role_name_new').addClass('is-invalid');
                $('#role_name_new').focus();
                isvalid = false;
            }
            if (!$roleDescription) {
                $('#role_description_new').addClass('is-invalid');
                $('#role_description_new').focus();
                isvalid = false;
            }
            return isvalid;
        }

        function CreateRole() {
            $('#btnCreate').on('click', function(e) {
                e.preventDefault();
                if (!Validate()) {
                    return;
                }
                const roleName = $('#role_name_new').val().trim();
                const roleDescription = $('#role_description_new').val().trim();
                $.ajax({
                    url: '{{ route("roles.create") }}',
                    method: 'POST',
                    data: {
                        role_name: roleName,
                        role_description: roleDescription,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        alert('Đã xảy ra lỗi khi lưu nhóm quyền.');
                    }
                });
            });
        }
    </script>
</body>

</html>