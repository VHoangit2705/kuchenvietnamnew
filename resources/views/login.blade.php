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
                    <label for="password" class="form-label">Nhập mật khẩu để đăng nhập</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    @if ($errors->has('password'))
                        <small class="text-danger">{{ $errors->first('password') }}</small>
                    @endif
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
        });
    </script>
</body>
</html>