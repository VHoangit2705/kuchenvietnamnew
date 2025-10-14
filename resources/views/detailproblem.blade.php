<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết lỗi</title>
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- jQuery UI -->
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <!-- CSS -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
        <!-- JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <h3 class="mt-2 text-center">Chi tiết yêu cầu hỗ trợ</h3>
    </div>
    <div class="container-fluid d-flex justify-content-start gap-4 mt-4">
        <div class="card pb-3" style="flex: 1;">
            <div class="card-header bg-primary text-white">
                <h5>Thông tin lỗi</h5>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <p class="ms-3 mt-2"><strong>Tên lỗi:</strong></p>
                    <p class="ms-3"><strong>Mô tả:</strong></p>
                    <p class="ms-3"><strong>Người gửi:</strong></p>
                    <p class="ms-3"><strong>Ngày gửi:</strong></p>
                    <p class="ms-3"><strong>Người xử lý:</strong></p>
                    <p class="ms-3"><strong>Ngày xử lý:</strong></p>
                    <p class="ms-3"><strong>Trạng thái:</strong></p>
                    <p class="ms-3"><strong>Phản hồi:</strong></p>
                </div>
                <div class="col-md-9">
                    <p class="mt-2">{{ $data->error_name}}</p>
                    <p>{{ $data->description }}</p>
                    <p>{{ $data->create_by }}</p>
                    <p>{{ $data->create_at }}</p>
                    <p>{{ $data->update_by ?? 'chưa được xử lý'  }}</p>
                    <p>{{ $data->update_at ?? 'chưa được xử lý' }}</p>
                    <p>
                        <span class="{{ $data->status === 0 ? 'text-danger' : 'text-success' }}">
                            {{ $data->status === 0 ? 'Chưa cập nhật' : 'Đã cập nhật' }}
                        </span>
                    </p>
                    <p>
                        <textarea  class="form-control" style="width: 98%;" id="phanhoi" name="phanhoi" rows="2" required>{{ $data->solution }}</textarea>
                    </p>
                    <button class="btn btn-success" id="updateStatus" onclick="updateStatus({{ $data->id }})">Cập nhật</button>
                </div>
            </div>
        </div>
        <div class="card" style="flex: 2;">
            <div class="card-header bg-primary text-white">
                <h5>Danh sách hình ảnh</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @forelse ($images as $image)
                        <div class="position-relative">
                            <img src="{{ asset('storage/' . $image->error_img) }}" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;" 
                            data-bs-toggle="modal" data-bs-target="#imageModal" onclick="showImage('{{ asset('storage/' . $image->error_img) }}')">
                        </div>
                    @empty
                        <p class="text-muted">Không có hình ảnh nào.</p>
                    @endforelse
                    <!-- Modal -->
                    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button id="rotateButton" class="btn btn-primary">Xoay ảnh</button>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img id="modalImage" src="" class="img-fluid" alt="Image" style="max-width: 100%; height: auto;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid mt-2">
        <button type="button" id="back" class="btn btn-secondary">Quay lại</button>
    </div>
    <script>
        $(document).ready(function() {
            $('#phanhoi').on('input', function () {
                $(this).css('border', '');
                this.setCustomValidity('');
            });
            goBack();
        });
        function goBack() {
            $('#back').on('click', function(){
                window.location.href = "{{ route('listproblem') }}";
            });
        }
        function updateStatus(id) {
            var solution = $('#phanhoi').val().trim();
            if (solution === '') {
                $('#phanhoi')[0].setCustomValidity('Vui lòng nhập phản hồi.');
                $('#phanhoi')[0].reportValidity();
                // Swal.fire({
                //     icon: 'warning',
                //     text: 'Vui lòng nhập phản hồi!'
                // });
            } else {
                Swal.fire({
                    title: 'Xác nhận cập nhật trạng thái',
                    text: 'Bạn có chắc chắn muốn cập nhật bản ghi này không?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Cập nhật',
                    cancelButtonText: 'Hủy bỏ'
                }).then((result) => {
                    if (result.isConfirmed) {  
                        $.ajax({
                            url: '{{ route("updatestatus") }}',
                            type: 'get',
                            data: {
                                id: id,
                                solution: solution
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                Swal.fire({
                                    icon: 'success',
                                    text: 'Cập nhật thành công!'
                                });
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            },
                            error: function (xhr) {
                                debugger;
                                Swal.fire({
                                    icon: 'error',
                                    text: 'Gửi yêu cầu thất bại. Vui lòng thử lại!'
                                });
                            }
                        });
                    }
                });
            }
        }

        let rotationAngle = 0; // Biến để theo dõi góc quay hiện tại
        let zoomScale = 1;
        let isDragging = false;
        let startX, startY, currentX = 0, currentY = 0;
        
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
        
        // Xoay ảnh
        $('#rotateButton').on('click', function() {
            rotationAngle += 90;
            updateTransform();
        });
        
        // Hàm cập nhật transform của ảnh
        function updateTransform() {
            $('#modalImage').css('transform', `translate(${currentX}px, ${currentY}px) rotate(${rotationAngle}deg) scale(${zoomScale})`);
        }
        
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
            if (zoomScale > 1 && e.which === 1) { // Chỉ kéo nếu đã zoom và nhấn chuột trái
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
    </script>
</body>
</html>