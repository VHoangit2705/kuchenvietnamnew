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

    const maxPhotos = 4;
    const maxPhotoSize = 5 * 1024 * 1024;
    const maxVideoSize = 10 * 1024 * 1024;
    const maxVideoDuration = 30;

    const canvas = document.getElementById('canvas');
    const takePhotoBtn = document.getElementById('takePhoto');
    const photoList = document.getElementById('photoList');
    const preview = document.getElementById('preview');
    const startBtn = document.getElementById('startVideo');
    const stopBtn = document.getElementById('stopVideo');
    const saveBtn = document.getElementById('confirmSave');
    const videoOutput = document.getElementById('videoOutput');
    const id = document.getElementById('id').value;
    const modalImage = document.getElementById('modalImage');

    let stream;
    let photoCount = 0;
    let mediaRecorder;
    let recordedChunks = [];
    let recordingSize = 0;

    // Mở camera khi vào trang
    window.addEventListener('DOMContentLoaded', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: 1280, height: 720 },
                audio: true
            });
            preview.srcObject = stream;
            await preview.play();
            console.log("Camera đã mở.");
        } catch (err) {
            console.error("Không thể truy cập camera:", err);
            if (err.name === 'NotAllowedError') {
                alert("Bạn đã từ chối quyền truy cập camera. Hãy cho phép để sử dụng chức năng này.");
            } else {
                alert("Lỗi khi mở camera: " + err.message);
            }
        }
    });

    // Chụp ảnh
    takePhotoBtn.onclick = () => {
        if (!stream) {
            alert("Camera chưa sẵn sàng!");
            return;
        }
        if (photoCount >= maxPhotos) {
            return;
        }
        if (photoList.querySelectorAll('img').length >= maxPhotos) return;
            const ctx = canvas.getContext('2d');
            canvas.width = preview.videoWidth;
            canvas.height = preview.videoHeight;
            ctx.drawImage(preview, 0, 0);
            photoList.appendChild(createPhotoElement(canvas.toDataURL('image/jpeg', 0.7))
        );
    };

    function createPhotoElement(src) {
        const wrapper = $('<div>', { class: 'col-6 position-relative' });
        const img = $('<img>', { src, class: 'rounded border', style: 'width: 100%; object-fit: cover; cursor: pointer;' });
        const deleteBtn = $('<button>', {
            text: '×', class: 'btn btn-sm btn-danger position-absolute top-0 end-0 m-1',
            click: () => wrapper.remove()
        });
        img.click(() => {
            $('#modalImage').attr('src', src);
            new bootstrap.Modal($('#photoModal')).show();
        });
        wrapper.append(img, deleteBtn);
        return wrapper[0];
    }

    $('#uploadPhotos').on('change', function () {
        const files = Array.from(this.files).slice(0, maxPhotos - photoList.querySelectorAll('img').length);
        files.forEach(file => {
            if (!file.type.startsWith('image/') || file.size > maxPhotoSize) {
                Notification('warning', `Ảnh "${file.name}" không hợp lệ hoặc vượt 5MB.`, 1500, true);
                return;
            }
            const reader = new FileReader();
            reader.onload = e => photoList.appendChild(createPhotoElement(e.target.result));
            reader.readAsDataURL(file);
        });
        this.value = '';
    });

    // Fallback nếu MediaRecorder không hỗ trợ
    const fallbackInput = document.createElement('input');
    fallbackInput.type = 'file';
    fallbackInput.accept = 'video/*';
    fallbackInput.capture = 'environment';

    fallbackInput.onchange = function (e) {
        const file = e.target.files[0];
        if (file) {
            const url = URL.createObjectURL(file);
            videoOutput.src = url;
            videoOutput.play();
        }
    };

    // Bắt đầu quay video
    startBtn.onclick = async () => {
        if (!stream) {
            alert("Camera chưa sẵn sàng để quay video.");
            return;
        }
        recordedChunks = [];
        recordingSize = 0;

        let mimeType = '';
        if (MediaRecorder.isTypeSupported('video/webm')) {
            mimeType = 'video/webm';
        } else if (MediaRecorder.isTypeSupported('video/mp4')) {
            mimeType = 'video/mp4';
        }

        mediaRecorder = new MediaRecorder(stream, {
            mimeType: mimeType,
            bitsPerSecond: 500_000
        });

        mediaRecorder.ondataavailable = e => {
            if (e.data.size > 0) {
                recordedChunks.push(e.data);
                recordingSize += e.data.size;

                // Tự dừng khi vượt quá 10MB
                if (recordingSize > maxVideoSize) {
                    mediaRecorder.stop();
                }
            }
        };

        mediaRecorder.onstop = () => {
            const blob = new Blob(recordedChunks, { type: mimeType });
            const url = URL.createObjectURL(blob);
            videoOutput.src = url;
        };

        mediaRecorder.start();

        startBtn.classList.add('d-none');
        stopBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
    };

    // Dừng quay
    stopBtn.onclick = () => {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
        }
        stopBtn.classList.add('d-none');
        startBtn.classList.remove('d-none');
        saveBtn.classList.remove('d-none');
    };
    // Upload video từ máy
    const uploadInput = document.getElementById('uploadVideo');
    uploadInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Kiểm tra định dạng và dung lượng
        if (!file.type.startsWith('video/')) {
            alert('Tệp không phải video!');
            this.value = '';
            return;
        }

        if (file.size > maxVideoSize) {
            alert('Video vượt quá dung lượng 10MB!');
            this.value = '';
            return;
        }

        const tempURL = URL.createObjectURL(file);

        // Đặt lại video
        videoOutput.pause();
        videoOutput.removeAttribute('src'); // clear
        videoOutput.load();

        videoOutput.src = tempURL;
        // Cho phép xử lý lưu lại video nếu cần
        recordedChunks = [file];
    });

    $(document).on('click', '#confirmSave', async function() {
        const images = [...photoList.querySelectorAll('img')].map(img => img.src);

        if (images.length === 0 && recordedChunks.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Bạn cần thêm ít nhất 1 ảnh hoặc 1 video!',
                confirmButtonText: 'Đã hiểu'
            });
            return;
        }

        const result = await Swal.fire({
            title: 'Xác nhận lưu',
            text: "Bạn có chắc chắn muốn lưu ảnh và video không?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Lưu',
            cancelButtonText: 'Huỷ'
        });

        if (!result.isConfirmed) return;

        const formData = new FormData();

        for (let i = 0; i < images.length; i++) {
            const blob = dataURLtoBlob(images[i]);
            if (blob.size > maxPhotoSize) {
             Swal.fire('Ảnh quá lớn', `Ảnh ${i + 1} vượt quá 5MB.`, 'error');
              return;
        }
            formData.append(`photos[]`, blob, `photo${i + 1}.jpg`);
        }

        if (recordedChunks.length > 0) {
            const videoBlob = new Blob(recordedChunks, {
                type: 'video/webm'
            });
            formData.append('video', videoBlob, 'video.webm');
        }

        formData.append('id', id);
        // formData.append('_token', '{{ csrf_token() }}');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        Swal.fire({
            title: 'Đang lưu...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch("{{ route('warranty.storemedia') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });
            const json = await res.json();
            if (json.success) {
                Swal.fire('Thành công', 'Lưu thành công!', 'success');
                setTimeout(function() {
                    let brand = "{{ session('brand') }}";
                    window.location.href = (brand === 'kuchen') ? "{{ route('warranty.kuchen') }}" : "{{ route('warranty.hurom') }}";
                }, 1000);
            } else {
                debugger;
                Swal.fire('Lỗi', json.message || 'Lưu thất bại.', 'error');
            }
        } catch (err) {
            debugger;
            Swal.fire('Lỗi', 'Lỗi khi gửi dữ liệu: ' + err.message, 'error');
        }
    });

    function dataURLtoBlob(dataURL) {
        const [meta, base64] = dataURL.split(',');
        const mime = meta.match(/:(.*?);/)[1];
        const bin = atob(base64);
        const arr = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
        return new Blob([arr], { type: mime });
    }
</script>
@endsection