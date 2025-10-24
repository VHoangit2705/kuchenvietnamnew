<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa phân quyền</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                            <a href="#" class="btn btn-outline-secondary btn-sm" onclick="window.history.back()">Quay lại</a>
                            <!-- <a href="#" class="btn btn-outline-primary btn-sm">✓ Lưu</a> -->
                            <!-- <a href="#" class="btn btn-outline-primary btn-sm">Thêm tài khoản</a> -->
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
                                    <option value="HUROM VINH">HUROM VINH</option>
                                    <option value="HUROM HÀ NỘI">HUROM HÀ NỘI</option>
                                    <option value="HUROM HCM">HUROM HCM</option>
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
                        <form method="POST" action="{{ route('permissions.update') }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                            @foreach ($roles as $role)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}"
                                    {{ $selectedUser->roles->contains($role->id) ? 'checked' : '' }}>
                                <label class="form-check-label">{{ $role->name }}</label>
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
    <script>
        function togglePermissions(iconSpan) {
            const content = iconSpan.closest('.group-header').nextElementSibling;
            const icon = iconSpan.querySelector('i');
            content.classList.toggle('open');
            icon.classList.toggle('fa-plus');
            icon.classList.toggle('fa-minus');
        }

        function Validate() {
            $full_name = $('#full_name').val().trim();
            $password = $('#password').val().trim();
            $repassword = $('#repassword').val().trim();
            $zone = $('#zone').val().trim();
            $('.error').removeClass('is-invalid');
            let isvalid = true;
            if (!$full_name) {
                $('#full_name').addClass('is-invalid');
                $('#full_name').focus();
                isvalid = false;
            }
            if (!$password) {
                $('#password').addClass('is-invalid');
                $('#password').focus();
                isvalid = false;
            }
            if (!$repassword) {
                $('#repassword').addClass('is-invalid');
                $('#repassword').focus();
                isvalid = false;
            }
            if (!$zone) {
                $('#zone').addClass('is-invalid');
                $('#zone').focus();
                isvalid = false;
            }
            if ($password !== $repassword) {
                $('#password').addClass('is-invalid');
                $('#repassword').addClass('is-invalid');
                $('#password').focus();
            }
            return isvalid;
        }

        function setupRoleCheckboxes() {
            // Chọn tất cả
            $('.btn-outline-success').on('click', function(e) {
                e.preventDefault();
                $('input[name="roles[]"]').prop('checked', true);
            });

            // Bỏ chọn tất cả
            $('.btn-outline-danger').on('click', function(e) {
                e.preventDefault();
                $('input[name="roles[]"]').prop('checked', false);
            });
        }

        $(document).ready(function() {
            $('#toggleFormUser').on('click', function(e) {
                e.preventDefault();
                $('#formCreateUser').toggleClass('d-none');
            });

            $('#btnCreateUser').on('click', function(e) {
                e.preventDefault();
                if (!Validate()) {
                    return;
                }
                const full_name = $('#full_name').val().trim();
                const password = $('#password').val().trim();
                const repassword = $('#repassword').val().trim();
                const zone = $('#zone').val().trim();
                debugger;
                $.ajax({
                    url: '{{ route("roles.createuser") }}',
                    method: 'POST',
                    data: {
                        full_name: full_name,
                        password: password,
                        zone: zone,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Thêm thành công!');
                        } else {
                            // Xử lý thông báo lỗi từ server
                            let errorMessage = 'Đã có lỗi xảy ra.';
                            if (response.message) {
                                if (typeof response.message === 'object') {
                                    // Lấy lỗi đầu tiên từ object validation
                                    const firstErrorKey = Object.keys(response.message)[0];
                                    errorMessage = response.message[firstErrorKey][0];
                                } else {
                                    // Nếu message là một chuỗi bình thường
                                    errorMessage = response.message;
                                }
                            }
                            alert(errorMessage);
                        }
                    },
                    error: function(xhr) {
                        alert('Đã xảy ra lỗi khi lưu nhóm quyền.');
                    }
                });
            });

            setupRoleCheckboxes();
        });
    </script>
</body>

</html>