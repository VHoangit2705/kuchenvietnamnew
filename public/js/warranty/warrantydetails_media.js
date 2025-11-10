function showImages(imageString) {
    const carouselInner = document.getElementById("carouselInner");
    carouselInner.innerHTML = "";
    
    const baseUrl = "https://kuchenvietnam.vn/kuchen/trungtambaohanhs/storage/app/public";
    const imageList = imageString.split(',');
    
    if (!imageString || imageList.length === 0) {
        carouselInner.innerHTML = "<div class='text-center'>Không có ảnh nào để hiển thị.</div>";
    } else {
        imageList.forEach((imgUrl, index) => {
            let finalUrl = imgUrl.trim();
            let isGoogleDrive = false;
            if (!finalUrl.startsWith("http://") && !finalUrl.startsWith("https://")) {
                if (finalUrl.startsWith("uploads/")) {
                    finalUrl = baseUrl + "/storage/" + finalUrl;
                } else if (finalUrl.startsWith("/storage/")) {
                    finalUrl = baseUrl + finalUrl;
                } else {
                    finalUrl = baseUrl + "/" + finalUrl;
                }
            }

            const match = finalUrl.match(/\/file\/d\/([^/]+)\//);
            if (match) {
                const fileId = match[1];
                finalUrl = `https://drive.google.com/file/d/${fileId}/preview`;
                isGoogleDrive = true;
            }
        
            const item = document.createElement("div");
            item.classList.add("carousel-item");
            if (index === 0) item.classList.add("active");

            if (isGoogleDrive) {
                const iframe = document.createElement("iframe");
                iframe.src = finalUrl;
                iframe.classList.add("d-block", "w-100");
                iframe.style.width = "100%";
                iframe.style.height = "600px";
                iframe.style.border = "none";
                iframe.allow = "fullscreen";
                item.appendChild(iframe);
            } else {
                const img = document.createElement("img");
                img.src = finalUrl;
                img.classList.add("d-block", "w-100", "fullscreen-img");
                img.style.maxHeight = "600px";
                img.style.objectFit = "contain";
                img.addEventListener('click', () => {
                    if (img.requestFullscreen) img.requestFullscreen();
                    else if (img.webkitRequestFullscreen) img.webkitRequestFullscreen();
                    else if (img.msRequestFullscreen) img.msRequestFullscreen();
                });
                item.appendChild(img);
            }
            carouselInner.appendChild(item);
        });
    }

    const modal = new bootstrap.Modal(document.getElementById("imageModal"));
    $('#savePhotoBtn').addClass('d-none');
    modal.show();
}

function showVideo(videoPath) {
    const modalBody = document.getElementById('modalVideoBody');
    modalBody.innerHTML = '';

    if(!videoPath){
        modalBody.innerHTML = "<div class='text-center'>Không video nào để hiển thị.</div>";
        const modal = new bootstrap.Modal(document.getElementById('videoModal'));
        modal.show();
        return;
    }
    
    if (videoPath.includes('drive.google.com')) {
        const fileId = extractDriveFileId(videoPath);
        if (fileId) {
            const iframe = document.createElement('iframe');
            iframe.src = `https://drive.google.com/file/d/${fileId}/preview`;
            iframe.width = '100%';
            iframe.height = '480';
            iframe.allow = 'autoplay';
            iframe.allowFullscreen = true;
            iframe.frameBorder = '0';
            modalBody.appendChild(iframe);
        } else {
            modalBody.innerHTML = 'Không thể phát video từ liên kết Google Drive này.';
        }
    } else {
        const video = document.createElement('video');
        video.src = "https://kuchenvietnam.vn/kuchen/trungtambaohanhs/storage/app/public/" + videoPath;
        video.controls = true;
        video.className = 'w-100';
        video.style.maxHeight = '80vh';
        modalBody.appendChild(video);
    }

    const modal = new bootstrap.Modal(document.getElementById('videoModal'));
    modal.show();
}

function extractDriveFileId(url) {
    const match = url.match(/\/file\/d\/(.*?)\/view/);
    return match ? match[1] : null;
}

$('#videoModal').on('hidden.bs.modal', function () {
    const modalBody = document.getElementById('modalVideoBody');
    $('#saveVideoBtn').addClass('d-none');
    modalBody.innerHTML = '';
});

// Upload Ảnh
var PhotoUploadId = null;
function triggerPhotoUpload(button) {
    PhotoUploadId = button.getAttribute('data-id');
    document.getElementById('photoUpload').click();
}

let selectedPhotos = [];
function handlePhotoload(input) {
    const files = input.files;
    if (!files || files.length === 0) return;

    const maxSize = 5 * 1024 * 1024;
    selectedPhotos = [];

    const $carouselInner = $('#carouselInner');
    $carouselInner.empty();

    let validIndex = 0;

    Array.from(files).forEach((file) => {
        if (file.size > maxSize) {
            Swal.fire('Cảnh báo', `Ảnh "${file.name}" vượt quá 5MB`, 'warning');
            return;
        }

        selectedPhotos.push(file);

        const reader = new FileReader();
        reader.onload = function (e) {
            const isActive = validIndex === 0 ? 'active' : '';
            const carouselItem = `
                <div class="carousel-item ${isActive}">
                    <img src="${e.target.result}" class="d-block w-100" style="object-fit: contain; max-height: 500px;" alt="Ảnh ${validIndex + 1}">
                </div>
            `;
            $carouselInner.append(carouselItem);
            validIndex++;
        };
        reader.readAsDataURL(file);
    });

    if (selectedPhotos.length > 0) {
        $('#savePhotoBtn').removeClass('d-none');
    } else {
        $('#savePhotoBtn').addClass('d-none');
        Swal.fire('Cảnh báo', 'Không có ảnh nào hợp lệ (dưới 5MB) được chọn', 'warning', false);
    }
}

function UdatePhotos() {
    if (!selectedPhotos.length) return;
    const formData = new FormData();
    formData.append('_token', (window.csrfToken || ''));
    formData.append('id', (window.currentWarrantyId || ''));
    selectedPhotos.forEach((file) => {
        formData.append('photos[]', file);
    });
    OpenWaitBox();
    $.ajax({
        url: (window.photoUploadRoute || ''),
        type: 'POST',
        processData: false,
        contentType: false,
        data: formData,
        success: function (res) {
            CloseWaitBox();
            if (res.success) {
                Swal.fire('Thành công', 'Lưu thành công!', 'success');
                location.reload();
            } else {
                alert('Lỗi: ' + res.message);
            }
        },
        error: function (err) {
            CloseWaitBox();
            console.error(err);
            alert('Đã xảy ra lỗi khi upload ảnh.');
        }
    });
}

// Upload Video
var videoUploadId = null;
function triggerVideoUpload(button) {
    videoUploadId = button.getAttribute('data-id');
    document.getElementById('videoUpload').click();
}

function handleVideoUpload(input) {
    const file = input.files[0];
    if (!file || !file.type.startsWith('video/')) {
        Swal.fire('Lỗi', 'Vui lòng chọn một tệp video hợp lệ.', 'error');
        return;
    }
    const maxSize = 50 * 1024 * 1024;
    if (file.size > maxSize) {
        Swal.fire('Cảnh báo', `Video "${file.name}" vượt quá 50MB và sẽ không được tải lên.`, 'warning');
        input.value = '';
        return;
    }
    const url = URL.createObjectURL(file);
    const videoElement = document.createElement('video');
    videoElement.setAttribute('controls', true);
    videoElement.setAttribute('style', 'max-width: 100%; height: 80vh;');
    videoElement.src = url;
    const modalBody = document.getElementById('modalVideoBody');
    modalBody.innerHTML = '';
    modalBody.appendChild(videoElement);
    $('#saveVideoBtn').removeClass('d-none');
}

function UdateVideo() {
    var $btn = $('#saveVideoBtn');
    var id = $btn.data('id');
    var file = $('#videoUpload')[0].files[0];
    const formData = new FormData();
    formData.append('id', id);
    formData.append('_token', (window.csrfToken || ''));
    formData.append('video', file);
    OpenWaitBox();
    $.ajax({
        url: (window.videoUploadRoute || ''),
        method: 'POST',
        processData: false,
        contentType: false,
        data: formData,
        success: function(response) {
            CloseWaitBox();
            if (response.success) {
                Swal.fire('Thành công', 'Lưu thành công!', 'success');
                location.reload();
            } else {
                alert("Lưu thất bại: " + response.message);
            }
        },
        error: function(xhr) {
            CloseWaitBox();
            console.error(xhr);
            alert("Có lỗi xảy ra khi gửi request.");
        }
    });
}

