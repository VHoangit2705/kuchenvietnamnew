/**
 * JavaScript for detailproblem page
 */

$(document).ready(function() {
    $('#phanhoi').on('input', function () {
        $(this).css('border', '');
        this.setCustomValidity('');
    });
    goBack();
    setupImageModal();
});

function goBack() {
    $('#back').on('click', function(){
        window.location.href = window.listProblemRoute || '';
    });
}

function updateStatus(id) {
    var solution = $('#phanhoi').val().trim();
    if (solution === '') {
        $('#phanhoi')[0].setCustomValidity('Vui lòng nhập phản hồi.');
        $('#phanhoi')[0].reportValidity();
        return;
    }

    showSwalConfirm(
        'Xác nhận cập nhật trạng thái',
        'Bạn có chắc chắn muốn cập nhật bản ghi này không?',
        'Cập nhật',
        'Hủy bỏ'
    ).then((result) => {
        if (result.isConfirmed) {
            performAjaxRequest({
                url: window.updateStatusRoute || '',
                method: 'GET',
                data: {
                    id: id,
                    solution: solution
                },
                swalSuccess: {
                    icon: 'success',
                    title: 'Cập nhật thành công!',
                    text: '',
                    options: {
                        timer: 2000
                    }
                },
                onSuccess: function(response) {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            });
        }
    });
}

// Image modal functionality
let rotationAngle = 0;
let zoomScale = 1;
let isDragging = false;
let startX, startY, currentX = 0, currentY = 0;

function setupImageModal() {
    // Xoay ảnh
    $('#rotateButton').on('click', function() {
        rotationAngle += 90;
        updateTransform();
    });
    
    // Zoom bằng cuộn chuột
    $('#modalImage').on('wheel', function(e) {
        e.preventDefault();
        const delta = e.originalEvent.deltaY < 0 ? 0.1 : -0.1;
        zoomScale = Math.max(0.2, zoomScale + delta);
    
        // Đặt lại vị trí nếu ảnh nhỏ lại
        if (zoomScale <= 1) {
            currentX = 0;
            currentY = 0;
        }
    
        updateTransform();
    });
    
    // Kéo ảnh nếu đã được zoom
    $('#modalImage').on('mousedown', function(e) {
        if (zoomScale > 1 && e.which === 1) {
            isDragging = true;
            startX = e.pageX - currentX;
            startY = e.pageY - currentY;
            $(this).css('cursor', 'grabbing');
        }
    });
    
    // Theo dõi sự kiện di chuyển chuột khi kéo
    $(document).on('mousemove', function(e) {
        if (isDragging) {
            currentX = e.pageX - startX;
            currentY = e.pageY - startY;
            updateTransform();
        }
    });
    
    // Dừng kéo ảnh khi nhả chuột
    $(document).on('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            $('#modalImage').css('cursor', 'grab');
        }
    });
}

// Hàm hiển thị ảnh trong modal
function showImage(src) {
    $('#modalImage').attr('src', src);
    rotationAngle = 0;
    zoomScale = 1;
    currentX = 0;
    currentY = 0;
    $('#modalImage').css({
        'transform': 'rotate(0deg) scale(1)',
        'cursor': 'grab',
        'transition': 'none'
    });
}

// Hàm cập nhật transform của ảnh
function updateTransform() {
    $('#modalImage').css('transform', `translate(${currentX}px, ${currentY}px) rotate(${rotationAngle}deg) scale(${zoomScale})`);
}

