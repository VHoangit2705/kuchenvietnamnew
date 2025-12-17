@extends('layout.layout')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-person-plus me-2"></i>Tạo tài khoản đại lý mới
            </h4>
            <a href="{{ route('useragency.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('useragency.store') }}" id="createForm">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại (Username) <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" 
                            value="{{ old('username') }}" placeholder="Nhập số điện thoại" required>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Số điện thoại sẽ được dùng làm tên đăng nhập</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control @error('fullname') is-invalid @enderror" 
                            value="{{ old('fullname') }}" placeholder="Nhập họ tên đầy đủ" required>
                        @error('fullname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                            placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" 
                            placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Đại lý</label>
                        <select name="agency_id" class="form-select @error('agency_id') is-invalid @enderror">
                            <option value="">-- Chọn đại lý (tùy chọn) --</option>
                            @foreach($agencies as $agency)
                                <option value="{{ $agency->id }}" {{ old('agency_id') == $agency->id ? 'selected' : '' }}>
                                    {{ $agency->name }} - {{ $agency->phone }}
                                </option>
                            @endforeach
                        </select>
                        @error('agency_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Có thể gán đại lý sau</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Kích hoạt</option>
                            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Vô hiệu hóa</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Nếu chọn "Kích hoạt", tài khoản sẽ tự động được đánh dấu là đã xác minh</small>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Lưu ý:</strong> Mật khẩu sẽ được mã hóa bằng MD5. Vui lòng đảm bảo mật khẩu có độ dài tối thiểu 6 ký tự.
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('useragency.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Hủy
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Tạo tài khoản
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createForm');
    
    form.addEventListener('submit', function(e) {
        const password = form.querySelector('input[name="password"]').value;
        const passwordConfirm = form.querySelector('input[name="password_confirmation"]').value;
        
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
    });
});
</script>
@endsection
