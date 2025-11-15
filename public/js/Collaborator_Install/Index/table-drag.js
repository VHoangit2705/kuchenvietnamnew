/**
 * Xử lý kéo ngang bảng bằng chuột
 */

function initTableDrag() {
    // Biến trạng thái
    let isMouseDown = false; // Cờ cho biết chuột đang được nhấn
    let isGrabbing = false;  // Cờ cho biết chế độ kéo-cuộn đang được kích hoạt
    let startX, scrollLeft;
    let $dragTarget; // Biến lưu trữ container .table-container đang được kéo

    // 1. Gắn sự kiện 'mousedown' vào #tabContent, 
    //    nhưng chỉ lắng nghe cho phần tử con .table-container
    $('#tabContent').on('mousedown', '.table-container', function(e) {
        // Chỉ xử lý khi nhấn chuột trái
        if (e.button !== 0) return;

        // Nếu người dùng click vào scrollbar thì không làm gì cả
        if (e.target.scrollHeight > e.target.clientHeight && e.offsetX > e.target.clientWidth) {
            return;
        }

        isMouseDown = true;
        isGrabbing = false; // Reset cờ kéo-cuộn
        $dragTarget = $(this); // Lưu lại container này

        // Ghi lại vị trí bắt đầu và vị trí cuộn hiện tại
        startX = e.pageX;
        scrollLeft = $dragTarget.scrollLeft();

        // KHÔNG gọi e.preventDefault() ở đây để cho phép chọn văn bản
    });

    // 2. Gắn sự kiện 'mousemove' vào cả trang (document)
    //    để bạn vẫn kéo được ngay cả khi chuột ra khỏi bảng
    $(document).on('mousemove', function(e) {
        if (!isMouseDown || !$dragTarget) return; // Nếu chưa mousedown, bỏ qua

        const x = e.pageX;
        const walk = x - startX;
        
        // Chỉ kích hoạt chế độ kéo-cuộn nếu di chuyển chuột đủ xa (ví dụ: 5px)
        if (!isGrabbing && Math.abs(walk) > 5) {
            isGrabbing = true; // Kích hoạt chế độ kéo-cuộn
            $dragTarget.addClass('is-grabbing'); // Thêm class để đổi con trỏ
        }

        if (!isGrabbing) return; // Nếu chưa ở chế độ kéo-cuộn, không làm gì cả

        e.preventDefault();
        const scrollDistance = walk * 2; // Nhân 2 để kéo nhạy hơn
        
        // Thiết lập vị trí cuộn mới = vị trí cũ - khoảng cách di chuyển
        $dragTarget.scrollLeft(scrollLeft - scrollDistance);
    });

    // 3. Gắn sự kiện 'mouseup' vào cả trang (document)
    //    để dừng kéo khi nhả chuột ở bất cứ đâu
    $(document).on('mouseup', function(e) {
        isMouseDown = false;
        if (isGrabbing) { // Chỉ reset nếu đã ở chế độ kéo-cuộn
            isGrabbing = false;
            if ($dragTarget) {
                $dragTarget.removeClass('is-grabbing');
            }
            $dragTarget = null; // Xóa mục tiêu
        }
    });

    // 4. Cũng dừng kéo nếu chuột đi ra ngoài cửa sổ trình duyệt
    $(document).on('mouseleave', function() {
        isMouseDown = false;
        if (isGrabbing) { // Chỉ reset nếu đã ở chế độ kéo-cuộn
            isGrabbing = false;
            if ($dragTarget) {
                $dragTarget.removeClass('is-grabbing');
            }
            $dragTarget = null;
        }
    });

    // 5. Thêm class 'can-grab' vào .table-container
    //    Chúng ta cũng dùng ủy quyền sự kiện cho việc này
    $('#tabContent').on('mouseenter', '.table-container', function() {
        const $container = $(this);
        // Kiểm tra xem bảng có thực sự bị tràn không
        if ($container[0].scrollWidth > $container[0].clientWidth) {
            $container.addClass('can-grab');
        }
    }).on('mouseleave', '.table-container', function() {
        $(this).removeClass('can-grab');
    });
}

