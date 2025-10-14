@extends('layout.layout')

@section('content')
<div class="container-fluid mt-2">
    <div class="row g-4">
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white position-relative">
                    <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" onclick="goBackOrReload()" title="Quay lại"
                        style="height: 15px; filter: brightness(0) invert(1); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                    <h5 class="mb-0 text-center">Thông tin phiếu bảo hành</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive col-12">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th class="w-50 w-md-25">Tên sản phẩm:</th>
                                    <td>{{ $data->product }}</td>
                                </tr>
                                <tr>
                                    <th>Số seri thân máy:</th>
                                    <td data-type="serial_thanmay" data-id="{{ $data->id }}">
                                        <span class="serial-text">{{ $data->serial_thanmay }}</span>
                                        <input type="text" class="serial-input form-control form-control-sm d-none" value="{{ $data->serial_thanmay }}" style="width: 150px; display: inline-block;">
                                        <img src="{{ asset('icons/pen.png') }}"
                                            alt="Chỉnh sửa seri thân máy"
                                            title="Chỉnh sửa seri thân máy"
                                            class="edit-serial-icon"
                                            style="height: 13px; cursor: pointer; margin-left: 5px;">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số seri tem bảo hành:</th>
                                    <td data-type="serial_number" data-id="{{ $data->id }}">
                                        <span class="serial-text">{{ $data->serial_number }}</span>
                                        <input type="text" class="serial-input form-control form-control-sm d-none" value="{{ $data->serial_number }}" style="width: 150px; display: inline-block;">
                                        <img src="{{ asset('icons/pen.png') }}"
                                            alt="Chỉnh sửa seri"
                                            title="Chỉnh sửa seri"
                                            class="edit-serial-icon"
                                            style="height: 13px; cursor: pointer; margin-left: 5px;">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tên khách hàng:</th>
                                    <td>{{ $data->full_name }}</td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại:</th>
                                    <td>{{ $data->phone_number }}</td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ:</th>
                                    <td>{{ $data->address }}</td>
                                </tr>
                                @if(isset($history) && count($history) > 1)
                                <tr>
                                    <td colspan="2" class="text-center text-danger">Sản phẩm đã bảo hành lần {{ count($history) }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white text-center">
                    <h5 class="mb-0">Thông tin lịch hẹn trả</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive col-12">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th class="w-50 w-md-25">Loại tiếp nhận/Chi nhánh:</th>
                                    <td>{{ $data->branch }}</td>
                                </tr>
                                <tr>
                                    <th>Kỹ thuật viên tiếp nhận:</th>
                                    <td>{{ $data->staff_received }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày tiếp nhận:</th>
                                    <td>{{ \Carbon\Carbon::parse($data->Ngaytao)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày hẹn trả:</th>
                                    <td data-type="return_date" data-id="{{ $data->id }}">
                                        <span class="serial-text">{{ \Carbon\Carbon::parse($data->return_date)->format('d/m/Y') }}</span>
                                        <input type="date" class="serial-input form-control form-control-sm d-none" value="{{ \Carbon\Carbon::parse($data->return_date)->format('Y-m-d') }}" style="width: 150px; display: inline-block;">
                                        <img src="{{ asset('icons/pen.png') }}"
                                            alt="Chỉnh sửa seri thân máy"
                                            title="Chỉnh sửa seri thân máy"
                                            class="edit-serial-icon"
                                            style="height: 13px; cursor: pointer; margin-left: 5px;">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ngày xuất kho:</th>
                                    <td data-type="shipment_date" data-id="{{ $data->id }}">
                                        <span class="serial-text">{{ \Carbon\Carbon::parse($data->shipment_date)->format('d/m/Y') }}</span>
                                        <input type="date" class="serial-input form-control form-control-sm d-none" value="{{ \Carbon\Carbon::parse($data->shipment_date)->format('Y-m-d') }}" style="width: 150px; display: inline-block;">
                                        <img src="{{ asset('icons/pen.png') }}"
                                            alt="Chỉnh sửa seri thân máy"
                                            title="Chỉnh sửa seri thân máy"
                                            class="edit-serial-icon"
                                            style="height: 13px; cursor: pointer; margin-left: 5px;">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Trạng thái sửa chữa: </th>
                                    <td>
                                        <span style="cursor: pointer;" class="p-1 @if($data->status == 'Đã hoàn tất') bg-success @elseif($data->status == 'Chờ KH phản hồi') bg-secondary @elseif($data->status == 'Đã tiếp nhận') bg-primary @elseif($data->status == 'Đã gửi linh kiện') bg-info @else bg-warning @endif" ?
                                            @if(($data->status != 'Đã hoàn tất' && session('user') == $data->staff_received) || session('position') == 'admin')
                                            onclick="showStatusModal({{ $data->id }}, '{{ $data->status }}', '{{ $data->type }}', true)"
                                            @else
                                            onclick="showError()"
                                            @endif>
                                            <strong>{{ $data->status }}</strong>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt-4">
    <div class="d-flex" style="overflow-x: auto; white-space: nowrap;">
        <ul class="nav nav-tabs flex-nowrap" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#quatrinhsua" type="button" role="tab">
                    Quá trình sửa chữa
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab2" data-bs-toggle="tab" data-bs-target="#tinhtrangtiepnhan" type="button" role="tab">
                    Tình trạng tiếp nhận
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab3" data-bs-toggle="tab" data-bs-target="#lichsubaohanh" type="button" role="tab">
                    Lịch sử bảo hành
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab4" data-bs-toggle="tab" data-bs-target="#phieuin" type="button" role="tab">
                    Phiếu in
                </button>
            </li>
        </ul>
    </div>
    <div class="tab-content mt-0">
        <div class="tab-pane fade show active" id="quatrinhsua" role="tabpanel" tabindex="0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="repairTable">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>STT</th>
                            <th style="min-width: 150px;">Lỗi gặp</th>
                            <th style="min-width: 150px;">Cách xử lý</th>
                            <th style="min-width: 150px;">Linh kiện/Giải pháp</th>
                            <th style="min-width: 80px;">Số lượng</th>
                            <th style="min-width: 80px;">Đơn giá (vnd)</th>
                            <th style="min-width: 100px;">Thành tiền (vnd)</th>
                            <th style="min-width: 120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quatrinhsua as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $item->error_type }}</td>
                            <td>{{ $item->solution }}</td>
                            <td>{{ $item->replacement }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-center">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-center">{{ number_format($item->quantity * $item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm edit-row" onclick="Edit({{ $item->id }})">Sửa</button>
                                <button class="btn btn-danger btn-sm delete-row" onclick="Delete({{ $item->id }})">Xóa</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <a href="#" id="" class="btn d-flex align-items-center" title="Thêm quá trình mới" style="width: 50px;"onclick="ShowFormRepair()">
                <img src="{{ asset('icons/plus.png') }}" alt="Add" style="height: 30px; width: 30px">
            </a>
        </div>

        <div class="tab-pane fade" id="tinhtrangtiepnhan" role="tabpanel" tabindex="0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark text-center">
                        <tr>
                            <th class="w-50">Thông tin</th>
                            <th class="w-50">Nội dung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1. Lỗi khách hàng phản ánh khi bàn giao</td>
                            <td>{{ $data->initial_fault_condition }}</td>
                        </tr>
                        <tr>
                            <td>2. Tình trạng bên ngoài của sản phẩm khi bàn giao</td>
                            <td>{{ $data->product_fault_condition }}</td>
                        </tr>
                        <tr>
                            <td>3. Số linh kiện, sản phẩm khi nhận bàn giao</td>
                            <td>{{ $data->product_quantity_description }}</td>
                        </tr>
                        <tr>
                            <td>4. Ảnh tiếp nhận bàn giao</td>
                            <td><button class="btn btn-primary btn-sm" onclick="showImages('{{ $data->image_upload }}')">Xem ảnh</button></td>
                        </tr>
                        <tr>
                            <td>5. Video</td>
                            <td><button class="btn btn-primary btn-sm" onclick="showVideo('{{ $data->video_upload }}')">Xem video</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="lichsubaohanh" role="tabpanel" tabindex="0">
            <h5 class="fw-bold ms-3 mt-3">Lịch sử bảo hành</h5>
            <div class="timeline">
                @if(isset($history) && count($history) > 0)
                @foreach ($history as $item)
                <div class="timeline-item">
                    <div class="timeline-header d-flex justify-content-between align-items-center">
                        <p><strong>Ngày tiếp nhận: </strong>{{ \Carbon\Carbon::parse($item->received_date)->format('d/m/Y') }}</p>
                        <button class="btn btn-link toggle-details">▼</button>
                    </div>
                    <div class="timeline-details" style="overflow: hidden; height: 0;">
                        @php
                        $warrantyTypeTexts = [
                        'branch' => 'Khách đến trực tiếp tại chi nhánh bảo hành',
                        'remote' => 'Khách gửi sản phẩm đến TT bảo hành',
                        'customer_home' => 'Tiếp nhận bảo hành tại nhà khách hàng',
                        'agent_home' => 'Giao CTV bảo hành tại nhà khách hàng',
                        'agent_component' => 'Gửi phụ kiện cho cộng tác viên',
                        ];

                        $warrantyTypeText = $warrantyTypeTexts[$item->type] ?? '';
                        @endphp

                        @if($warrantyTypeText)
                        <p><strong>Hình thức bảo hành: </strong>{{ $warrantyTypeText }}</p>
                        @endif

                        @if($item->type != 'agent_component')
                        <p><strong>Loại lỗi gặp phải: </strong>{{ $item->initial_fault_condition }}</p>
                        @endif
                        <p><strong>Người tiếp nhận: </strong> {{ $item->staff_received }}</p>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>

        <div class="tab-pane fade" id="phieuin" role="tabpanel" tabindex="0">   
            <a id="downloadPdf" href="{{ route('warranty.dowloadpdf', ['id' => $data->id]) }}" download> Tải file PDF </a>
            <a id="printRequest" href="#" data-id="{{ $data->id }}" class="ms-5 my-1">Yêu cầu in phiếu</a>
            <div class="d-flex justify-content-center align-items-center" style="height: 80vh;">
                <iframe id="pdfViewer" src="{{ route('warranty.pdf', ['id' => $data->id]) }}" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="repairModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm quá trình sửa chữa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <form id="repairForm">
                    <input hidden type="text" class="form-control" id="id" name="id" value="">
                    <input hidden type="text" class="form-control" id="warranty_request_id" name="warranty_request_id" value="{{ $data->id }}">
                    <div class="mb-2">
                        <label for="error_type" class="form-label">Lỗi gặp phải</label>
                        <input type="text" class="form-control" id="error_type" name="error_type" placeholder="Nhập lỗi gặp phải">
                        <div class="error error_type text-danger small mt-1 d-none"></div>
                    </div>
                    <div class="mb-2">
                        <label for="solution" class="form-label">Cách xử lý</label>
                        <select id="solution" name="solution" class="form-control">
                            <option value="" disabled selected>-- Cách xử lý --</option>
                            <option value="Sửa chữa tại chỗ (lỗi nhẹ)">Sửa chữa tại chỗ (lỗi nhẹ)</option>
                            <option value="Giữ lại để kiểm tra chuyên sâu">Giữ lại để kiểm tra chuyên sâu</option>
                            <option value="Thay thế linh kiện/hardware">Thay thế linh kiện/hardware</option>
                            <option value="Đổi mới sản phẩm">Đổi mới sản phẩm</option>
                            <option value="Gửi về trung tâm bảo hành NSX">Gửi về trung tâm bảo hành NSX</option>
                            <option value="Từ chối bảo hành">Từ chối bảo hành</option>
                            <option value="KH không muốn bảo hành">KH không muốn bảo hành</option>
                        </select>
                        <div class="error error_sl text-danger small mt-1 d-none"></div>
                    </div>
                    <div id="des_error_container" class="mb-2 d-none">
                        <label for="des_error_type" class="form-label">Mô tả cách xử lý</label>
                        <input type="text" class="form-control" id="des_error_type" name="des_error_type" placeholder="Nhập mô tả các xử lý">
                    </div>
                    <div class="mb-2">
                        <label for="replacement" class="form-label">Linh kiện thay thế</label>
                        <div style="position: relative;">
                            <input type="text" id="replacement" name="replacement" class="form-control" placeholder="Nhập linh kiện thay thế">
                            <div id="replacement-suggestions" class="list-group position-absolute w-100 d-none"></div>
                        </div>
                        <div class="error error_replace text-danger small mt-1 d-none"></div>
                    </div>
                    <div class="mb-2">
                        <label for="quantity" class="form-label">Số lượng</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0">
                        <div class="error error_quan text-danger small mt-1 d-none"></div>
                    </div>
                    <div class="mb-2">
                        <label for="unit_price" class="form-label">Đơn giá (vnđ)</label>
                        <input type="number" class="form-control" id="unit_price" name="unit_price" min="0">
                        <div class="error error_price text-danger small mt-1 d-none"></div>
                    </div>
                    <div class="mb-2">
                        <label for="unit_price" class="form-label">Thành tiền (vnđ)</label>
                        <input type="text" class="form-control" id="total_price" name="total_price" disabled>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="saveRepairBtn" class="btn btn-primary">Lưu</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ảnh bàn giao</h5>
                <button class="btn btn-primary btn-sm ms-3" onclick="triggerPhotoUpload(this)" data-id="{{ $data->id }}">Upload ảnh</button>
                <button class="btn btn-warning btn-sm ms-3 d-none" id="savePhotoBtn" onclick="UdatePhotos()" data-id="{{ $data->id }}">Lưu</button>
                <input type="file" id="photoUpload" accept="image/*" onchange="handlePhotoload(this)" multiple hidden>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div id="carouselImagePreview" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner" id="carouselInner"></div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselImagePreview" data-bs-slide="prev">
                        <span aria-hidden="true">
                            <svg width="24" height="24" fill="black" viewBox="0 0 16 16">
                                <path d="M11 1L3 8l8 7V1z" />
                            </svg>
                        </span>
                        <span class="visually-hidden">Trước</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselImagePreview" data-bs-slide="next">
                        <span aria-hidden="true">
                            <svg width="24" height="24" fill="black" viewBox="0 0 16 16">
                                <path d="M5 1l8 7-8 7V1z" />
                            </svg>
                        </span>
                        <span class="visually-hidden">Sau</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Video bàn giao</h5>
                <button class="btn btn-primary btn-sm ms-3" onclick="triggerVideoUpload(this)" data-id="{{ $data->id }}">Upload video</button>
                <input type="file" id="videoUpload" accept="video/*" style="display: none;" onchange="handleVideoUpload(this)">
                <button class="btn btn-warning btn-sm ms-3 d-none" id="saveVideoBtn" onclick="UdateVideo()" data-id="{{ $data->id }}">Lưu</button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body text-center" id="modalVideoBody">
            </div>
        </div>
    </div>
</div>
@include('components.status_modal')
<style>
    .timeline {
        position: relative;
        padding-left: 20px;
        margin-left: 15px;
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

    #replacement-suggestions {
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        background-color: #fff;
        border: 1px solid #ced4da;
    }
    
</style>
<script>
    $(document).ready(function() {
        $('.timeline-header, .timeline-details').click(function() {
            toggleTimelineDetails($(this).closest('.timeline-item'));
        });
        $('#quantity, #unit_price').on('input', updateTotalPrice);
        ChangeSelect();
        LuuQuaTrinhSua();
        PrintRequest();
    });
    
    function PrintRequest(){
        $('#printRequest').on('click', function(e){
            e.preventDefault();
            let id = $(this).data('id');
            $.ajax({
                url: "{{ url('/baohanh/request') }}/" + id,
                type: "GET",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã yêu cầu',
                            timer: 1500,
                            showConfirmButton: false
                        })
                    }
                },
                error: function (xhr) {
                    alert("Có lỗi xảy ra, vui lòng thử lại!");
                }
            });
        });
    }
    
    function ChangeSelect(){
        $('#solution').on('change', function(){
            if ($(this).val() === 'Sửa chữa tại chỗ (lỗi nhẹ)') {
                $('#des_error_container').closest('.mb-2').removeClass('d-none'); // Hiện
            } else {
                $('#des_error_container').closest('.mb-2').addClass('d-none'); // Ẩn
            }
        })
    }

    function ShowFormRepair() {
        resetRepairForm();
        $('#repairModal').modal('show');
    }

    // Reset form
    function resetRepairForm() {
        $('#repairForm')[0].reset();
        $('#repairForm .form-control').removeClass('is-invalid');
        $('#repairForm .error').text('').addClass('d-none');
    }

    function LuuQuaTrinhSua() {
        $('#saveRepairBtn').on('click', function(e) {
            e.preventDefault();
            if (validateRepairForm()) {
                let formData = $('#repairForm').serialize();
                OpenWaitBox();
                $.ajax({
                    url: '{{ route("warranty.updatedetail") }}',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        CloseWaitBox();
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            resetRepairForm();
                            $('#repairModal').modal('hide');
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        debugger;
                        CloseWaitBox();
                    }
                });

            } else {
                e.preventDefault();
            }
        });
    }

    function validateRepairForm() {
        let isValid = true;

        let errorType = $('#error_type').val().trim();
        if (errorType === '') {
            $('.error_type').text('Vui lòng nhập lỗi gặp phải').removeClass('d-none');
            isValid = false;
        } else {
            $('.error_type').text('').addClass('d-none');
        }

        let solution = $('#solution').val();
        if (!solution) {
            $('.error_sl').text('Vui lòng chọn cách xử lý').removeClass('d-none');
            isValid = false;
        } else {
            $('.error_sl').text('').addClass('d-none');
        }

        let replacement = $('#replacement').val().trim();
        if (solution === 'Thay thế linh kiện/hardware' && replacement === '') {
            $('.error_replace').text('Vui lòng nhập linh kiện thay thế').removeClass('d-none');
            isValid = false;
        } else {
            $('.error_replace').text('').addClass('d-none');
        }

        let quantity = parseInt($('#quantity').val()) || 0;
        if (solution === 'Thay thế linh kiện/hardware' && quantity < 1) {
            $('.error_quan').text('Số lượng phải >= 1').removeClass('d-none');
            isValid = false;
        } else {
            $('.error_quan').text('').addClass('d-none');
        }

        let price = parseInt($('#unit_price').val()) || 0;
        if (solution === 'Thay thế linh kiện/hardware' && price < 0) {
            $('.error_price').text('Đơn giá không hợp lệ').removeClass('d-none');
            isValid = false;
        } else {
            $('.error_price').text('').addClass('d-none');
        }
        return isValid;
    }

    function updateTotalPrice() {
        let quantity = parseInt($('#quantity').val()) || 0;
        let unitPrice = parseInt($('#unit_price').val()) || 0;
        let total = quantity * unitPrice;
        let formatted = total.toLocaleString('en-US', {
            minimumFractionDigits: 0
        });
        $('#total_price').val(formatted);
    }

    function toggleTimelineDetails(timelineItem) {
        const details = timelineItem.find('.timeline-details');

        $('.timeline-details').not(details).animate({
            height: 0
        }, 10);
        $('.toggle-details').not(timelineItem.find('.toggle-details')).text('▼');

        if (details.height() > 0) {
            details.animate({
                height: 0
            }, 10);
            timelineItem.find('.toggle-details').text('▼');
        } else {
            const fullHeight = details.prop('scrollHeight');
            details.animate({
                height: fullHeight
            }, 10);
            timelineItem.find('.toggle-details').text('▲');
        }
    }
</script>
<script>
    $(document).ready(function() {
        const replacementList = {!! json_encode($linhkien) !!};
        $('#replacement').on('input', function() {
            const keyword = $(this).val().toLowerCase().trim();
            const $suggestionsBox = $('#replacement-suggestions');
            $suggestionsBox.empty();

            if (!keyword) {
                $suggestionsBox.addClass('d-none');
                return;
            }
            const matchedReplacement = replacementList.filter(p =>
                p.product_name.toLowerCase().includes(keyword)
            );

            if (matchedReplacement.length > 0) {
                matchedReplacement.slice(0, 10).forEach(p => {
                    $suggestionsBox.append(
                        `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                    );
                });
                $suggestionsBox.removeClass('d-none');
            } else {
                $suggestionsBox.addClass('d-none');
            }
        });

        $(document).on('click', '#replacement-suggestions button', function() {
            $('#replacement').val($(this).text());
            $('#replacement-suggestions').addClass('d-none');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#replacement, #replacement-suggestions').length) {
                $('#replacement-suggestions').addClass('d-none');
            }
        });
    });

    function Edit(id) {
        const quatrinhsua = {!! json_encode($quatrinhsua) !!};
        const data = quatrinhsua.find(item => item.id === id);
        $('#id').val(data.id);
        $('#warranty_request_id').val(data.warranty_request_id);
        $('#error_type').val(data.error_type);
        $('#solution').val(data.solution);
        $('#replacement').val(data.replacement);
        $('#quantity').val(data.quantity);
        $('#unit_price').val(parseInt(data.unit_price));

        updateTotalPrice();

        $('#repairModal').modal('show');
    }

    function Delete(id) {
        Swal.fire({
            title: 'Xác nhận xoá?',
            text: "Bạn có chắc chắn muốn xoá bản ghi này?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xoá',
            cancelButtonText: 'Huỷ'
        }).then((result) => {
            if (result.isConfirmed) {
                OpenWaitBox();
                $.ajax({
                    url: '{{ route("warranty.delete") }}',
                    method: 'POST',
                    data: {
                        id: id
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        CloseWaitBox();
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã xoá!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        CloseWaitBox();
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: xhr.responseJSON?.message || 'Đã xảy ra lỗi khi xoá bản ghi.'
                        });
                    }
                });
            }
        });
    }

    $(document).on('click', '.edit-serial-icon', function() {
        const td = $(this).closest('td');
        td.find('.serial-text').hide();
        td.find('.serial-input').removeClass('d-none').focus();
    });

    $(document).on('blur', '.serial-input', function() {
        const input = $(this);
        const newValue = input.val();
        const td = input.closest('td');
        const type = td.data('type');
        const id = td.data('id');
        const currentValue = td.find('.serial-text').text().trim();

        if (newValue === currentValue) {
            input.addClass('d-none');
            td.find('.serial-text').show();
            return;
        }
        
        $.ajax({
            url: '{{ route("warranty.updateserial") }}',
            method: 'POST',
            data: {
                id: id,
                type: type,
                value: newValue
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if(response.success){
                    Swal.fire({
                        icon: 'success',
                        title: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        let displayValue = newValue;
                        if (type === 'return_date' || type === 'shipment_date') {
                            const date = new Date(newValue);
                            const day = String(date.getDate()).padStart(2, '0');
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const year = date.getFullYear();
                            displayValue = `${day}/${month}/${year}`;
                        }
                        td.find('.serial-text').text(displayValue).show();
                        input.addClass('d-none');
                    });
                }
                else{
                    Swal.fire({
                        icon: 'error',
                        title: response.message,
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        let displayValue = response.old_value;
                        if (type === 'return_date' || type === 'shipment_date') {
                            const date = new Date(response.old_value);
                            const day = String(date.getDate()).padStart(2, '0');
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const year = date.getFullYear();
                            displayValue = `${day}/${month}/${year}`;
                        }
                        td.find('.serial-text').text(displayValue).show();
                        input.val('');
                        input.addClass('d-none');
                    });
                }
            },
            error: function(xhr) {
                alert('Lỗi khi cập nhật serial!');
                input.focus();
            }
        });
    });
</script>
<script>
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

                // Nếu là link Google Drive
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
                        if (img.requestFullscreen) {
                            img.requestFullscreen();
                        } else if (img.webkitRequestFullscreen) {
                            img.webkitRequestFullscreen();
                        } else if (img.msRequestFullscreen) {
                            img.msRequestFullscreen();
                        }
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
        modalBody.innerHTML = ''; // Xoá nội dung cũ

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

    //thêm ảnh
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
        const formData = new FormData();
        const id = $('#savePhotoBtn').data('id');

        formData.append('id', id);

        selectedPhotos.forEach((file, index) => {
            formData.append('photos[]', file);
        });
        OpenWaitBox();
        $.ajax({
            url: '{{ route("photo.upload") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                CloseWaitBox()
                if (res.success) {
                    Swal.fire('Thành công', 'Lưu thành công!', 'success');
                    location.reload();
                } else {
                    alert('Lỗi: ' + res.message);
                }
            },
            error: function (err) {
                CloseWaitBox()
                console.error(err);
                alert('Đã xảy ra lỗi khi upload ảnh.');
            }
        });
    }

    //thêm video
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
        formData.append('video', file);
        OpenWaitBox();
        $.ajax({
            url: '{{ route("video.upload") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            processData: false,
            contentType: false,
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
</script>
<script>
    document.addEventListener('keydown', handlePrintShortcut);
    function handlePrintShortcut(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
            e.preventDefault();
            const iframe = document.getElementById('pdfViewer');
            try {
                if (iframe && iframe.contentWindow && typeof iframe.contentWindow.print === 'function') {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                }
            } catch (error) {
                console.error('Lỗi in:', error);
            }
        }
    }

    document.getElementById('pdfViewer').addEventListener('load', () => {
        try {
            const iframeWindow = document.getElementById('pdfViewer').contentWindow;
            iframeWindow.document.addEventListener('keydown', handlePrintShortcut);
        } catch (e) {
            console.warn('Không thể gắn listener bên trong iframe (có thể do cross-origin)');
        }
    });
    $(document).ready(function() {
        let isLoaded = false;
        const pdfSupported = isPdfSupported();
    
        if (!pdfSupported) {
            $('#downloadPdf').show();
        } else {
            $('#downloadPdf').hide();
        }
    });
    function isPdfSupported() {
    let ua = navigator.userAgent.toLowerCase();
    if (ua.indexOf('iphone') !== -1 || ua.indexOf('ipad') !== -1) {
        return false;
    }
    return !!navigator.mimeTypes['application/pdf'];
    }
</script>
@endsection