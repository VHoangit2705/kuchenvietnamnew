/**
 * Validation form kiểm tra bảo hành
 * 
 * NOTE: Các hàm validate đã được di chuyển sang /js/validate_input/checkwarranty.js
 * Các hàm showError, hideError đã được di chuyển sang /js/validate_input/helpers.js
 * Vui lòng đảm bảo các file sau được load trước file này:
 * - /js/validate_input/helpers.js
 * - /js/validate_input/checkwarranty.js
 */

let formErrors = {};

// Cập nhật trạng thái nút Tra cứu
function updateButtonState() {
    const hasErrors = Object.keys(formErrors).length > 0;
    $('#btn-check').prop('disabled', hasErrors);
}

// Đợi validation files load xong trước khi khởi tạo
function initCheckWarranty() {
    if (typeof validateSerial === 'function') {
        // Gắn sự kiện validation khi người dùng nhập
        $('#serial_number').on('input', validateSerial);
    }
}

if (window.validationFilesLoaded) {
    $(document).ready(initCheckWarranty);
} else {
    $(document).on('validation:loaded', initCheckWarranty);
    $(document).ready(function() {
        // Fallback: nếu event không trigger, thử lại sau 1 giây
        setTimeout(function() {
            if (window.validationFilesLoaded && typeof validateSerial === 'function') {
                $('#serial_number').on('input', validateSerial);
            }
        }, 1000);
    });
}

$(document).ready(function() {

    // Xử lý sự kiện click nút "Tra cứu"
    $('#btn-check').click(function(e) {
        e.preventDefault();
        
        // Chạy validation lần cuối trước khi submit
        if (typeof validateSerial === 'function' && validateSerial()) {
            if (typeof OpenWaitBox === 'function') {
                OpenWaitBox();
            }
            $.ajax({
                url: window.warrantyFindRoute || '',
                method: 'POST',
                data: {
                    serial_number: $('#serial_number').val().trim(),
                    _token: window.csrfToken || ''
                },
                success: function(response) {
                    if (typeof CloseWaitBox === 'function') {
                        CloseWaitBox();
                    }
                    if (!response.success) {
                        showSwalMessage('error', response.message, '', {
                            timer: 2000
                        });
                        $('#customer-info').html('').fadeOut(150);
                    }
                    $('#customer-info').html(response.view).fadeIn(200);
                },
                error: function(xhr) {
                    if (typeof CloseWaitBox === 'function') {
                        CloseWaitBox();
                    }
                    showSwalMessage('error', 'Lỗi Server', '', {
                        timer: 2000
                    });
                }
            });
        }
    });
});

// QR Code Scanner
if (typeof ZXing !== 'undefined') {
    const codeReader = new ZXing.BrowserMultiFormatReader();
    const videoElement = document.getElementById('video');
    
    document.getElementById('btn_check_qick').addEventListener('click', async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            videoElement.srcObject = stream;
            videoElement.style.display = 'block';
            
            codeReader.decodeFromVideoDevice(null, 'video', (result, err) => {
                if (result) {
                    let serial = null;
                    const rawData = result.text.trim(); 
                    if (rawData.includes('serial=')) {
                        const url = new URL(rawData);
                        serial = url.searchParams.get('serial');
                    } else if (/SN[:\- ]?\s*\d{6,}/i.test(rawData)) {
                        const match = rawData.match(/SN[:\- ]?\s*(\d{6,})/i);
                        if (match) {
                            serial = match[1];
                        }
                    } else if (/^\d{6,}$/.test(rawData)) {
                        serial = rawData;
                    }

                    if (serial) {
                        if (typeof OpenWaitBox === 'function') {
                            OpenWaitBox();
                        }
                        $.ajax({
                            url: window.warrantyFindQrRoute || '',
                            method: 'POST',
                            data: {
                                serial_number: serial,
                                _token: window.csrfToken || ''
                            },
                            success: function(response) {
                                if (typeof CloseWaitBox === 'function') {
                                    CloseWaitBox();
                                }
                                if (response.success) {
                                    const $form = $('<form>', {
                                        method: 'POST',
                                        action: window.warrantyFormCardRoute || ''
                                    });

                                    $form.append($('<input>', {
                                        type: 'hidden',
                                        name: '_token',
                                        value: window.csrfToken || ''
                                    }));
                                    $form.append($('<input>', {
                                        type: 'hidden',
                                        name: 'warranty',
                                        value: JSON.stringify(response.warranty)
                                    }));
                                    $form.append($('<input>', {
                                        type: 'hidden',
                                        name: 'lstproduct',
                                        value: JSON.stringify(response.lstproduct)
                                    }));
                                    // Gắn form vào body và submit
                                    $('body').append($form);
                                    $form.submit();
                                } else {
                                    showSwalMessage('warning', response.message, '', {
                                        timer: 2000
                                    });
                                }
                            },
                            error: function(xhr) {
                                if (typeof CloseWaitBox === 'function') {
                                    CloseWaitBox();
                                }
                                console.error(xhr.responseJSON?.message || 'Lỗi không xác định');
                            }
                        });
                    } else {
                        alert("Không tìm thấy mã serial hợp lệ trong chuỗi: " + rawData);
                    }
                    stream.getTracks().forEach(track => track.stop());
                    videoElement.srcObject = null;
                    videoElement.style.display = 'none';
                    codeReader.reset();
                }
                if (err && !(err instanceof ZXing.NotFoundException)) {
                    console.error(err);
                }
            });
        } catch (error) {
            alert('Không thể mở camera: ' + error.message);
        }
    });
}

