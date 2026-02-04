@extends('layout.public_layout')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5 text-center">
                    <div class="mb-4 text-primary">
                        <i class="bi bi-shield-lock display-1"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Tài liệu được bảo vệ</h3>
                    <p class="text-muted mb-4">Vui lòng nhập mật khẩu để truy cập tài liệu này.</p>
                    
                    @if($errors->any())
                        <div class="alert alert-danger text-start">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('document.share.public_auth', $token) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control form-control-lg text-center" placeholder="Nhập mật khẩu..." required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Xác nhận</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
