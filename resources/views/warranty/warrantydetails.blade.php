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
                        <label for="replacement" class="form-label" id="replacement-label">Linh kiện thay thế</label>
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
    window.updatedetailRoute = "{{ route('warranty.updatedetail') }}";
    window.deleteRoute = "{{ route('warranty.delete') }}";
    window.photoUploadRoute = "{{ route('photo.upload') }}";
    window.videoUploadRoute = "{{ route('video.upload') }}";
    window.requestPrintBase = "{{ url('/baohanh/request') }}";
    window.csrfToken = $('meta[name="csrf-token"]').attr('content');
    window.linhkienList = {!! json_encode($linhkien) !!};
    window.sanphamList = {!! json_encode($sanpham) !!};
    window.quatrinhsua = {!! json_encode($quatrinhsua) !!};
</script>
<script src="{{ asset('js/warranty/warrantydetails.js') }}"></script>
<script>
    window.updateSerialRoute = '{{ route("warranty.updateserial") }}';
</script>
<script src="{{ asset('js/warranty/warrantydetails_edit.js') }}"></script>
<script src="{{ asset('js/warranty/warrantydetails_media.js') }}"></script>
<script src="{{ asset('js/warranty/warrantydetails_pdf.js') }}"></script>
@endsection