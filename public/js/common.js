/**
 * Common utility functions for all JavaScript files
 * This file contains reusable functions that are used across multiple modules
 */

/**
 * Hiển thị thông báo Swal với cấu hình chuẩn
 * @param {string} icon - Icon type (success, error, warning, info)
 * @param {string} title - Tiêu đề
 * @param {string} text - Nội dung (optional)
 * @param {Object} options - Các tùy chọn bổ sung
 * @returns {Promise} Swal instance
 */
function showSwalMessage(icon, title, text = '', options = {}) {
    const config = {
        icon: icon,
        title: title,
        timer: options.timer || 3000,
        showConfirmButton: options.showConfirmButton !== undefined ? options.showConfirmButton : true,
        confirmButtonText: options.confirmButtonText || 'OK',
        ...options
    };

    if (text) {
        config.text = text;
    }

    return Swal.fire(config);
}

/**
 * Hiển thị dialog xác nhận với Swal
 * @param {string} title - Tiêu đề
 * @param {string} text - Nội dung
 * @param {string} confirmText - Text nút xác nhận (default: 'Xác nhận')
 * @param {string} cancelText - Text nút hủy (default: 'Hủy bỏ')
 * @returns {Promise} Swal result
 */
function showSwalConfirm(title, text, confirmText = 'Xác nhận', cancelText = 'Hủy bỏ') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
}

/**
 * Hiển thị loading với Swal
 * @param {string} title - Tiêu đề loading
 * @param {string} text - Nội dung phụ (optional)
 * @returns {Promise} Swal instance
 */
function showSwalLoading(title = 'Đang tải...', text = '') {
    const config = {
        title: title,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    };
    if (text) {
        config.text = text;
    }
    return Swal.fire(config);
}

/**
 * Xử lý lỗi AJAX chuẩn
 * @param {Object} xhr - XMLHttpRequest object
 * @param {Function} customHandler - Custom error handler (optional)
 */
function handleAjaxError(xhr, customHandler = null) {
    if (typeof CloseWaitBox === 'function') {
        CloseWaitBox();
    }

    if (customHandler && typeof customHandler === 'function') {
        customHandler(xhr);
        return;
    }

    // Default error handling
    const errorMessage = xhr.responseJSON?.message || xhr.responseText || 'Lỗi khi thực hiện yêu cầu. Vui lòng thử lại.';
    showSwalMessage('error', errorMessage, '');
    console.error('AJAX Error:', xhr.responseText);
}

/**
 * Thực hiện AJAX request với xử lý lỗi chuẩn
 * @param {Object} config - Cấu hình AJAX
 * @param {string} config.url - URL endpoint
 * @param {string} config.method - HTTP method (GET, POST, etc.)
 * @param {Object} config.data - Data to send
 * @param {Function} config.onSuccess - Callback khi thành công
 * @param {Function} config.onError - Callback khi lỗi (optional)
 * @param {Object} config.swalSuccess - Cấu hình Swal khi thành công (optional)
 * @param {boolean} config.showLoading - Hiển thị loading (default: false)
 */
function performAjaxRequest(config) {
    const {
        url,
        method = 'GET',
        data = {},
        onSuccess = null,
        onError = null,
        swalSuccess = null,
        showLoading = false
    } = config;

    if (showLoading) {
        if (typeof OpenWaitBox === 'function') {
            OpenWaitBox();
        }
    }

    $.ajax({
        url: url,
        type: method,
        data: data,
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || $('meta[name="csrf-token"]').attr('content') || ''
        },
        success: function (response) {
            if (showLoading && typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            if (swalSuccess) {
                showSwalMessage(
                    swalSuccess.icon || 'success',
                    swalSuccess.title || 'Thành công!',
                    swalSuccess.text || '',
                    swalSuccess.options || {}
                );
            }
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(response);
            }
        },
        error: function (xhr) {
            if (showLoading && typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            if (onError && typeof onError === 'function') {
                onError(xhr);
            } else {
                handleAjaxError(xhr);
            }
        }
    });
}

/**
 * Download file từ blob
 * @param {Blob} blob - Blob object
 * @param {string} filename - Tên file để download
 */
function downloadBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

/**
 * Lấy tên file từ Content-Disposition header
 * @param {string} disposition - Content-Disposition header value
 * @param {string} defaultFilename - Tên file mặc định
 * @returns {string} Tên file
 */
function extractFilenameFromDisposition(disposition, defaultFilename = 'file') {
    if (!disposition || disposition.indexOf('filename=') === -1) {
        return defaultFilename;
    }
    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
    if (match && match[1]) {
        return decodeURIComponent(match[1].replace(/['"]/g, ''));
    }
    return defaultFilename;
}

/**
 * Download file từ URL với loading indicator
 * @param {string} url - URL để download
 * @param {Object} options - Options
 * @param {string} options.defaultFilename - Tên file mặc định
 * @param {string} options.acceptHeader - Accept header (default: 'application/pdf')
 * @param {Function} options.onSuccess - Callback khi thành công
 * @param {Function} options.onError - Callback khi lỗi
 * @param {boolean} options.useSwalLoading - Sử dụng Swal loading thay vì OpenWaitBox (default: false)
 */
function downloadFile(url, options = {}) {
    const {
        defaultFilename = 'file',
        acceptHeader = 'application/pdf',
        onSuccess = null,
        onError = null,
        useSwalLoading = false
    } = options;

    // Hiển thị loading
    if (useSwalLoading) {
        showSwalLoading('Đang tải file...');
    } else {
        if (typeof OpenWaitBox === 'function') {
            OpenWaitBox();
        }
    }

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': acceptHeader
        }
    })
        .then(response => {
            if (!response.ok) throw new Error('Lỗi tải file');

            // Kiểm tra nếu response là JSON (lỗi)
            const contentType = response.headers.get('Content-Type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(json => {
                    throw new Error(json.message || 'Lỗi tải file');
                });
            }

            // Lấy tên file từ header
            const disposition = response.headers.get('Content-Disposition');
            const filename = extractFilenameFromDisposition(disposition, defaultFilename);

            // Chuyển response thành blob
            return response.blob().then(blob => ({
                blob,
                filename
            }));
        })
        .then(({ blob, filename }) => {
            // Đóng loading
            if (useSwalLoading) {
                Swal.close();
            } else {
                if (typeof CloseWaitBox === 'function') {
                    CloseWaitBox();
                }
            }

            // Download file
            downloadBlob(blob, filename);

            // Hiển thị thông báo thành công
            if (useSwalLoading) {
                showSwalMessage('success', 'Tải file thành công!', '', {
                    showConfirmButton: false,
                    timer: 1500
                });
            }

            // Callback success
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(blob, filename);
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải file:', error);

            // Đóng loading
            if (useSwalLoading) {
                Swal.close();
            } else {
                if (typeof CloseWaitBox === 'function') {
                    CloseWaitBox();
                }
            }

            // Hiển thị lỗi
            showSwalMessage('error', 'Lỗi khi tải file!', error.message || 'Lỗi không xác định');

            // Callback error
            if (onError && typeof onError === 'function') {
                onError(error);
            }
        });
}

/**
 * Download file từ URL với cooldown period
 * @param {string} url - URL để download
 * @param {Object} options - Options
 * @param {string} options.defaultFilename - Tên file mặc định
 * @param {string} options.acceptHeader - Accept header
 * @param {string} options.cooldownKey - Key để lưu timestamp trong localStorage
 * @param {number} options.cooldownPeriodMs - Thời gian cooldown (ms)
 * @param {Function} options.onSuccess - Callback khi thành công
 * @param {Function} options.onError - Callback khi lỗi
 */
function downloadFileWithCooldown(url, options = {}) {
    const {
        defaultFilename = 'file.xlsx',
        acceptHeader = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        cooldownKey = 'lastExportTimestamp',
        cooldownPeriodMs = 2 * 60 * 1000, // 2 phút mặc định
        onSuccess = null,
        onError = null
    } = options;

    // Kiểm tra cooldown
    const lastExportTime = localStorage.getItem(cooldownKey);
    const currentTime = Date.now();
    
    if (lastExportTime) {
        const timeDiff = currentTime - parseInt(lastExportTime, 10);
        if (timeDiff < cooldownPeriodMs) {
            const timeLeftSeconds = Math.ceil((cooldownPeriodMs - timeDiff) / 1000);
            const minutes = Math.floor(timeLeftSeconds / 60);
            const seconds = timeLeftSeconds % 60;
            showSwalMessage('warning', 'Vui lòng chờ', `Bạn vừa xuất file. Vui lòng chờ ${minutes} phút ${seconds} giây trước khi xuất tiếp.`);
            return;
        }
    }

    // Hiển thị loading
    showSwalLoading('Đang xuất file...', 'Vui lòng chờ trong giây lát');

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': acceptHeader
        }
    })
        .then(response => {
            Swal.close();
            
            if (!response.ok) throw new Error('Lỗi tải file');

            // Kiểm tra nếu response là JSON (lỗi)
            const contentType = response.headers.get('Content-Type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(json => {
                    throw new Error(json.message || 'Lỗi tải file');
                });
            }

            // Lấy tên file từ header
            const disposition = response.headers.get('Content-Disposition');
            const filename = extractFilenameFromDisposition(disposition, defaultFilename);

            return response.blob().then(blob => ({
                blob,
                filename
            }));
        })
        .then(({ blob, filename }) => {
            // Lưu timestamp khi tải file thành công
            localStorage.setItem(cooldownKey, currentTime.toString());
            
            // Download file
            downloadBlob(blob, filename);

            // Callback success
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(blob, filename);
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải file:', error);
            showSwalMessage('error', 'Lỗi server', error.message || 'Lỗi không xác định');

            // Callback error
            if (onError && typeof onError === 'function') {
                onError(error);
            }
        });
}

/**
 * Format date to input format (YYYY-MM-DD)
 * @param {string|Date} dateInput - Date string or Date object
 * @returns {string} Formatted date string (YYYY-MM-DD)
 */
function formatDateToInput(dateInput) {
    try {
        const date = new Date(dateInput);
        if (isNaN(date.getTime())) return ''; // Trả về chuỗi rỗng nếu không hợp lệ

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Tháng bắt đầu từ 0
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    } catch (e) {
        return '';
    }
}

/**
 * Format date to DD/MM/YYYY format
 * @param {string|Date} dateString - Date string or Date object
 * @returns {string} Formatted date string (DD/MM/YYYY)
 */
function formatDateToDMY(dateString) {
    const date = new Date(dateString);
    if (isNaN(date)) return '';
    
    const day = ('0' + date.getDate()).slice(-2);
    const month = ('0' + (date.getMonth() + 1)).slice(-2);
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
}

/**
 * Parse date from DD/MM/YYYY format
 * @param {string} dateStr - Date string in DD/MM/YYYY format
 * @returns {Date|null} Date object or null if invalid
 */
function parseDate(dateStr) {
    if (!dateStr || !/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) return null;
    const [day, month, year] = dateStr.split('/');
    const date = new Date(`${year}-${month}-${day}`);
    date.setHours(0, 0, 0, 0);
    return date;
}

/**
 * Validate phone number (10-11 digits)
 * @param {string|HTMLElement} selector - Phone number string or element selector
 * @returns {Object} { valid: boolean, message?: string }
 */
function validatePhoneNumber(selector) {
    let val;
    if (typeof selector === 'string') {
        val = selector.trim();
    } else {
        const $input = $(selector);
        val = $input.val().trim();
    }
    
    let regex = /^[0-9]{10,11}$/;
    if (!regex.test(val)) {
        return {
            valid: false,
            message: 'Số điện thoại không hợp lệ. Chỉ gồm 10–11 chữ số.'
        };
    }
    return { valid: true };
}

/**
 * Validate date input format (YYYY-MM-DD)
 * @param {string|HTMLElement} selector - Date string or element selector
 * @returns {Object} { valid: boolean, message?: string }
 */
function validateDateInput(selector) {
    let val;
    if (typeof selector === 'string') {
        val = selector;
    } else {
        const $input = $(selector);
        val = $input.val();
    }
    
    // Kiểm tra định dạng yyyy-mm-dd (HTML5 date input chuẩn trả về định dạng này)
    let regex = /^\d{4}-\d{2}-\d{2}$/;
    if (!regex.test(val)) {
        return { valid: false, message: 'Ngày không đúng định dạng.' };
    }

    // Kiểm tra ngày có hợp lệ hay không
    const parts = val.split('-');
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);

    // Tạo đối tượng Date JS
    const dateObj = new Date(year, month - 1, day);

    // Kiểm tra ngày hợp lệ: Date obj phải khớp đúng với giá trị nhập
    if (
        dateObj.getFullYear() !== year ||
        dateObj.getMonth() + 1 !== month ||
        dateObj.getDate() !== day
    ) {
        return { valid: false, message: 'Ngày không hợp lệ.' };
    }

    return { valid: true };
}

/**
 * Setup autocomplete từ server (AJAX)
 * @param {string} inputSelector - Selector của input
 * @param {string} suggestionBoxSelector - Selector của suggestion box
 * @param {string} requestUrl - URL để lấy dữ liệu
 * @param {number} minLength - Độ dài tối thiểu để trigger (default: 1)
 */
function setupAutoComplete(inputSelector, suggestionBoxSelector, requestUrl, minLength = 1) {
    $(inputSelector).on('keyup', function() {
        let query = $(this).val();
        if (query.length === 0) {
            $(suggestionBoxSelector).hide();
            return;
        }
        if (query.length >= minLength) {
            $.ajax({
                url: requestUrl,
                type: 'GET',
                data: {
                    query: query
                },
                success: function(data) {
                    $(suggestionBoxSelector).empty();
                    if (data.length > 0) {
                        $(suggestionBoxSelector).show();
                        data.forEach(function(item) {
                            $(suggestionBoxSelector).append(
                                '<div class="suggestion-item" style="padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;">' + item + '</div>'
                            );
                        });
                        // Gán lại sự kiện click cho từng item
                        $(suggestionBoxSelector + ' .suggestion-item').on('click', function() {
                            $(inputSelector).val($(this).text());
                            $(suggestionBoxSelector).hide();
                        });
                    } else {
                        $(suggestionBoxSelector).hide();
                    }
                }
            });
        }
    });
}

/**
 * Setup autocomplete từ client-side (local list)
 * @param {string} inputSelector - Selector của input
 * @param {string} suggestionBoxSelector - Selector của suggestion box
 * @param {Array} dataList - Mảng dữ liệu để tìm kiếm
 * @param {string} searchKey - Key để tìm kiếm trong object (default: 'product_name')
 * @param {number} maxResults - Số kết quả tối đa (default: 10)
 */
function setupClientAutoComplete(inputSelector, suggestionBoxSelector, dataList, searchKey = 'product_name', maxResults = 10) {
    $(inputSelector).on('input', function() {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $(suggestionBoxSelector);
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        const matched = dataList.filter(item => {
            const searchValue = item[searchKey] || item;
            return typeof searchValue === 'string' && searchValue.toLowerCase().includes(keyword);
        });

        if (matched.length > 0) {
            matched.slice(0, maxResults).forEach(item => {
                const displayText = item[searchKey] || item;
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${displayText}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    $(document).on('mousedown', suggestionBoxSelector + ' button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(inputSelector).val($(this).text());
        $(suggestionBoxSelector).addClass('d-none').empty();
    });
}

/**
 * Ẩn suggestion box khi click ra ngoài
 * @param {Array} selectors - Array of objects chứa các selector cần xử lý
 * @example [{ input: '#product', suggestion: '#suggestions-product-name' }]
 */
function setupClickOutsideToHide(selectors) {
    $(document).on('click', function(e) {
        selectors.forEach(({ input, suggestion }) => {
            if (!$(e.target).closest(input).length && !$(e.target).closest(suggestion).length) {
                $(suggestion).hide().addClass('d-none').empty();
            }
        });
    });
}

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
 * Format currency input (VNĐ format)
 * @param {jQuery|string} input - jQuery object hoặc selector của input
 */
function formatCurrency(input) {
    const $input = $(input);
    let value = $input.val().replace(/[^0-9]/g, '');
    if (!value) {
        $input.val('');
        return;
    }
    let num = parseInt(value, 10);
    if (isNaN(num)) {
        $input.val('');
        return;
    }
    $input.val(num.toLocaleString('vi-VN'));
}

/**
 * Lấy giá trị số thô từ trường tiền tệ
 * @param {jQuery|string} input - jQuery object hoặc selector của input
 * @returns {string} Giá trị số thô (ví dụ: "1000000")
 */
function getCurrencyValue(input) {
    const $input = $(input);
    let value = $input.val() || '';
    return value.replace(/[^0-9]/g, '') || '0';
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