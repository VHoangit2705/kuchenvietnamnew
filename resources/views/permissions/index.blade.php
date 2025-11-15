<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa phân quyền</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .form-check {
            margin: 0;
        }

        .child-permissions {
            max-height: 0;
            overflow: hidden;
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
    <div class="container-fluid">
        <div class=" mt-4 row">
            <div class="mb-3 col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Phân quyền tài khoản</h6>
                        <div>
                            {{-- <a href="#" class="btn btn-outline-secondary btn-sm" onclick="window.history.back()">Quay lại</a> --}}
                            <a href="#" class="btn btn-outline-primary btn-sm" onclick="window.location.href = '{{ route('permissions.index') }}';">Reset</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('permissions.index') }}" class="mb-3 mt-2">
                            <label class="form-label">Tài khoản phân quyền</label>
                            <select name="user_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Chọn tài khoản --</option>
                                @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->full_name }}
                                </option>
                                @endforeach
                            </select>
                        </form>
                        <a href="#" id="toggleFormUser">Thêm tài khoản mới</a>
                        <div class="d-none mt-3" id="formCreateUser">
                            <div class="mb-2">
                                <label class="form-label" for="full_name">Tên đẩy đủ <span class="text-danger">*</span></label>
                                <input type="text" class="error form-control" name="full_name" id="full_name" placeholder="Tên tài khoản">
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="password">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="error form-control" name="password" id="password" placeholder="Mật khẩu">
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="repassword">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="error form-control" name="repassword" id="repassword" placeholder="Mật khẩu">
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="zone">Chi nhánh <span class="text-danger">*</span></label>
                                <select name="zone" id="zone" class="error form-select">
                                    <option value="">-- Chọn chi nhánh --</option>
                                    <option value="KUCHEN VINH">KUCHEN VINH</option>
                                    <option value="KUCHEN HÀ NỘI">KUCHEN HÀ NỘI</option>
                                    <option value="KUCHEN HCM">KUCHEN HCM</option>
                                    <!--<option value="HUROM VINH">HUROM VINH</option>
                                    <option value="HUROM HÀ NỘI">HUROM HÀ NỘI</option>
                                    <option value="HUROM HCM">HUROM HCM</option>-->
                                </select>
                            </div>
                            <div class="mb-2 d-flex justify-content-end">
                                <button id="btnCreateUser" class="btn btn-primary btn-sm">Thêm tài khoản</button>
                            </div>
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
                        @if ($selectedUser)
                        <form id="permissionsForm" method="POST" action="{{ route('permissions.update') }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                            
                            <!-- Thông tin người dùng -->
                            <div class="mb-3">
                                <label class="form-label">Tên: <strong>{{ $selectedUser->full_name }}</strong></label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Chi nhánh: <strong>{{ $selectedUser->zone }}</strong></label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Chức vụ hiện tại: </label>
                                <div class="badge text-wrap {{ empty($selectedUser->position) ? 'bg-warning text-dark' : 'bg-primary' }}" style="width: 8rem;">
                                    <span id="current-position">{{ $selectedUser->position ?: 'Chưa có' }}</span>
                                </div>
                            </div>

                            
                            <hr>
                            <h6>Phân quyền:</h6>
                            @foreach ($roles as $role)
                            <div class="form-check mt-2">
                                <input class="form-check-input role-checkbox" type="checkbox" name="roles[]" value="{{ $role->id }}"
                                    data-description="{{ $role->description }}"
                                    {{ $selectedUser->roles->contains($role->id) ? 'checked' : '' }}>
                                <label class="form-check-label">
                                    @if($role->description)
                                        <strong>{{ $role->description }}</strong>
                                    @endif
                                </label>
                            </div>
                            @endforeach
                            <button type="submit" class="btn btn-outline-primary btn-sm mt-3">Cập nhật</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Permission Management JS -->
    <script src="{{ asset('js/permissions/permission-common.js') }}"></script>
    <script src="{{ asset('js/permissions/permission-index.js') }}"></script>
    <script>
        // Show session messages
        @if(session('success'))
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: '{{ session('success') }}',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        @endif

        @if(session('error'))
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'OK'
                });
            }
        @endif

        @if(isset($errors) && $errors->any())
            if (typeof Swal !== 'undefined') {
                let errorMessage = '';
                @foreach($errors->all() as $error)
                    errorMessage += '{{ $error }}\n';
                @endforeach
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi validation!',
                    text: errorMessage,
                    confirmButtonText: 'OK'
                });
            }
        @endif

        // Initialize permission index page
        initPermissionIndex(
            '{{ route("roles.createuser") }}',
            '{{ route("permissions.index") }}',
            '{{ route("login.form") ?: "/login" }}'
        );
    </script>
</body>

</html>