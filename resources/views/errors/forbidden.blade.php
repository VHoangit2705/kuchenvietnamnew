{{-- resources/views/errors/403.blade.php --}}
@extends('layout.layout')

@section('title', '403 Forbidden')

@section('content')
<div class="container" style="min-height:70vh; display:flex; align-items:center; justify-content:center;">
    <div class="text-center">
        <h1 style="font-size:96px; margin:0;">403</h1>
        <p style="font-size:20px; margin-top:0.5rem;">Forbidden — Bạn không có quyền truy cập trang này.</p>

        @isset($message)
        <div class="alert alert-info mt-3">{{ $message }}</div>
        @endisset

        <div class="mt-4">
            <a href="{{ url('/') }}" class="btn btn-primary">Về trang chủ</a>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Quay lại</a>
        </div>

        <p class="text-muted mt-3" style="font-size:13px;">
            Nếu bạn cho rằng đây là lỗi, hãy liên hệ admin.
        </p>
    </div>
</div>
@endsection