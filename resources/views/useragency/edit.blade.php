@extends('layout.layout')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-pencil-square me-2"></i>Chỉnh sửa tài khoản đại lý
            </h4>
            <a href="{{ route('useragency.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('useragency.update', $user->id) }}" id="editForm">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại (Username) <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" 
                            value="{{ old('username', $user->username) }}" placeholder="Nhập số điện thoại" required>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Số điện thoại sẽ được dùng làm tên đăng nhập</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control @error('fullname') is-invalid @enderror" 
                            value="{{ old('fullname', $user->fullname) }}" placeholder="Nhập họ tên đầy đủ" required>
                        @error('fullname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                            placeholder="Để trống nếu không đổi mật khẩu (tối thiểu 6 ký tự)">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Chỉ nhập nếu muốn thay đổi mật khẩu</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" name="password_confirmation" class="form-control" 
                            placeholder="Nhập lại mật khẩu mới">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Trạng thái tài khoản <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="1" {{ old('status', $user->status) == '1' ? 'selected' : '' }}>Kích hoạt</option>
                            <option value="2" {{ old('status', $user->status) == '2' ? 'selected' : '' }}>Vô hiệu hóa</option>
                            <option value="0" {{ old('status', $user->status) == '0' ? 'selected' : '' }}>Chưa xác minh</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if($user->agency)
                <hr>
                <h5 class="mb-3"><i class="bi bi-building me-2"></i>Thông tin cá nhân của đại lý</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tên đại lý</label>
                        <input type="text" name="agency_name"
                               class="form-control"
                               value="{{ old('agency_name', $user->agency->name) }}"
                               placeholder="Nhập tên đại lý">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại đại lý</label>
                        <input type="text" name="agency_phone"
                               class="form-control"
                               value="{{ old('agency_phone', $user->agency->phone) }}"
                               placeholder="Nhập số điện thoại đại lý">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Địa chỉ đại lý</label>
                        <input type="text" name="agency_address"
                               class="form-control"
                               value="{{ old('agency_address', $user->agency->address) }}"
                               placeholder="Nhập địa chỉ đại lý">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Ngân hàng</label>
                        <input type="text" name="agency_bank_name"
                               class="form-control"
                               value="{{ old('agency_bank_name', $user->agency->bank_name_agency) }}"
                               placeholder="Nhập tên ngân hàng">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Số tài khoản</label>
                        <input type="text" name="agency_sotaikhoan"
                               class="form-control"
                               value="{{ old('agency_sotaikhoan', $user->agency->sotaikhoan) }}"
                               placeholder="Nhập số tài khoản">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Chi nhánh</label>
                        <input type="text" name="agency_chinhanh"
                               class="form-control"
                               value="{{ old('agency_chinhanh', $user->agency->chinhanh) }}"
                               placeholder="Nhập chi nhánh ngân hàng">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Căn cước công dân</label>
                        <input type="text" name="agency_cccd"
                               class="form-control"
                               value="{{ old('agency_cccd', $user->agency->cccd) }}"
                               placeholder="Nhập số CCCD">
                    </div>
                </div>
                @endif

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Lưu ý:</strong> Nếu không nhập mật khẩu mới, mật khẩu cũ sẽ được giữ nguyên.
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('useragency.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Hủy
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    
    form.addEventListener('submit', function(e) {
        const password = form.querySelector('input[name="password"]').value;
        const passwordConfirm = form.querySelector('input[name="password_confirmation"]').value;
        
        if (password && password.length < 6) {
            e.preventDefault();
            toastr.error('Mật khẩu phải có ít nhất 6 ký tự!');
            return false;
        }
        
        if (password && password !== passwordConfirm) {
            e.preventDefault();
            toastr.error('Mật khẩu xác nhận không khớp!');
            return false;
        }
    });
});
</script>
@endsection
