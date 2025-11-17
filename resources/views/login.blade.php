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
                <input type="hidden" name="machine_id" id="machine_id">
                <input type="hidden" name="browser_info" id="browser_info">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required autofocus>
                    @error('username')
                        <small class="text-danger">{{ $message }}</small>
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
        });
    </script>
</body>
</html>