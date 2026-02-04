// JavaScript cho tablebody.blade.php
function initPopovers() {
    var existingPopovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    existingPopovers.forEach(function(el) {
        var existingPopover = bootstrap.Popover.getInstance(el);
        if (existingPopover) {
            existingPopover.dispose();
        }
    });
    
    // Lấy tất cả elements có data-bs-toggle="popover" và convert NodeList thành Array
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    
    // Khởi tạo Popover cho từng element
    popoverTriggerList.forEach(function (popoverTriggerEl) {
        const headerClass = popoverTriggerEl.getAttribute('data-popover-header-class');

        // Khởi tạo Popover – không cần gán vào biến
        new bootstrap.Popover(popoverTriggerEl);

        if (headerClass) {
            popoverTriggerEl.addEventListener('shown.bs.popover', function () {
                const popoverId = popoverTriggerEl.getAttribute('aria-describedby');
                if (!popoverId) return;

                const popoverElement = document.getElementById(popoverId);
                if (!popoverElement) return;

                const header = popoverElement.querySelector('.popover-header');
                if (header) {
                    header.className = 'popover-header ' + headerClass;
                }
            });
        }
    });
}

// Khởi tạo event handlers cho delete buttons
function initDeleteButtons() {
    $(".btn-delete").off('click');
    
    $(document).off('click', '.btn-delete').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        let url = $(this).data("url");
        let row = $(this).closest("tr");
        Swal.fire({
            title: "Bạn có chắc chắn muốn xóa?",
            text: "Dữ liệu sẽ không thể khôi phục!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Xóa",
            cancelButtonText: "Hủy"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: "DELETE",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr("content")
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire("Đã xóa!", response.message, "success");
                            row.remove();
                        } else {
                            Swal.fire("Lỗi!", response.message, "error");
                        }
                    },
                    error: function() {
                        Swal.fire("Lỗi!", "Không thể kết nối server.", "error");
                    }
                });
            }
        });
    });
}

// Khởi tạo tất cả các chức năng cho tablebody
function initTableBody() {
    initPopovers();
    initDeleteButtons();
}

// Hàm có thể gọi lại để khởi tạo lại sau khi HTML được load lại
function loadTableBodyScript() {
    initTableBody();
}

// Khởi tạo khi document ready
$(document).ready(function() {
    initTableBody();
});

