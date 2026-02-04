@extends('layout.layout')

@section('content')
<div class="container-fluid mt-2 mb-2">
    <div class="row g-4">
        <div class="col-12 col-md-12">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white position-relative">
                    <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" onclick="window.history.back()" title="Quay lại"
                        style="height: 15px; filter: brightness(0) invert(1); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                    <h5 class="mb-0 ms-5">Thông tin phiếu nhập</h5>
                </div>
                <div class="card-body">
                    <p><strong>Tên sản phẩm:</strong> {{ $item->product }}</p>
                    <div class="d-flex justify-content-between">
                        <p><strong>Số lượng:</strong> {{ $item->quantity }}</p>
                        <a href="{{ route('warrantycard.temdowload', $item->id) }}" 
                           class="btn btn-primary" 
                           id="downloadBtn"
                           data-download-url="{{ route('warrantycard.temdowload', ['id' => $item->id]) }}">Dowload file</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid" id="previewContainer" 
     data-tem-url="{{ route('warrantycard.tem', ['id' => $item->id]) }}"
     data-item-id="{{ $item->id }}"
     data-pdf-base-url="https://kuchenvietnam.vn/kuchen/trungtambaohanhs/storage/app/public/pdfs"></div>
<script src="{{ asset('public/js/printwarranty/details.js') }}"></script>
@endsection