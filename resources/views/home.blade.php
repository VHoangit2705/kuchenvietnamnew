@extends('layout.layout')

@section('content')
    <div class="bg-light d-flex justify-content-center align-items-center" style="height: calc(100vh - 60px - 60px);">
        <div class="d-flex flex-wrap gap-4 justify-content-center align-items-center text-center">
            <a href="{{ route('warranty.kuchen') }}" class="warranty-card text-decoration-none text-dark">
                <img src="{{ asset('imgs/logokuchen.png') }}" alt="Bảo hành KUCHEN" style="width: 100px;">
                <h5 class="fw-bold mt-2">Bảo hành KUCHEN</h5>
            </a>
            <a href="{{ route('warranty.hurom') }}" class="warranty-card text-decoration-none text-dark">
                <img src="{{ asset('imgs/hurom.webp') }}" alt="Bảo hành HUROM" style="width: 210px;">
                <h5 class="fw-bold mt-2">Bảo hành HUROM</h5>
            </a>
        </div>
    </div>
    {{-- <button type="button" class="btn btn-primary" id="CapNhatHanBaoHanh"> Cập nhật</button> --}}

    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endsection