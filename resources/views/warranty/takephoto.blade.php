@extends('layout.layout')

@section('content')
    <div class="container mt-4">
        <div class="card-header bg-primary text-white position-relative">
            <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" onclick="window.history.back()" title="Quay lại"
                style="height: 15px; filter: brightness(0) invert(1); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
            <h5 class="mb-0 text-center">Chụp ảnh / Quay video sản phẩm</h5>
        </div>

        <div class="mb-3">
            <video style="height: 50vh;" id="preview" autoplay muted playsinline class="w-100 rounded border"></video>
        </div>

        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-primary" id="takePhoto">📸 Chụp ảnh</button>
            <button class="btn btn-success" id="startVideo">🎥 Quay video</button>
            <button class="btn btn-danger d-none" id="stopVideo">⏹️ Dừng quay</button>
            <button class="btn btn-warning" id="confirmSave">💾 Lưu</button>
        </div>

        <div class="mt-4">
            <input hidden id="id" name="id" value="{{$id}}">

            <h5 class="d-flex align-items-center justify-content-start">
                <span>Ảnh đã chụp: </span>
                <label class="btn btn-sm btn-outline-secondary mb-0 ms-2 only-desktop">
                    📸 Tải ảnh lên
                    <input type="file" id="uploadPhotos" accept="image/*" multiple hidden>
                </label>
            </h5>
            <div id="photoList" class="row g-2"></div>

            <h5 class="mt-4 d-flex align-items-center justify-content-start">
                <span>Video đã quay: </span>
                <label class="btn btn-sm btn-outline-secondary mb-0 ms-2 only-desktop">
                    🎥 Tải video lên
                    <input type="file" id="uploadVideo" accept="video/*" hidden>
                </label>
            </h5>
            <video id="videoOutput" class="w-100 rounded border" style="height: 60vh;" controls></video>
        </div>
    </div>
    <canvas id="canvas" class="d-none"></canvas>
    <!-- Modal hiển thị ảnh toàn màn hình -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-body d-flex justify-content-center align-items-center p-0">
                    <img id="modalImage" src="" class="img-fluid" />
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <style>           
        #photoList img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .btn-close {
            filter: brightness(0) invert(1);
            width: 32px;
            height: 32px;
            opacity: 1;
        }
        /* @media (max-width: 991.98px) {
            .only-desktop {
                display: none !important;
            }
        } */
    </style>
<script>
    window.warrantyStoreMediaRoute = "{{ route('warranty.storemedia') }}";
    window.brand = "{{ session('brand') }}";
    window.routeKuchen = "{{ route('warranty.kuchen') }}";
    window.routeHurom = "{{ route('warranty.hurom') }}";
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    window.takePhotoId = document.getElementById('id').value;
</script>
<script src="{{ asset('js/warranty/takephoto.js') }}"></script>
@endsection