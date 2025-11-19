<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4" style="max-width:500px; width: 100%;">
            <h5 class="text-center mb-4">ĐĂNG NHẬP TRUNG TÂM BẢO HÀNH</h5>
            <!-- Thông báo khóa tài khoản -->
            @if(session('account_locked') || (isset($account_locked) && $account_locked))
                <div id="lockAlert" class="alert alert-danger" role="alert">
                    <strong>⚠️ Tài khoản bị khóa:</strong>
                    <div id="lockMessage" class="mt-2">
                        Bạn đã nhập sai mật khẩu quá 5 lần. Tài khoản đã bị khóa trong 1 giờ.
                    </div>
                    <div id="countdown" class="mt-2 fw-bold"></div>
                </div>
            @else
                <div id="lockAlert" class="alert alert-danger d-none" role="alert">
                    <strong>⚠️ Tài khoản bị khóa:</strong>
                    <div id="lockMessage" class="mt-2"></div>
                    <div id="countdown" class="mt-2 fw-bold"></div>
                </div>
            @endif
            
            <!-- Thông báo số lần thử còn lại (sai mật khẩu) -->
            @if(session('remaining_attempts') && !session('account_locked') && !session('device_limit_warning'))
                <div id="attemptsAlert" class="alert alert-warning" role="alert">
                    <div id="attemptsMessage">
                        <strong>⚠️ Cảnh báo:</strong> Bạn đã nhập sai mật khẩu {{ session('failed_attempts') }} lần. 
                        Còn lại <strong>{{ session('remaining_attempts') }}</strong> lần thử trước khi tài khoản bị khóa.
                    </div>
                </div>
            @else
                <div id="attemptsAlert" class="alert alert-warning d-none" role="alert">
                    <div id="attemptsMessage"></div>
                </div>
            @endif
            
            <!-- Thông báo số lần thử còn lại (spam device limit) -->
            @if(session('device_limit_warning') && session('remaining_attempts') && !session('account_locked'))
                <div id="deviceLimitAlert" class="alert alert-danger" role="alert">
                    <div id="deviceLimitMessage">
                        <strong>⚠️ Cảnh báo:</strong> Bạn đã cố gắng đăng nhập {{ session('failed_attempts') }} lần khi đạt giới hạn thiết bị. 
                        Còn lại <strong>{{ session('remaining_attempts') }}</strong> lần thử trước khi tài khoản bị khóa trong 1 giờ.
                    </div>
                </div>
            @else
                <div id="deviceLimitAlert" class="alert alert-danger d-none" role="alert">
                    <div id="deviceLimitMessage"></div>
                </div>
            @endif
            <form action="{{ route('login') }}" method="POST" id="loginForm">
                @csrf
                <input type="hidden" name="machine_id" id="machine_id">
                <input type="hidden" name="browser_info" id="browser_info">
                <input type="hidden" name="device_type" id="device_type">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required autofocus>
                    @error('username')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @else
                        <div class="invalid-feedback"></div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                    @error('password')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
            </form>
        </div>
    </div>
    @if($errors->has('msg'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'Thông báo',
            text: @json($errors->first('msg')),
        });
    </script>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('public/js/device-fingerprint.js') }}?v=2025-11-18"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const machineIdField = document.getElementById('machine_id');
            const browserInfoField = document.getElementById('browser_info');

            if (window.MachineIdentity) {
                const machineId = window.MachineIdentity.get();
                const browserInfo = window.MachineIdentity.getBrowserInfo();

                machineIdField.value = machineId;
                browserInfoField.value = JSON.stringify(browserInfo);
            } else {
                console.error('MachineIdentity script missing');
            }

            // Xử lý countdown cho tài khoản bị khóa
            @if(session('account_locked') && session('lockout_until'))
                const lockoutUntil = {{ session('lockout_until') }};
                const countdownElement = document.getElementById('countdown');
                
                function updateCountdown() {
                    const now = Math.floor(Date.now() / 1000);
                    const remaining = lockoutUntil - now;
                    
                    if (remaining <= 0) {
                        countdownElement.textContent = 'Tài khoản đã được mở khóa. Vui lòng thử lại.';
                        countdownElement.className = 'mt-2 fw-bold text-success';
                        // Reload page after 2 seconds
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                        return;
                    }
                    
                    const hours = Math.floor(remaining / 3600);
                    const minutes = Math.floor((remaining % 3600) / 60);
                    const seconds = remaining % 60;
                    
                    if (hours > 0) {
                        countdownElement.textContent = `Thời gian còn lại: ${hours} giờ ${minutes} phút ${seconds} giây`;
                    } else {
                        countdownElement.textContent = `Thời gian còn lại: ${minutes} phút ${seconds} giây`;
                    }
                }
                
                updateCountdown();
                setInterval(updateCountdown, 1000);
                
                // Disable form khi bị khóa
                const loginForm = document.getElementById('loginForm');
                if (loginForm) {
                    loginForm.querySelectorAll('input, button').forEach(element => {
                        element.disabled = true;
                    });
                }
            @endif

            // Real-time validation for username
            const usernameInput = document.getElementById('username');
            const usernameFeedback = usernameInput.nextElementSibling;
            const initialUsername = usernameInput.value;
            
            usernameInput.addEventListener('input', function() {
                const username = this.value.trim();
                
                // Nếu user đã thay đổi giá trị so với ban đầu, xóa lỗi từ server và validate lại
                if (username !== initialUsername) {
                    usernameInput.classList.remove('is-invalid');
                    if (usernameFeedback && usernameFeedback.classList.contains('invalid-feedback')) {
                        usernameFeedback.textContent = '';
                    }
                }
                
                if (username) {
                    // Validate username: không được có dấu tiếng Việt và không được có dấu cách
                    // Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-)
                    const usernameRegex = /^[a-zA-Z0-9_-]+$/;
                    if (!usernameRegex.test(username)) {
                        usernameInput.classList.add('is-invalid');
                        if (usernameFeedback && usernameFeedback.classList.contains('invalid-feedback')) {
                            usernameFeedback.textContent = 'Tên đăng nhập không được chứa dấu tiếng Việt và không được có dấu cách. Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-).';
                        }
                    }
                }
            });

            // Validate before submit
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const username = usernameInput.value.trim();
                usernameInput.classList.remove('is-invalid');
                if (usernameFeedback && usernameFeedback.classList.contains('invalid-feedback')) {
                    usernameFeedback.textContent = '';
                }

                if (username) {
                    const usernameRegex = /^[a-zA-Z0-9_-]+$/;
                    if (!usernameRegex.test(username)) {
                        e.preventDefault();
                        usernameInput.classList.add('is-invalid');
                        if (usernameFeedback && usernameFeedback.classList.contains('invalid-feedback')) {
                            usernameFeedback.textContent = 'Tên đăng nhập không được chứa dấu tiếng Việt và không được có dấu cách. Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-).';
                        }
                        usernameInput.focus();
                        return false;
                    }
                }
            });
        });
        function detectDeviceType() {
    const ua = navigator.userAgent.toLowerCase();

    if (ua.includes("mobile") || ua.includes("android") || ua.includes("iphone") || ua.includes("ipad")) {
        return "mobile";
    }
    return "pc";
}

document.getElementById('device_type').value = detectDeviceType();

    </script>
</body>
</html>