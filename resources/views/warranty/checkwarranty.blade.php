@extends('layout.layout')

@section('content')
<div class="container-fluid mt-3">
    <div class="row g-4">
        <div class="col-12 col-md-12">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white position-relative">
                    <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" onclick="goBackOrReload()" title="Quay lại"
                        style="height: 15px; filter: brightness(0) invert(1); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                    <h5 class="mb-0 ms-5">Kiểm tra thông tin bảo hành</h5>
                </div>
                <div class="card-body">
                    <label for="search_code" class="form-label">Nhập mã tem bảo hành hoặc mã đơn hàng (<span style="color: red;">*</span>)</label>
                    <input id="search_code" name="search_code" type="text" class="form-control mb-3" placeholder="Nhập mã tem bảo hành / mã đơn hàng">
                    <div id="error-message-search" class="text-danger mb-3" style="display: none;"></div>
                    <p>- Hệ thống sẽ ưu tiên kiểm tra theo mã bảo hành. Nếu không tìm thấy, hệ thống sẽ tự động tra cứu theo mã đơn hàng (order_code1 / order_code2).</p>
                    <p>- Đối với các đơn hàng được mua trước ngày 25/11/2024 đang áp dụng mẫu tem bảo hành cũ. Vui lòng nhấn tạo phiếu bảo hành và bỏ qua bước "Tra cứu" này và tạo trực tiếp phiếu bảo hành ngay nút dưới.</p>
                    <p>- CHÚ Ý: Sản phẩm <b>Robot hút bụi lau nhà KU PPR3006</b> kỹ thuật viên khi tra cứu bảo hành vui lòng nhập theo cú pháp: 2025050500 + (3 chữ số cuối của mã serial sản phẩm)</p>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" id="btn-check">Tra cứu</button>
                        <button onclick="window.location.href='{{ route('warranty.formcard') }} '" class="btn btn-warning text-dark">&#43; Tạo phiếu bảo hành</button>
                        <button class="btn btn-success" id="btn_check_qick">Quét mã QR</button>
                    </div>
                    <video id="video" autoplay playsinline></video>
                </div>
            </div>
            
        </div>
    </div>
    <!-- Thông tin khách hàng -->
    <div id="customer-info" class="row g-4 mt-2" style="display: none; padding-bottom: 40px;"></div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 10px;
        margin-left: 5px;
        border-left: 2px solid #007bff;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 10px 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .timeline-details {
        overflow: hidden;
        transition: height 0.4s ease;
    }

    #video {
        width: 100vw;
        height: 50vh;
        object-fit: cover;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 9999;
        display: none;
        background: black;
        margin-top: 50px;
    }
</style>
<script>
    // validation form kiểm tra bảo hành
    let formErrors = {};

    // Hàm hiển thị lỗi
    function showError($field, message) {
        const fieldId = $field.attr('id');
        if (!fieldId) return;
        hideError($field); // Xóa lỗi cũ

        $('#error-message-search').text(message).show();
        formErrors[fieldId] = true;
        updateButtonState();
    }

    // Hàm ẩn lỗi
    function hideError($field) {
        const fieldId = $field.attr('id');
        if (!fieldId) return;

        $('#error-message-search').text('').hide();
        delete formErrors[fieldId];
        updateButtonState();
    }

    // Cập nhật trạng thái nút Tra cứu
    function updateButtonState() {
        const hasErrors = Object.keys(formErrors).length > 0;
        $('#btn-check').prop('disabled', hasErrors);
    }

    // Hàm validation cho mã seri
    function validateSearchCode() {
        const $input = $('#search_code');
        const value = $input.val().trim();

        if (!value) {
            showError($input, "Vui lòng nhập mã cần tra cứu.");
            return false;
        }
        if (!/^[a-zA-Z0-9-]+$/.test(value)) {
            showError($input, "Chỉ được nhập chữ, số và dấu gạch ngang (-).");
            return false;
        }
        if (value.length > 50) {
            showError($input, "Tối đa 50 ký tự.");
            return false;
        }

        hideError($input);
        return true;
    }

    $(document).ready(function() {
        // Gắn sự kiện validation khi người dùng nhập
        $('#search_code').on('input', validateSearchCode);

        // Xử lý sự kiện click nút "Tra cứu"
        $('#btn-check').click(function(e) {
            e.preventDefault();
            
            if (validateSearchCode()) {
                const value = $('#search_code').val().trim();
                performWarrantySearch(value);
            }
        });
    });

    function handleSearchSuccess(response) {
        CloseWaitBox();
        if (!response.success) {
            Swal.fire({
                icon: 'error',
                title: response.message,
                timer: 2000,
            });
            $('#customer-info').html('').fadeOut(150);
        } else {
            $('#customer-info').html(response.view).fadeIn(200);
        }
    }

    function performWarrantySearch(value) {
        OpenWaitBox();
        $.ajax({
            url: '{{ route("warranty.find") }}',
            method: 'POST',
            data: {
                serial_number: value,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    handleSearchSuccess(response);
                } else {
                    performOrderSearch(value, response.message);
                }
            },
            error: function() {
                CloseWaitBox();
                Swal.fire({
                    icon: 'error',
                    title: "Lỗi Server khi tra cứu mã bảo hành",
                    timer: 2000,
                });
            }
        });
    }

    function performOrderSearch(value, previousMessage = '') {
        $.ajax({
            url: '{{ route("warranty.findbyorder") }}',
            method: 'POST',
            data: {
                order_code: value,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    handleSearchSuccess(response);
                } else {
                    CloseWaitBox();
                    Swal.fire({
                        icon: 'error',
                        title: response.message,
                        text: previousMessage ? 'Chi tiết: ' + previousMessage : '',
                        timer: 2500,
                    });
                    $('#customer-info').html('').fadeOut(150);
                }
            },
            error: function() {
                CloseWaitBox();
                Swal.fire({
                    icon: 'error',
                    title: "Lỗi Server khi tra cứu mã đơn hàng",
                    timer: 2000,
                });
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/umd/index.min.js"></script>
<script>
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
                OpenWaitBox();        
                $.ajax({
                    url: '{{ route("warranty.findqr") }}',
                    method: 'POST',
                    data: {
                        serial_number: serial,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        CloseWaitBox();
                        if (response.success) {
                            const $form = $('<form>', {
                                method: 'POST',
                                action: '{{ route("warranty.formcard") }}'
                            });

                            $form.append($('<input>', {
                                type: 'hidden',
                                name: '_token',
                                value: '{{ csrf_token() }}'
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
                            Swal.fire({
                                icon: 'warning',
                                title: response.message,
                                timer: 2000,
                            });
                        }
                    },
                    error: function(xhr) {
                        CloseWaitBox();
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
</script>
@endsection