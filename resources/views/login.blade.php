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
            <form action="{{ route('login') }}" method="POST" id="loginForm">
                @csrf
                <input type="hidden" name="device_fingerprint" id="device_fingerprint">
                <input type="hidden" name="browser_info" id="browser_info">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required autofocus>
                    <div class="invalid-feedback"></div>
                    @error('username')
                        <div class="text-danger small">{{ $message }}</div>
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
    <script src="{{ asset('js/device-fingerprint.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy device fingerprint và browser info
            const fingerprint = window.DeviceFingerprint.get();
            const browserInfo = window.DeviceFingerprint.getBrowserInfo();
            
            document.getElementById('device_fingerprint').value = fingerprint;
            document.getElementById('browser_info').value = JSON.stringify(browserInfo);

            // Real-time validation for username
            const usernameInput = document.getElementById('username');
            const usernameFeedback = usernameInput.nextElementSibling;
            
            usernameInput.addEventListener('input', function() {
                const username = this.value.trim();
                usernameInput.classList.remove('is-invalid');
                if (usernameFeedback && usernameFeedback.classList.contains('invalid-feedback')) {
                    usernameFeedback.textContent = '';
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
    </script>
</body>
</html>