/**
 * UI utility functions
 * Các hàm tiện ích cho giao diện người dùng
 */

/**
 * Rút gọn text và thêm tooltip
 * @param {string} selector - Selector của các element cần xử lý
 * @param {number} maxLength - Độ dài tối đa (default: 50)
 */
function shortenTextWithTooltip(selector = '.shorten-text', maxLength = 50) {
    document.addEventListener("DOMContentLoaded", function() {
        const cells = document.querySelectorAll(selector);
        cells.forEach(cell => {
            const originalText = cell.textContent.trim();
            if (originalText.length > maxLength) {
                const words = originalText.split(' ');
                let shortText = '';
                for (let word of words) {
                    if ((shortText + word).length > maxLength) break;
                    shortText += word + ' ';
                }
                shortText = shortText.trim() + '...';
                cell.textContent = shortText;
                cell.setAttribute('title', originalText);
                cell.setAttribute('data-bs-toggle', 'tooltip');
            }
        });
        // Kích hoạt tooltip của Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
}

/**
 * Resize table container để fit với window height
 * @param {string} containerSelector - Selector của container
 * @param {string} tableContainerSelector - Selector của table container
 * @param {number} offset - Offset để trừ đi (default: 10)
 */
function resizeTableContainer(containerSelector = '.container', tableContainerSelector = '.table-container', offset = 10) {
    const windowHeight = $(window).height();
    const containerHeight = $(containerSelector).outerHeight(true); // bao gồm margin
    const newHeight = windowHeight - containerHeight;
    $(tableContainerSelector).height(newHeight - offset);
}

/**
 * Setup drag to scroll cho table container
 * @param {string} containerSelector - Selector của container chứa table (default: '#tabContent')
 * @param {string} tableContainerSelector - Selector của table container (default: '.table-container')
 */
function setupTableDragScroll(containerSelector = '#tabContent', tableContainerSelector = '.table-container') {
    // Biến trạng thái
    let isMouseDown = false;
    let isGrabbing = false;
    let startX, scrollLeft;
    let $dragTarget = null;

    // 1. Gắn sự kiện 'mousedown'
    $(containerSelector).on('mousedown', tableContainerSelector, function(e) {
        // Chỉ xử lý khi nhấn chuột trái
        if (e.button !== 0) return;

        // Nếu người dùng click vào scrollbar thì không làm gì cả
        if (e.target.scrollHeight > e.target.clientHeight && e.offsetX > e.target.clientWidth) {
            return;
        }

        isMouseDown = true;
        isGrabbing = false;
        $dragTarget = $(this);

        // Ghi lại vị trí bắt đầu và vị trí cuộn hiện tại
        startX = e.pageX;
        scrollLeft = $dragTarget.scrollLeft();
    });

    // 2. Gắn sự kiện 'mousemove' vào cả trang
    $(document).on('mousemove', function(e) {
        if (!isMouseDown || !$dragTarget) return;

        const x = e.pageX;
        const walk = x - startX;
        
        // Chỉ kích hoạt chế độ kéo-cuộn nếu di chuyển chuột đủ xa (5px)
        if (!isGrabbing && Math.abs(walk) > 5) {
            isGrabbing = true;
            $dragTarget.addClass('is-grabbing');
        }

        if (!isGrabbing) return;

        e.preventDefault();
        const scrollDistance = walk * 2; // Nhân 2 để kéo nhạy hơn
        $dragTarget.scrollLeft(scrollLeft - scrollDistance);
    });

    // 3. Gắn sự kiện 'mouseup' vào cả trang
    $(document).on('mouseup', function(e) {
        isMouseDown = false;
        if (isGrabbing) {
            isGrabbing = false;
            if ($dragTarget) {
                $dragTarget.removeClass('is-grabbing');
            }
            $dragTarget = null;
        }
    });

    // 4. Dừng kéo nếu chuột đi ra ngoài cửa sổ trình duyệt
    $(document).on('mouseleave', function() {
        isMouseDown = false;
        if (isGrabbing) {
            isGrabbing = false;
            if ($dragTarget) {
                $dragTarget.removeClass('is-grabbing');
            }
            $dragTarget = null;
        }
    });

    // 5. Thêm class 'can-grab' vào .table-container
    $(containerSelector).on('mouseenter', tableContainerSelector, function() {
        const $container = $(this);
        if ($container[0].scrollWidth > $container[0].clientWidth) {
            $container.addClass('can-grab');
        }
    }).on('mouseleave', tableContainerSelector, function() {
        $(this).removeClass('can-grab');
    });
}

/**
 * Setup row highlight khi click vào table row
 * @param {string} containerSelector - Selector của container chứa table
 * @param {string} rowSelector - Selector của row (default: 'tbody tr')
 * @param {string} highlightClass - Class để highlight (default: 'highlight-row')
 */
function setupRowHighlight(containerSelector, rowSelector = 'tbody tr', highlightClass = 'highlight-row') {
    $(containerSelector).on('click', rowSelector, function() {
        const isHighlighted = $(this).hasClass(highlightClass);
        $(rowSelector).removeClass(highlightClass);
        if (!isHighlighted) {
            $(this).addClass(highlightClass);
        }
    });
}

