@extends('layout.layout')

@section('content')
<style>
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-1 {
        background-color: #198754;
        color: #fff;
    }
    .status-0 {
        background-color: #dc3545;
        color: #fff;
    }
    .verified-badge {
        background-color: #0d6efd;
        color: #fff;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
    }
    .info-box {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
</style>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-person-circle me-2"></i>Chi tiết tài khoản đại lý
            </h4>
            <div>
                <a href="{{ route('useragency.edit', $user->id) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i>Chỉnh sửa
                </a>
                <a href="{{ route('useragency.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Thông tin tài khoản -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Thông tin tài khoản</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Số điện thoại (Username)</label>
                            <div class="form-control-plaintext">
                                <strong>{{ $user->username }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Họ tên</label>
                            <div class="form-control-plaintext">
                                <strong>{{ $user->fullname }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Trạng thái</label>
                            <div>
                                <span class="status-badge status-{{ $user->status }}">
                                    {{ $user->status == 1 ? 'Kích hoạt' : 'Vô hiệu hóa' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Xác minh</label>
                            <div>
                                @if($user->isVerified())
                                    <span class="verified-badge">
                                        <i class="bi bi-check-circle me-1"></i>Đã xác minh
                                    </span>
                                @else
                                    <span class="text-muted">Chưa xác minh</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Ngày tạo</label>
                            <div class="form-control-plaintext">
                                @if($user->created_at)
                                    {{ $user->created_at->format('d/m/Y H:i:s') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Ngày xác minh</label>
                            <div class="form-control-plaintext">
                                @if($user->phone_verified_at)
                                    {{ $user->phone_verified_at->format('d/m/Y H:i:s') }}
                                @else
                                    <span class="text-muted">Chưa xác minh</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($user->updated_at)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Cập nhật lần cuối</label>
                            <div class="form-control-plaintext">
                                {{ $user->updated_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Thông tin đại lý -->
            @if($user->agency)
            <div class="row mb-4">
                <!-- Thông tin đại lý (bên trái) -->
                <div class="col-md-6">
                    <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Thông tin đại lý</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Tên đại lý</label>
                            <div class="form-control-plaintext">
                                <strong>{{ $user->agency->name ?? '-' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Số điện thoại đại lý</label>
                            <div class="form-control-plaintext">
                                {{ $user->agency->phone ?? '-' }}
                            </div>
                        </div>
                    </div>

                    @if($user->agency->cccd)
                    <div class="row mb-3">
                                <div class="col-md-12">
                            <label class="form-label text-muted">CCCD đại lý</label>
                            <div class="form-control-plaintext">
                                {{ $user->agency->cccd }}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($user->agency->address)
                            <div class="row mb-0">
                        <div class="col-md-12">
                            <label class="form-label text-muted">Địa chỉ đại lý</label>
                            <div class="form-control-plaintext">
                                {{ $user->agency->address }}
                            </div>
                        </div>
                    </div>
                    @endif
                        </div>
                    </div>
                </div>

                <!-- Thông tin thanh toán (bên phải) -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Thông tin thanh toán</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-5">
                                        <span class="text-muted">Chủ tài khoản:</span>
                                    </div>
                                    <div class="col-7">
                                        <strong>{{ $user->agency->bank_account ?? '-' }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-5">
                                        <span class="text-muted">Số tài khoản:</span>
                                    </div>
                                    <div class="col-7">
                                        {{ $user->agency->sotaikhoan ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-5">
                                        <span class="text-muted">Ngân hàng:</span>
                                    </div>
                                    <div class="col-7">
                                        {{ $user->agency->bank_name_agency ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <div class="row">
                                    <div class="col-5">
                                        <span class="text-muted">Chi nhánh:</span>
                                    </div>
                                    <div class="col-7">
                                        {{ $user->agency->chinhanh ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="info-box">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Tài khoản này chưa được gán cho đại lý nào.</strong> Bạn có thể chỉnh sửa để gán đại lý.
            </div>
            @endif

            <!-- Form đặt lại mật khẩu -->
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Đặt lại mật khẩu</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('useragency.reset-password', $user->id) }}" id="resetPasswordForm">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror" 
                                    placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" required>
                                @error('new_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" name="new_password_confirmation" class="form-control" 
                                    placeholder="Nhập lại mật khẩu mới" required>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Lưu ý:</strong> Sau khi đặt lại mật khẩu, người dùng sẽ cần đăng nhập lại với mật khẩu mới.
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-1"></i>Đặt lại mật khẩu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    
    form.addEventListener('submit', function(e) {
        const password = form.querySelector('input[name="new_password"]').value;
        const passwordConfirm = form.querySelector('input[name="new_password_confirmation"]').value;
        
        if (password.length < 6) {
            e.preventDefault();
            toastr.error('Mật khẩu phải có ít nhất 6 ký tự!');
            return false;
        }
        
        if (password !== passwordConfirm) {
            e.preventDefault();
            toastr.error('Mật khẩu xác nhận không khớp!');
            return false;
        }
        
        if (!confirm('Bạn có chắc chắn muốn đặt lại mật khẩu cho tài khoản này?')) {
            e.preventDefault();
            return false;
        }
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});
</script>
@endsection
