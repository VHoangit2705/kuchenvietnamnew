<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Phân quyền hệ thống</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    <!-- Permission Management JS -->
    <script src="{{ asset('js/permissions/permission-common.js') }}"></script>
    <script src="{{ asset('js/permissions/permission-roles.js') }}"></script>
    <script>
        // Initialize permission roles page
        initPermissionRoles(
            '{{ route("roles.create") }}',
            '{{ route("permission.delete", ":id") }}'
        );
    </script>
</body>

</html>