const maxPhotos = 4;
const maxPhotoSize = 1024 * 1024;
const maxVideoSize = 10 * 1024 * 1024;

const canvas = document.getElementById('canvas');
const takePhotoBtn = document.getElementById('takePhoto');
const photoList = document.getElementById('photoList');
const preview = document.getElementById('preview');
const startBtn = document.getElementById('startVideo');
const stopBtn = document.getElementById('stopVideo');
const saveBtn = document.getElementById('confirmSave');
const videoOutput = document.getElementById('videoOutput');
const modalImage = document.getElementById('modalImage');
const id = (window.takePhotoId || '');

let stream;
let mediaRecorder;
let recordedChunks = [];
let recordingSize = 0;

window.addEventListener('DOMContentLoaded', async () => {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: 1280, height: 720 },
            audio: true
        });
        preview.srcObject = stream;
        await preview.play();
    } catch (err) {
        console.error("Không thể truy cập camera:", err);
        if (err.name === 'NotAllowedError') {
            alert("Bạn đã từ chối quyền truy cập camera. Hãy cho phép để sử dụng chức năng này.");
        } else {
            alert("Lỗi khi mở camera: " + err.message);
        }
    }
});

takePhotoBtn.onclick = () => {
    if (!stream) {
        alert("Camera chưa sẵn sàng!");
        return;
    }
    if (photoList.querySelectorAll('img').length >= maxPhotos) return;
    const ctx = canvas.getContext('2d');
    canvas.width = preview.videoWidth;
    canvas.height = preview.videoHeight;
    ctx.drawImage(preview, 0, 0);
    photoList.appendChild(createPhotoElement(canvas.toDataURL('image/jpeg', 0.7)));
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
            if (typeof Notification === 'function') {
                Notification('warning', `Ảnh "${file.name}" không hợp lệ hoặc vượt 1MB.`, 1500, true);
            } else {
                alert(`Ảnh "${file.name}" không hợp lệ hoặc vượt 1MB.`);
            }
            return;
        }
        const reader = new FileReader();
        reader.onload = e => photoList.appendChild(createPhotoElement(e.target.result));
        reader.readAsDataURL(file);
    });
    this.value = '';
});

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

stopBtn.onclick = () => {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
    stopBtn.classList.add('d-none');
    startBtn.classList.remove('d-none');
    saveBtn.classList.remove('d-none');
};

document.getElementById('uploadVideo').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
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
    videoOutput.pause();
    videoOutput.removeAttribute('src');
    videoOutput.load();
    videoOutput.src = tempURL;
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
            Swal.fire('Ảnh quá lớn', `Ảnh ${i + 1} vượt quá 1MB.`, 'error');
            return;
        }
        formData.append(`photos[]`, blob, `photo${i + 1}.jpg`);
    }
    if (recordedChunks.length > 0) {
        const fileLike = recordedChunks[0] instanceof Blob ? recordedChunks[0] : new Blob(recordedChunks, { type: 'video/webm' });
        formData.append('video', fileLike, 'video.webm');
    }
    formData.append('id', id);
    formData.append('_token', (window.csrfToken || ''));

    Swal.fire({
        title: 'Đang lưu...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const res = await fetch((window.warrantyStoreMediaRoute || ''), {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            Swal.fire('Thành công', 'Lưu thành công!', 'success');
            setTimeout(function() {
                const brand = (window.brand || '');
                window.location.href = (brand === 'kuchen') ? (window.routeKuchen || '/') : (window.routeHurom || '/');
            }, 1000);
        } else {
            Swal.fire('Lỗi', json.message || 'Lưu thất bại.', 'error');
        }
    } catch (err) {
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


