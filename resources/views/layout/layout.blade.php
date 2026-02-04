<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if(session()->has('brand') && session('brand') == 'kuchen')
    <link rel="shortcut icon" href="{{ asset('imgs/logokuchen.png') }}" type="image/x-icon">
    @elseif(session()->has('brand') && session('brand') == 'hurom')
    <link rel="shortcut icon" href="{{ asset('imgs/logohurom.png') }}" type="image/x-icon">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bảo hành {{ strtoupper(session('brand')) }}</title>
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"
        integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
        integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF"
        crossorigin="anonymous"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <!-- JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('public/css/menu.css') }}">
</head>

<body class="bg-gray text-gray-800">
    <header class="bg-dark py-2 position-relative" style="height: 60px; z-index: 1050;">
        <div class="container-fluid d-flex align-items-center" style="height: 100%; gap: 20px; padding-left: 15;">
            <!-- Logo (Hidden on Small Screens) -->
            <div class="header-logo d-flex align-items-center d-none d-lg-flex" style="flex-shrink: 0;">
                <a href="{{ route('home') }}">
                    @if (session()->has('brand') && session('brand') == 'kuchen')
                    <img src="{{ asset('imgs/logokuchen.png') }}" alt="Logo" style="height: 50px;">
                    @elseif(session()->has('brand') && session('brand') == 'hurom')
                    <img src="{{ asset('imgs/hurom.webp') }}" alt="Logo" style="height: 30px;">
                    @endif
                </a>
            </div>

            <!-- Toggle Menu for Small Screens -->
            <button class="navbar-toggler d-lg-none text-white border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false"
                aria-label="Toggle navigation" style="z-index: 1060; flex-shrink: 0; padding-left: 15px;">
                @if (session()->has('brand'))
                <img src="{{ asset('icons/menu.png') }}" alt="Menu"
                    style="height: 25px; filter: invert(100%) sepia(100%) saturate(2) hue-rotate(180deg);">
                @endif
            </button>

            <!-- Full Menu (Hidden on small screens) -->
            <nav class="nav d-none d-lg-flex" style="flex: 1 1 auto; justify-content: center; gap: 10px; padding-left: 15px;">
                @if (session()->has('brand'))
                @if (Auth::user()->hasPermission('Danh sách ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('warranty.' . session('brand')) }}">Danh sách ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Tiếp nhận ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('warranty.check') }}">Tiếp nhận ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Thống kê ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('baocao') }}">Thống kê bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('ctv.getlist') }}">Quản lý CTV</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('requestagency.index') }}">QL Đại Lý</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('dieuphoi.index') }}">Điều phối CTV</a>
                @endif
                @if (Auth::user()->hasPermission('In tem bảo hành'))
                <a class="nav-link text-white" href="{{ route('warrantycard') }}">In tem bảo hành</a>
                @endif
                @endif
            </nav>
            <!-- Account Section -->
            <div class="d-flex align-items-center text-white" style="flex-shrink: 0; margin-left: auto; padding-right: 15px;">
                <div class="dropdown">
                    <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center" type="button" id="accountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                        <span class="d-none d-lg-inline">{{ Auth::user()->full_name ?? 'Khách' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                        <li>
                            <a class="dropdown-item" href="#" onclick="checkAndOpenPasswordModal();">
                                <i class="bi bi-person-gear me-2"></i>Cập nhật thông tin
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Form đăng xuất ẩn -->
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>

        <!-- Collapsible Menu for Small Screens (Overlay with z-index) -->
        <div class="collapse position-absolute w-100 bg-dark d-lg-none" id="navbarMenu"
            style="top: 48px; z-index: 1050;">
            <nav class="nav flex-column text-white p-3">
                @if (session()->has('brand'))
                <a class="nav-link text-white" href="{{ route('home')}}">Thoát khỏi {{ strtoupper(session('brand')) }}</a>
                @if (Auth::user()->hasPermission('Danh sách ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('warranty.' . session('brand')) }}">Danh sách ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Tiếp nhận ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('warranty.check') }}">Tiếp nhận ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Thống kê ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('baocao') }}">Thống kê bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('ctv.getlist') }}">Quản lý CTV</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('requestagency.index') }}">QL Đại Lý</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('dieuphoi.index') }}">Điều phối CTV</a>
                @endif
                @if (Auth::user()->hasPermission('In tem bảo hành'))
                <a class="nav-link text-white" href="{{ route('warrantycard') }}">In tem bảo hành</a>
                @endif
                @endif
            </nav>
        </div>
    </header>
    <div class="content mb-3 pb-3">
        @yield('content')
    </div>
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Modal Cập Nhật Thông Tin -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Cập nhật thông tin tài khoản</h5>
                    <button type="button" class="btn-close" id="modalCloseBtn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changePasswordForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" value="{{ Auth::user()->username ?? '' }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ Auth::user()->email ?? '' }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <hr class="my-3">
                        <h6 class="mb-3">Đổi mật khẩu</h6>
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Mật khẩu hiện tại <span class="text-muted">(để trống nếu không đổi)</span></label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" minlength="8">
                            <small class="form-text text-muted">Mật khẩu phải có ít nhất 8 ký tự, bao gồm cả chữ cái và số</small>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" minlength="8">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelBtn" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- <footer class="bg-dark py-2 position-relative" style="height: 60px; flex-shrink: 0;" >
    </footer> -->

    <script>
        function NewYear2026Notice() {
            const now = new Date();
            const currentYear = now.getFullYear();

            // Chỉ hiển thị trong năm 2026
            if (currentYear !== 2026) return;

            const STORAGE_KEY = 'newyear2026_notice_closed_at';
            const HIDE_DURATION = 12 * 60 * 60 * 1000; // 12 giờ

            const lastClosedTime = localStorage.getItem(STORAGE_KEY);

            if (lastClosedTime) {
                const diff = Date.now() - parseInt(lastClosedTime, 10);
                if (diff < HIDE_DURATION) {
                    return; // Chưa đủ 12h thì không hiện
                }
            }

            Swal.fire({
                title: '🎉 Chào mừng năm mới 2026',
                html: `
                <div class="text-center">
                    <img src="{{ asset('public/imgs/chao2026.jpg') }}" 
                         alt="Chào mừng năm mới 2026"
                         class="img-fluid mb-3"
                         style="max-height:350px;">
                    <p class="fw-bold mb-2">
                        🎉 Ngày làm việc đầu tiên của năm 2026
                    </p>
                    <p>
                        KÜCHEN kính chúc Quý Anh/Chị nhân viên công ty.<br>
                        <strong>Sức khỏe – Thành công – Nhiều khởi sắc</strong><br>
                        Chúc một năm làm việc hiệu quả và thuận lợi!
                    </p>
                </div>
            `,
                icon: 'success',
                confirmButtonText: 'Bắt đầu làm việc',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                // Lưu thời điểm đóng popup
                localStorage.setItem(STORAGE_KEY, Date.now());
            });
        }
    </script>
    <script>
        function goBackOrReload() {
            if (document.referrer) {
                window.history.back();
            }
        }

        function OpenWaitBox() {
            $('#loadingOverlay').removeClass('d-none');
        }

        function CloseWaitBox() {
            $('#loadingOverlay').addClass('d-none');
        }

        function Notification(icon, title, timeout, confirm) {
            Swal.fire({
                icon: icon,
                title: title,
                timer: timeout,
                showConfirmButton: confirm
            });
        }

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        $(document).ready(function() {
            ThongBao();
            CheckPasswordExpiry();
            NewYear2026Notice();
            // Set active menu item based on current URL
            setActiveMenuItem();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            // Bắt lỗi AJAX toàn cục
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thông báo',
                        text: xhr.responseJSON?.message || 'Phiên đăng nhập đã hết hạn.',
                    }).then(() => {
                        window.location.href = "{{ route('login') }}";
                    });
                }
            });

            // Reset form khi đóng modal
            $('#changePasswordModal').on('hidden.bs.modal', function() {
                $('#changePasswordForm')[0].reset();
                $('#changePasswordForm').find('.is-invalid').removeClass('is-invalid');
                $('#changePasswordForm').find('.invalid-feedback').text('');
                // Load lại giá trị username và email hiện tại
                $('#username').val('{{ Auth::user()->username ?? "" }}');
                $('#email').val('{{ Auth::user()->email ?? "" }}');
            });

            // Real-time validation for username (sử dụng event delegation để tránh đăng ký nhiều lần)
            $(document).on('input', '#changePasswordModal #username', function() {
                const $username = $(this);
                const username = $username.val().trim();
                $username.removeClass('is-invalid');
                $username.next('.invalid-feedback').text('');

                if (username) {
                    const usernameRegex = /^[a-zA-Z0-9_-]+$/;
                    if (!usernameRegex.test(username)) {
                        $username.addClass('is-invalid');
                        $username.next('.invalid-feedback').text('Tên đăng nhập không được chứa dấu tiếng Việt và không được có dấu cách. Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-).');
                    }
                }
            });

            // Real-time validation for email (sử dụng event delegation để tránh đăng ký nhiều lần)
            $(document).on('input', '#changePasswordModal #email', function() {
                const $email = $(this);
                const email = $email.val().trim();
                $email.removeClass('is-invalid');
                $email.next('.invalid-feedback').text('');

                if (email) {
                    if (!validateEmail(email)) {
                        $email.addClass('is-invalid');
                        $email.next('.invalid-feedback').text('Email không đúng định dạng.');
                    }
                }
            });

            // Xử lý form cập nhật thông tin
            $('#changePasswordForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const username = $('#username').val();
                const email = $('#email').val();
                const currentPassword = $('#currentPassword').val();
                const newPassword = $('#newPassword').val();
                const confirmPassword = $('#confirmPassword').val();

                // Reset validation
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');

                // Validate username và email
                if (!username || username.trim() === '') {
                    $('#username').addClass('is-invalid');
                    $('#username').next('.invalid-feedback').text('Vui lòng nhập tên đăng nhập.');
                    return;
                } else {
                    // Validate username: không được có dấu tiếng Việt và không được có dấu cách
                    // Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-)
                    const usernameRegex = /^[a-zA-Z0-9_-]+$/;
                    if (!usernameRegex.test(username.trim())) {
                        $('#username').addClass('is-invalid');
                        $('#username').next('.invalid-feedback').text('Tên đăng nhập không được chứa dấu tiếng Việt và không được có dấu cách. Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-).');
                        return;
                    }
                }

                if (!email || email.trim() === '') {
                    $('#email').addClass('is-invalid');
                    $('#email').next('.invalid-feedback').text('Vui lòng nhập email.');
                    return;
                } else {
                    // Validate email format using the provided function
                    if (!validateEmail(email.trim())) {
                        $('#email').addClass('is-invalid');
                        $('#email').next('.invalid-feedback').text('Email không đúng định dạng.');
                        return;
                    }
                }

                // Validate mật khẩu nếu có nhập
                if (newPassword || confirmPassword || currentPassword) {
                    if (!currentPassword) {
                        $('#currentPassword').addClass('is-invalid');
                        $('#currentPassword').next('.invalid-feedback').text('Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.');
                        return;
                    }

                    if (!newPassword) {
                        $('#newPassword').addClass('is-invalid');
                        $('#newPassword').next('.invalid-feedback').text('Vui lòng nhập mật khẩu mới.');
                        return;
                    }

                    if (newPassword !== confirmPassword) {
                        $('#confirmPassword').addClass('is-invalid');
                        $('#confirmPassword').next('.invalid-feedback').text('Mật khẩu xác nhận không khớp.');
                        return;
                    }

                    if (newPassword.length < 8) {
                        $('#newPassword').addClass('is-invalid');
                        $('#newPassword').next('.invalid-feedback').text('Mật khẩu phải có ít nhất 8 ký tự.');
                        return;
                    }

                    // Kiểm tra mật khẩu có chữ cái và số
                    const hasLetter = /[A-Za-z]/.test(newPassword);
                    const hasNumber = /\d/.test(newPassword);

                    if (!hasLetter || !hasNumber) {
                        $('#newPassword').addClass('is-invalid');
                        $('#newPassword').next('.invalid-feedback').text('Mật khẩu phải có ít nhất 8 ký tự, bao gồm cả chữ cái và số.');
                        return;
                    }
                }

                // Prepare data
                const formData = {
                    username: username,
                    email: email
                };

                // Chỉ thêm mật khẩu nếu có nhập
                if (newPassword && currentPassword) {
                    formData.current_password = currentPassword;
                    formData.new_password = newPassword;
                    formData.confirm_password = confirmPassword;
                }

                $.ajax({
                    url: "{{ route('password.change') }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: response.message || 'Cập nhật thông tin thành công!',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                $('#changePasswordModal').modal('hide');

                                // Nếu đổi mật khẩu thì logout và redirect về login
                                if (response.logout_required) {
                                    // Xóa cookie thiết bị và remember_token trước khi redirect
                                    document.cookie = 'browser_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                                    document.cookie = 'machine_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                                    document.cookie = 'remember_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                                    // Redirect về trang login (server đã logout rồi)
                                    window.location.href = "{{ route('login.form') }}";
                                } else {
                                    // Chỉ reload nếu chỉ cập nhật thông tin
                                    location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: response.message || 'Cập nhật thông tin thất bại!'
                            });
                        }
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        if (xhr.responseJSON?.message) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: xhr.responseJSON.message
                            });
                        } else {
                            Object.keys(errors).forEach(function(key) {
                                const input = form.find('[name="' + key + '"]');
                                input.addClass('is-invalid');
                                input.next('.invalid-feedback').text(errors[key][0]);
                            });
                        }
                    }
                });
            });
        })

        // Kiểm tra và cảnh báo đổi mật khẩu
        function CheckPasswordExpiry() {
            $.ajax({
                url: "{{ route('password.check-expiry') }}",
                type: 'GET',
                success: function(response) {
                    if (response.should_warn) {
                        const daysRemaining = response.days_remaining || 0;
                        const daysSinceChange = response.days_since_change || 0;
                        const isExpired = daysSinceChange >= 30 || daysRemaining <= 0;
                        const message = daysRemaining > 0 ?
                            `Mật khẩu của bạn sẽ hết hạn sau ${daysRemaining} ngày. Vui lòng đổi mật khẩu để bảo mật tài khoản.` :
                            'Mật khẩu của bạn đã quá 30 ngày. Vui lòng đổi mật khẩu ngay!';

                        Swal.fire({
                            icon: 'warning',
                            title: 'Cảnh báo đổi mật khẩu',
                            text: message,
                            confirmButtonText: 'Đổi mật khẩu ngay',
                            allowOutsideClick: false,
                            allowEscapeKey: !isExpired
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Load dữ liệu user hiện tại vào form
                                $('#username').val('{{ Auth::user()->username ?? "" }}');
                                $('#email').val('{{ Auth::user()->email ?? "" }}');
                                openPasswordModal(isExpired);
                            }
                        });
                    }
                },
                error: function(xhr) {
                    console.error("Lỗi kiểm tra mật khẩu:", xhr);
                }
            });
        }

        // Kiểm tra và mở modal từ dropdown menu
        function checkAndOpenPasswordModal() {
            $('#username').val('{{ Auth::user()->username ?? "" }}');
            $('#email').val('{{ Auth::user()->email ?? "" }}');

            // Kiểm tra xem mật khẩu có quá hạn không
            $.ajax({
                url: "{{ route('password.check-expiry') }}",
                type: 'GET',
                success: function(response) {
                    if (response.should_warn) {
                        const daysSinceChange = response.days_since_change || 0;
                        const isExpired = daysSinceChange >= 30;
                        openPasswordModal(isExpired);
                    } else {
                        openPasswordModal(false);
                    }
                },
                error: function() {
                    // Nếu lỗi thì mở modal bình thường
                    openPasswordModal(false);
                }
            });
        }

        // Hàm mở modal với tùy chọn disable nút hủy
        function openPasswordModal(isExpired) {
            const modalElement = document.getElementById('changePasswordModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const cancelBtn = $('#cancelBtn');
            const closeBtn = $('#modalCloseBtn');

            if (isExpired) {
                // Mật khẩu quá hạn: disable nút Hủy và nút đóng
                cancelBtn.prop('disabled', true).addClass('d-none');
                closeBtn.prop('disabled', true).addClass('d-none');
                // Không cho đóng modal bằng cách click bên ngoài hoặc ESC
                modalElement.setAttribute('data-bs-backdrop', 'static');
                modalElement.setAttribute('data-bs-keyboard', 'false');
                // Cập nhật cấu hình modal
                const newModal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                // Thêm cảnh báo
                if (!$('.password-expired-alert').length) {
                    $('.modal-body').prepend(
                        '<div class="alert alert-warning password-expired-alert mb-3">' +
                        '<i class="bi bi-exclamation-triangle me-2"></i>' +
                        '<strong>Mật khẩu đã quá hạn!</strong> Bạn phải đổi mật khẩu để tiếp tục sử dụng hệ thống.' +
                        '</div>'
                    );
                }
                // Bắt buộc phải nhập mật khẩu mới
                $('#currentPassword').prop('required', true);
                $('#newPassword').prop('required', true);
                $('#confirmPassword').prop('required', true);
                newModal.show();
            } else {
                // Mật khẩu chưa quá hạn: enable các nút
                cancelBtn.prop('disabled', false).removeClass('d-none');
                closeBtn.prop('disabled', false).removeClass('d-none');
                modalElement.setAttribute('data-bs-backdrop', 'true');
                modalElement.setAttribute('data-bs-keyboard', 'true');
                // Cập nhật cấu hình modal
                const newModal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true
                });
                $('.password-expired-alert').remove();
                // Không bắt buộc nhập mật khẩu
                $('#currentPassword').prop('required', false);
                $('#newPassword').prop('required', false);
                $('#confirmPassword').prop('required', false);
                newModal.show();
            }
        }

        // Reset modal khi đóng
        $('#changePasswordModal').on('hidden.bs.modal', function() {
            $('#cancelBtn').prop('disabled', false).removeClass('d-none');
            $('#modalCloseBtn').prop('disabled', false).removeClass('d-none');
            $(this).attr('data-bs-backdrop', 'true');
            $(this).attr('data-bs-keyboard', 'true');
            $('.password-expired-alert').remove();
        });

        function ThongBao() {
            const userBrand = @json(session('brand'));
            if (!userBrand) {
                return;
            }
            const timelimit = 4 * 60 * 60 * 1000;
            let lastTime = localStorage.getItem('lastThongBaoTime');
            let now = Date.now();

            if (lastTime && (now - lastTime) < timelimit) {
                return;
            }

            $.ajax({
                url: "{{ route('warranty.thongbao') }}",
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: "warning",
                            title: 'Cảnh Báo!',
                            text: response.message,
                            showCancelButton: true,
                            confirmButtonText: 'Xem ngay',
                            cancelButtonText: 'Xác nhận',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const tab = 'quahan';
                                const brand = "{{ session('brand') }}";
                                let baseUrl = "{{ route('warranty.kuchen') }}";
                                if (brand === 'hurom') {
                                    baseUrl = "{{ route('warranty.hurom') }}";
                                }
                                window.location.href = baseUrl + "?tab=" + tab + "&kythuatvien=" + encodeURIComponent(response.nhanvien);
                            }
                        });
                        localStorage.setItem('lastThongBaoTime', now);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Đã xảy ra lỗi:", error);
                }
            });
        }

        function updateQueryStringParameter(uri, key, value) {
            let re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            let separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + encodeURIComponent(value) + '$2');
            } else {
                return uri + separator + key + "=" + encodeURIComponent(value);
            }
        }

        // Set active menu item based on current URL
        function setActiveMenuItem() {
            const currentUrl = window.location.href;
            const currentPath = window.location.pathname;

            // Remove active class from all menu items
            $('.nav-link').removeClass('active');

            // Check each menu link
            $('.nav-link').each(function() {
                const linkHref = $(this).attr('href');
                if (linkHref) {
                    // Check if current URL matches the link
                    if (currentUrl.includes(linkHref) || currentPath === linkHref) {
                        $(this).addClass('active');
                    }
                }
            });
        }

        // Gửi request mỗi 5 phút để giữ session sống
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                fetch('/keep-alive', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            }
        }, 5 * 60 * 1000);
    </script>
    <script src="{{ asset('public/js/validateform.js') }}"></script>
</body>

</html>