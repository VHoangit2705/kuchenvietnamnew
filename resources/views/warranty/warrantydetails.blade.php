@extends('layout.layout')

<style>
    /* Giới hạn chiều cao modal và cho phép cuộn */
    .modal-content {
        max-height: 70vh;
        /* Giới hạn chiều cao modal là 70% chiều cao màn hình */
        display: flex;
        flex-direction: column;
    }

    .modal-body {
        overflow-y: auto;
        /* Cho phép cuộn nội dung modal body nếu quá dài */
    }

    .component-row {
        display: flex;
        flex-wrap: nowrap;
        align-items: flex-end;
    }

    /* --- QUY TẮC DESKTOP --- */

    .component-col-replacement {
        flex: 0 0 41.66666667%;
        padding-right: 8px;
        margin-right: 25px;
    }

    .component-col-quantity {
        flex: 0 0 25%;
        padding-right: 8px;
        margin-right: 25px;
    }

    .component-col-price {
        flex: 0 0 25%;
        padding-right: 8px;
        margin-right: 25px;
    }

    .component-col-delete {
        flex: 0 0 8.33333333%;
        text-align: center;
    }

    /* Ẩn nút xoá mobile trên desktop */
    .delete_mobile {
        display: none;
    }


    #repairForm .error {
        min-height: 1.2em;
        visibility: hidden;
    }

    .component-input-row {
        display: flex;
        width: 100%;
        flex-wrap: nowrap;
        align-items: center;
    }

    /* --- QUY TẮC MOBILE --- */
    @media (max-width: 767.98px) {

        .component-input-row {
            gap: 10px;
        }

        .component-scroll-container {
            flex-grow: 1;
            flex-shrink: 1;
            overflow-x: auto;
            overflow-y: hidden;
            min-width: 0;
            padding-bottom: 2px;
        }

        .component-row {
            gap: 8px;
        }

        .component-col-replacement {
            width: 200px;
            flex: 1 0 200px;
            padding-right: 0;
        }

        .component-col-quantity {
            width: 80px;
            flex: 0 0 80px;
            padding-right: 0;
        }

        .component-col-price {
            width: 100px;
            flex: 0 0 100px;
            padding-right: 0;
        }

        .component-col-delete {
            padding-bottom: 5px;
            flex-basis: auto;
            width: auto;
        }

        .component-input-row .form-label.d-md-none {
            display: block;
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .delete_destop {
            display: none;
        }

        .delete_mobile {
            display: block;
        }
    }

    .replacement-suggestions {
        max-height: 200px;
        overflow-y: auto;
        z-index: 1050;
    }

    .component-item-row {
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        padding: 4px 0;
    }

    .component-item-row:last-child {
        border-bottom: none;
    }
</style>

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
                                            <input type="text" class="serial-input form-control form-control-sm d-none"
                                                value="{{ $data->serial_thanmay }}"
                                                style="width: 150px; display: inline-block;">
                                            <img src="{{ asset('icons/pen.png') }}" alt="Chỉnh sửa seri thân máy"
                                                title="Chỉnh sửa seri thân máy" class="edit-serial-icon"
                                                style="height: 13px; cursor: pointer; margin-left: 5px;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Số seri tem bảo hành:</th>
                                        <td data-type="serial_number" data-id="{{ $data->id }}">
                                            <span class="serial-text">{{ $data->serial_number }}</span>
                                            <input type="text" class="serial-input form-control form-control-sm d-none"
                                                value="{{ $data->serial_number }}"
                                                style="width: 150px; display: inline-block;">
                                            <img src="{{ asset('icons/pen.png') }}" alt="Chỉnh sửa seri"
                                                title="Chỉnh sửa seri" class="edit-serial-icon"
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
                                        <td data-type="address" data-id="{{ $data->id }}">
                                            <span class="serial-text">{{ $data->address }}</span>
                                            <input type="text" class="serial-input form-control form-control-sm d-none"
                                                value="{{ $data->address }}" style="width: 150px; display: inline-block;">
                                            <img src="{{ asset('icons/pen.png') }}" alt="Chỉnh sửa địa chỉ"
                                                title="Chỉnh sửa địa chỉ" class="edit-serial-icon"
                                                style="height: 13px; cursor: pointer; margin-left: 5px;">
                                        </td>
                                    </tr>
                                    @if (isset($history) && count($history) > 1)
                                        <tr>
                                            <td colspan="2" class="text-center text-danger">Sản phẩm đã bảo hành lần
                                                {{ count($history) }}</td>
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
                                       <td>{{ $data->received_date ? \Carbon\Carbon::parse($data->received_date)->format('d/m/Y') : '-' }}</td>
                                   </tr>
                                    <tr>
                                        <th>Ngày hẹn trả:</th>
                                        <td data-type="return_date" data-id="{{ $data->id }}">
                                            <span
                                                class="serial-text">{{ \Carbon\Carbon::parse($data->return_date)->format('d/m/Y') }}</span>
                                            <input type="date" class="serial-input form-control form-control-sm d-none"
                                                value="{{ \Carbon\Carbon::parse($data->return_date)->format('Y-m-d') }}"
                                                style="width: 150px; display: inline-block;">
                                            <img src="{{ asset('icons/pen.png') }}" alt="Chỉnh sửa seri thân máy"
                                                title="Chỉnh sửa seri thân máy" class="edit-serial-icon"
                                                style="height: 13px; cursor: pointer; margin-left: 5px;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Ngày xuất kho:</th>
                                        <td data-type="shipment_date" data-id="{{ $data->id }}">
                                            <span
                                                class="serial-text">{{ \Carbon\Carbon::parse($data->shipment_date)->format('d/m/Y') }}</span>
                                            <input type="date" class="serial-input form-control form-control-sm d-none"
                                                value="{{ \Carbon\Carbon::parse($data->shipment_date)->format('Y-m-d') }}"
                                                style="width: 150px; display: inline-block;">
                                            <img src="{{ asset('icons/pen.png') }}" alt="Chỉnh sửa seri thân máy"
                                                title="Chỉnh sửa seri thân máy" class="edit-serial-icon"
                                                style="height: 13px; cursor: pointer; margin-left: 5px;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Trạng thái sửa chữa: </th>
                                        <td>
                                            <span style="cursor: pointer;"
                                                class="p-1 @if ($data->status == 'Đã hoàn tất') bg-success @elseif($data->status == 'Chờ KH phản hồi') bg-secondary @elseif($data->status == 'Đã tiếp nhận') bg-primary @elseif($data->status == 'Đã gửi linh kiện') bg-info @else bg-warning @endif"
                                                ?
                                                @if (($data->status != 'Đã hoàn tất' && session('user') == $data->staff_received) || session('position') == 'admin' || session('position') == 'quản trị viên') onclick="showStatusModal({{ $data->id }}, '{{ $data->status }}', '{{ $data->type }}', true)"
                                            @else
                                            onclick="showPermissionError()" @endif>
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
                    <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#quatrinhsua"
                        type="button" role="tab">
                        Quá trình sửa chữa
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab2" data-bs-toggle="tab" data-bs-target="#tinhtrangtiepnhan"
                        type="button" role="tab">
                        Tình trạng tiếp nhận
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab3" data-bs-toggle="tab" data-bs-target="#lichsubaohanh"
                        type="button" role="tab">
                        Lịch sử bảo hành
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab4" data-bs-toggle="tab" data-bs-target="#phieuin"
                        type="button" role="tab">
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
                                    <td>
                                        @if(is_array($item->replacement) || is_object($item->replacement))
                                            @php
                                                $components = is_array($item->replacement) ? $item->replacement : $item->replacement->toArray();
                                            @endphp
                                            @foreach($components as $index => $component)
                                                <div class="component-item-row">
                                                    <strong>{{ $component['number'] }}.</strong> {{ $component['name'] }}
                                                </div>
                                            @endforeach
                                        @else
                                            {{ $item->replacement }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(is_array($item->replacement) || is_object($item->replacement))
                                            @php
                                                $components = is_array($item->replacement) ? $item->replacement : $item->replacement->toArray();
                                            @endphp
                                            @foreach($components as $index => $component)
                                                <div class="component-item-row">{{ $component['quantity'] }}</div>
                                            @endforeach
                                        @else
                                            {{ $item->quantity }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(is_array($item->replacement) || is_object($item->replacement))
                                            @php
                                                $components = is_array($item->replacement) ? $item->replacement : $item->replacement->toArray();
                                            @endphp
                                            @foreach($components as $index => $component)
                                                <div class="component-item-row">{{ number_format($component['unit_price'], 0, ',', '.') }}</div>
                                            @endforeach
                                        @elseif($item->quantity > 0 && $item->unit_price !== null)
                                            {{ number_format($item->unit_price, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($item->total, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-warning btn-sm edit-row"
                                            onclick="Edit({{ $item->id }})">Sửa</button>
                                        <button class="btn btn-danger btn-sm delete-row"
                                            onclick="Delete({{ $item->id }})">Xóa</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="#" id="" class="btn d-flex align-items-center" title="Thêm quá trình mới"
                    style="width: 50px;"onclick="ShowFormRepair()">
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
                                <td><button class="btn btn-primary btn-sm"
                                        onclick="showImages('{{ $data->image_upload }}')">Xem ảnh</button></td>
                            </tr>
                            <tr>
                                <td>5. Video</td>
                                <td><button class="btn btn-primary btn-sm"
                                        onclick="showVideo('{{ $data->video_upload }}')">Xem video</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="lichsubaohanh" role="tabpanel" tabindex="0">
                <h5 class="fw-bold ms-3 mt-3">Lịch sử bảo hành</h5>
                <div class="timeline">
                    @if (isset($history) && count($history) > 0)
                        @foreach ($history as $item)
                            <div class="timeline-item">
                                <div class="timeline-header d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                            <a href="{{ route('warranty.detail', ['id' => $item->id]) }}" 
                                               title="Xem chi tiết tình trạng tiếp nhận và quá trình sửa chữa">
                                            Xem chi tiết
                                            </a>
                                        <p class="mb-1">
                                            <strong>Ngày tiếp nhận:</strong> 
                                            {{ \Carbon\Carbon::parse($item->received_date)->format('d/m/Y') }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>Tên sản phẩm:</strong> {{ $item->product ?? 'N/A' }}
                                        </p>
                                        @if($item->serial_number)
                                        <p class="mb-0">
                                            <strong>Mã bảo hành:</strong> {{ $item->serial_number }}
                                        </p>
                                        @endif
                                    </div>
                                    <button class="btn btn-link toggle-details ms-2">▼</button>
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

                                    @if ($warrantyTypeText)
                                        <p><strong>Hình thức bảo hành: </strong>{{ $warrantyTypeText }}</p>
                                    @endif

                                    @if ($item->type != 'agent_component' && $item->initial_fault_condition)
                                        <p><strong>Loại lỗi gặp phải: </strong>{{ $item->initial_fault_condition }}</p>
                                    @endif
                                    
                                    @if ($item->staff_received)
                                        <p><strong>Người tiếp nhận: </strong>{{ $item->staff_received }}</p>
                                    @endif
                                    
                                    <p class="mb-2"><strong>Tình trạng tiếp nhận:</strong></p>
                                    
                                    @if ($item->initial_fault_condition)
                                        <p class="mb-1">
                                            <strong>Lỗi khách hàng phản ánh khi bàn giao:</strong> 
                                            {{ $item->initial_fault_condition }}
                                        </p>
                                    @endif
                                    
                                    @if ($item->product_fault_condition)
                                        <p class="mb-1">
                                            <strong>Tình trạng bên ngoài của sản phẩm khi bàn giao:</strong> 
                                            {{ $item->product_fault_condition }}
                                        </p>
                                    @endif
                                    
                                    @if ($item->product_quantity_description)
                                        <p class="mb-1">
                                            <strong>Số linh kiện, sản phẩm khi nhận bàn giao:</strong> 
                                            {{ $item->product_quantity_description }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="tab-pane fade" id="phieuin" role="tabpanel" tabindex="0">
                <a id="downloadPdf" href="{{ route('warranty.dowloadpdf', ['id' => $data->id]) }}" download> Tải file PDF
                </a>
                <a id="printRequest" href="#" data-id="{{ $data->id }}" class="ms-5 my-1">Yêu cầu in
                    phiếu</a>
                <a id="printQr" href="#" data-id="{{ $data->id }}" class="ms-5 my-1">Yêu cầu in QR</a>
                <div class="d-flex justify-content-center align-items-center" style="height: 80vh;">
                    <div id="pdfLoading" class="text-center" style="display: none;">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                        <p class="mt-2">Đang tải phiếu bảo hành...</p>
                    </div>
                    <iframe id="pdfViewer" 
                        data-src="{{ route('warranty.pdf', ['id' => $data->id]) }}"
                        style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="repairModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm quá trình sửa chữa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <form id="repairForm" style="position: relative;">
                        <input hidden type="text" class="form-control" id="id" name="id" value="">
                        <input hidden type="text" class="form-control" id="warranty_request_id"
                            name="warranty_request_id" value="{{ $data->id }}">

                        <div class="mb-2">
                            <label for="error_type" class="form-label">Lỗi gặp phải</label>
                            <input type="text" class="form-control" id="error_type" name="error_type"
                                placeholder="Nhập lỗi gặp phải">
                            <div class="error error_type text-danger small mt-1"></div>
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
                            <div class="error error_sl text-danger small mt-1"></div>
                        </div>
                        <div id="des_error_container" class="mb-2 d-none">
                            <label for="des_error_type" class="form-label">Mô tả cách xử lý</label>
                            <textarea class="form-control" id="des_error_type" name="des_error_type"
                                placeholder="Nhập mô tả các xử lý" rows="4"></textarea>
                            <div class="error error_des text-danger small mt-1"></div>
                        </div>
                        <div id="rejection_reason_container" class="mb-2 d-none">
                            <label for="rejection_reason" class="form-label">Lý do từ chối bảo hành</label>
                            <input type="text" class="form-control" id="rejection_reason" name="rejection_reason"
                                placeholder="Nhập lý do từ chối">
                            <div class="error error_rejection_reason text-danger small mt-1"></div>
                        </div>

                        <div id="customer_refusal_reason_container" class="mb-2 d-none">
                            <label for="customer_refusal_reason" class="form-label">Lý do KH không muốn bảo hành</label>
                            <input type="text" class="form-control" id="customer_refusal_reason"
                                name="customer_refusal_reason" placeholder="Nhập lý do khách hàng từ chối">
                            <div class="error error_customer_refusal_reason text-danger small mt-1"></div>
                        </div>


                        <div id="components-container">
                            <hr class="my-3">

                            <div class="row g-2 mb-1 d-none d-md-flex">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Linh kiện thay thế</label>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Số lượng</label>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Đơn giá</label>
                                </div>
                                <div class="col-md-1">
                                </div>
                            </div>

                            <div class="component-row-wrapper border-bottom pb-2 mb-2">

                                <div class="component-input-row">
                                    <div class="component-scroll-container">
                                        <div class="component-row">

                                            <div class="component-col-replacement">
                                                <label class="form-label d-md-none">Linh kiện:</label>
                                                <div>
                                                    <input type="text" name="replacement[]"
                                                        class="form-control replacement-input"
                                                        placeholder="Nhập linh kiện..." id="replacement_0">
                                                    <div
                                                        class="replacement-suggestions list-group position-absolute w-100 d-none">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="component-col-quantity">
                                                <label class="form-label d-md-none">SL:</label>
                                                <input type="number" name="quantity[]"
                                                    class="form-control quantity-input" min="0" value="0"
                                                    placeholder="0" id="quantity_0">
                                            </div>

                                            <div class="component-col-price">
                                                <label class="form-label d-md-none">Đơn giá:</label>
                                                <input type="text" name="unit_price[]"
                                                    class="form-control unit-price-input" value="0" placeholder="0"
                                                    id="unit_price_0">
                                            </div>

                                        </div>
                                    </div>

                                    <div class="delete_destop component-col-delete">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-component-btn"
                                            title="Xoá linh kiện">
                                            Xoá
                                        </button>
                                    </div>
                                    <div class="delete_mobile component-col-delete">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-component-btn"
                                            title="Xoá linh kiện">
                                            Xoá
                                        </button>
                                    </div>
                                </div>

                                <div class="error component-error-row text-danger small mt-1"></div>
                            </div>
                        </div>

                        <button type="button" id="add-component-btn" class="btn btn-success btn-sm mt-2">
                            <i class="bi bi-plus-circle"></i> Thêm linh kiện
                        </button>

                        <div class="mb-2 mt-3"> <label for="unit_price" class="form-label">Thành tiền (vnđ)</label>
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
                    <button class="btn btn-primary btn-sm ms-3" onclick="triggerPhotoUpload(this)"
                        data-id="{{ $data->id }}">Upload ảnh</button>
                    <button class="btn btn-warning btn-sm ms-3 d-none" id="savePhotoBtn" onclick="UdatePhotos()"
                        data-id="{{ $data->id }}">Lưu</button>
                    <input type="file" id="photoUpload" accept="image/*" onchange="handlePhotoload(this)" multiple
                        hidden>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="carouselImagePreview" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner" id="carouselInner"></div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselImagePreview"
                            data-bs-slide="prev">
                            <span aria-hidden="true">
                                <svg width="24" height="24" fill="black" viewBox="0 0 16 16">
                                    <path d="M11 1L3 8l8 7V1z" />
                                </svg>
                            </span>
                            <span class="visually-hidden">Trước</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselImagePreview"
                            data-bs-slide="next">
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
                    <button class="btn btn-primary btn-sm ms-3" onclick="triggerVideoUpload(this)"
                        data-id="{{ $data->id }}">Upload video</button>
                    <input type="file" id="videoUpload" accept="video/*" style="display: none;"
                        onchange="handleVideoUpload(this)">
                    <button class="btn btn-warning btn-sm ms-3 d-none" id="saveVideoBtn" onclick="UdateVideo()"
                        data-id="{{ $data->id }}">Lưu</button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body text-center" id="modalVideoBody">
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">QR thanh toán</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qrLoading" class="mb-2 d-none">Đang tải QR...</div>
                    <img id="qrImage" src="" class="img-fluid mb-2 d-none" alt="QR thanh toán">
                    <div id="qrInfo" class="small text-muted d-none"></div>
                    <a id="qrDownloadLink" href="#" download class="btn btn-primary btn-sm d-none">Tải QR</a>
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
            $('#repairModal').on('input', '.quantity-input, .unit-price-input', updateTotalPrice);
            ChangeSelect();
            LuuQuaTrinhSua();
            PrintRequest();
            setupComponentActions();
            initQrModal();
            
            // Lazy load PDF iframe khi tab Phiếu in được click
            let pdfLoaded = false;
            const pdfViewer = document.getElementById('pdfViewer');
            const pdfLoading = document.getElementById('pdfLoading');
            
            // Lắng nghe sự kiện khi tab "phieuin" được show
            const phieuinTab = document.querySelector('#tab4');
            if (phieuinTab) {
                phieuinTab.addEventListener('shown.bs.tab', function() {
                    if (!pdfLoaded && pdfViewer && pdfViewer.dataset.src) {
                        pdfLoading.style.display = 'block';
                        pdfViewer.style.display = 'none';
                        
                        pdfViewer.src = pdfViewer.dataset.src;
                        pdfLoaded = true;
                        
                        pdfViewer.addEventListener('load', function() {
                            pdfLoading.style.display = 'none';
                            pdfViewer.style.display = 'block';
                        }, { once: true });
                    }
                });
            }
            
        });

        function PrintRequest() {
            $('#printRequest').on('click', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                $.ajax({
                    url: "{{ url('/baohanh/request') }}/" + id,
                    type: "GET",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã yêu cầu',
                                timer: 1500,
                                showConfirmButton: false
                            })
                        }
                    },
                    error: function(xhr) {
                        alert("Có lỗi xảy ra, vui lòng thử lại!");
                    }
                });
            });
        }

        function initQrModal() {
            const qrRouteTemplate = "{{ route('warranty.qr', ['id' => '__ID__']) }}";
            $('#printQr').on('click', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const url = qrRouteTemplate.replace('__ID__', id);
                $('#qrLoading').removeClass('d-none');
                $('#qrImage').addClass('d-none');
                $('#qrInfo').addClass('d-none');
                $('#qrModal').modal('show');
                $.get(url).done(function(response) {
                    if (response.success) {
                        $('#qrImage').attr('src', response.data.image).removeClass('d-none');
                        const infoHtml = [
                            '<strong>NỘI DUNG:</strong>' + ((response.data.description || '').toUpperCase()) + '<br>',
                            'Số tiền: ' + new Intl.NumberFormat('vi-VN').format(response.data.amount) + ' đ',
                        ].join(' | ');
                        $('#qrInfo').html(infoHtml).removeClass('d-none');
                        buildQrDownload(response.data);
                        $('#qrLoading').addClass('d-none');
                    } else {
                        $('#qrLoading').text(response.message || 'Không thể tải QR.');
                    }
                }).fail(function(xhr) {
                    $('#qrLoading').text(xhr.responseJSON?.message || 'Lỗi khi tải QR.');
                });
                $('#qrModal').on('hidden.bs.modal', function() {
                    $('#qrDownloadLink').addClass('d-none');
                });
            });
        }

        function buildQrDownload(data) {
            const img = new Image();
            img.onload = function() {
                const padding = 20;
                const infoLines = [
                    'NỘI DUNG: ' + (data.description || '').toUpperCase(),
                    'SỐ TIỀN: ' + new Intl.NumberFormat('vi-VN').format(data.amount) + ' Đ'
                ].filter(Boolean);
                const extraHeight = padding * (infoLines.length + 1);
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height + extraHeight;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, img.width, img.height);
                ctx.fillStyle = '#000000';
                ctx.font = 'bold 20px Arial';
                ctx.textAlign = 'center';
                infoLines.forEach((line, index) => {
                    ctx.fillText(line, canvas.width / 2, img.height + padding * (index + 1));
                });
                $('#qrDownloadLink')
                    .attr('href', canvas.toDataURL('image/png'))
                    .attr('download', `QR-${data.title}.png`)
                    .removeClass('d-none');
            };
            img.src = data.image;
        }

        function ChangeSelect() {
            $('#solution').on('change', function() {
                const selectedValue = $(this).val();
                const $desError = $('#des_error_container');
                const $rejectionReason = $('#rejection_reason_container');
                const $customerRefusal = $('#customer_refusal_reason_container');
                const $components = $('#components-container');
                const $addComponentBtn = $('#add-component-btn');
                const $totalPrice = $('#total_price').closest('.mb-2');

                // Reset tất cả các trường đặc biệt
                $desError.addClass('d-none');
                $rejectionReason.addClass('d-none');
                $customerRefusal.addClass('d-none');

                // Mặc định hiện linh kiện
                $components.removeClass('d-none');
                $addComponentBtn.removeClass('d-none');
                $totalPrice.removeClass('d-none');

                if (selectedValue === 'Sửa chữa tại chỗ (lỗi nhẹ)') {
                    $desError.removeClass('d-none');
                    $components.addClass('d-none');
                    $addComponentBtn.addClass('d-none');
                    $totalPrice.addClass('d-none');
                    // Xóa giá trị trong các input linh kiện để tránh gửi nhầm
                    $('.replacement-input').val('');
                    $('.quantity-input').val('0');
                    $('.unit-price-input').val('0');
                } else if (selectedValue === 'Từ chối bảo hành') {
                    $rejectionReason.removeClass('d-none');
                    $components.addClass('d-none');
                    $addComponentBtn.addClass('d-none');
                    $totalPrice.addClass('d-none');
                } else if (selectedValue === 'KH không muốn bảo hành') {
                    $customerRefusal.removeClass('d-none');
                    $components.addClass('d-none');
                    $addComponentBtn.addClass('d-none');
                    $totalPrice.addClass('d-none');
                } else if (selectedValue === 'Gửi về trung tâm bảo hành NSX') {
                    $components.addClass('d-none');
                    $addComponentBtn.addClass('d-none');
                    $totalPrice.addClass('d-none');
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

            // Kích hoạt sự kiện change để ẩn/hiện các trường đúng theo giá trị mặc định
            $('#solution').trigger('change');

            $('#id').val(''); // Đảm bảo ID được xóa

            // Xóa tất cả các dòng linh kiện trừ dòng đầu tiên
            $('.component-row-wrapper:not(:first)').remove();

            // Reset giá trị và lỗi của dòng đầu tiên
            const $firstRow = $('.component-row-wrapper:first');
            $firstRow.find('input').val('');
            $firstRow.find('input[name="quantity[]"]').val('0');
            $firstRow.find('input[name="unit_price[]"]').val('0');
            $firstRow.find('.remove-component-btn').hide(); // Ẩn nút xoá

            // Ẩn tất cả các thông báo lỗi
            $('#repairForm .error').text('').css('visibility', 'hidden');


            repairFormErrors = {}; // Reset cờ lỗi
            updateSaveButtonState(); // Cập nhật trạng thái nút Lưu
        }

        function LuuQuaTrinhSua() {
            $('#saveRepairBtn').on('click', function(e) {
                e.preventDefault();
                if (validateRepairForm()) {

                    // 1. Dùng serializeArray để lấy tất cả dữ liệu form
                    let formDataArray = $('#repairForm').serializeArray();
                    let dataToSend = {};
                    let replacements = [];
                    let quantities = [];
                    let unitPrices = [];

                    // 2. Gom nhóm các trường
                    formDataArray.forEach(function(item) {
                        if (item.name === 'replacement[]') {
                            replacements.push(item.value);
                        } else if (item.name === 'quantity[]') {
                            quantities.push(item.value ? parseInt(item.value) : 0);
                        } else if (item.name === 'unit_price[]') {
                            const rawPrice = item.value.replace(/[^0-9]/g, '');
                            unitPrices.push(rawPrice ? parseInt(rawPrice) : 0);
                        } else {
                            // Giữ lại các trường khác (id, error_type, solution...)
                            dataToSend[item.name] = item.value;
                        }
                    });

                    // 3. Thêm các mảng vào dataToSend
                    // Nếu là "Sửa chữa tại chỗ (lỗi nhẹ)", không gửi replacement array
                    if (dataToSend['solution'] === 'Sửa chữa tại chỗ (lỗi nhẹ)') {
                        // Không gửi replacement, quantity, unit_price cho trường hợp này
                        dataToSend['replacement'] = null;
                        dataToSend['quantity'] = null;
                        dataToSend['unit_price'] = null;
                    } else {
                        dataToSend['replacement'] = replacements;
                        dataToSend['quantity'] = quantities;
                        dataToSend['unit_price'] = unitPrices;
                    }

                    if (!dataToSend['_token']) {
                        dataToSend['_token'] = '{{ csrf_token() }}';
                    }

                    OpenWaitBox();
                    $.ajax({
                        url: '{{ route('warranty.updatedetail') }}',
                        method: 'POST',
                        data: dataToSend, // Gửi object chứa các mảng
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
                            CloseWaitBox();
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                // Ẩn lỗi cũ
                                $('#repairForm .component-row-wrapper .error').text('').css(
                                    'visibility', 'hidden');
                                $('#repairForm .error:not(.component-row-wrapper .error)').text('')
                                    .addClass('d-none');

                                let errorMessages = [];
                                $.each(errors, function(key, value) {
                                    const parts = key.split('.'); // VD: "replacement.0"
                                    let $errorDiv;

                                    if (parts.length === 2) {
                                        // Xử lý lỗi cho mảng (VD: replacement.0)
                                        const fieldName = parts[0]; // replacement
                                        const index = parts[1]; // 0
                                        const $row = $(`.component-row-wrapper:eq(${index})`);

                                        $errorDiv = $row.find(
                                            `.error_${fieldName.replace('[]', '')}`);

                                        // Dùng logic tìm ô lỗi cá nhân (vì file bạn đang dùng)
                                        if (fieldName === 'replacement') $errorDiv = $row.find(
                                            '.error_replace');
                                        else if (fieldName === 'quantity') $errorDiv = $row
                                            .find('.error_quan');
                                        else if (fieldName === 'unit_price') $errorDiv = $row
                                            .find('.error_price');

                                    } else {
                                        // Lỗi trường đơn (error_type, solution)
                                        let fieldClass = key === 'solution' ? 'sl' : key;
                                        $errorDiv = $('#repairForm .error_' + fieldClass);
                                    }

                                    // Hiển thị lỗi
                                    if ($errorDiv && $errorDiv.length) {
                                        if ($errorDiv.closest('.component-row-wrapper').length >
                                            0) {
                                            $errorDiv.text(value[0]).css('visibility',
                                                'visible');
                                        } else {
                                            $errorDiv.text(value[0]).removeClass('d-none');
                                        }
                                    }
                                    errorMessages.push(value[0]);
                                });

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Dữ liệu không hợp lệ',
                                    html: 'Vui lòng kiểm tra lại các trường:<br>' +
                                        errorMessages.join('<br>'),
                                    showConfirmButton: true
                                });

                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: 'Đã xảy ra lỗi không xác định. Vui lòng thử lại.' +
                                        xhr.statusText,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        }
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi nhập liệu',
                        text: 'Vui lòng kiểm tra và sửa các thông tin được báo lỗi.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
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
        // Lấy ngày tiếp nhận để so sánh
        const receivedDate = new Date('{{ $data->received_date }}');
        receivedDate.setHours(0, 0, 0, 0); // Chuẩn hóa về 0h để so sánh ngày

        // Hàm hiển thị lỗi
        function showError(input, message) {
            input.addClass('is-invalid');
            // Thêm message lỗi ngay sau input
            let errorDiv = `<div class="invalid-feedback d-block">${message}</div>`;
            input.after(errorDiv);
        }

        // Hàm ẩn lỗi
        function hideError(input) {
            input.removeClass('is-invalid');
            input.siblings('.invalid-feedback').remove();
        }

        // 1. Validate số seri thân máy (chữ và số, max 25)
        function validateSerialThanMay(input) {
            const value = input.val();
            hideError(input);
            if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
                showError(input, "Chỉ cho phép nhập chữ và số.");
                return false;
            }
            if (value.length > 25) {
                showError(input, "Tối đa 25 ký tự.");
                return false;
            }
            return true;
        }

        // 2. Validate số seri tem bảo hành (số, max 25)
        function validateSerialNumber(input) {
            const value = input.val();
            hideError(input);
            if (value && !/^\d+$/.test(value)) {
                showError(input, "Chỉ cho phép nhập số.");
                return false;
            }
            if (value.length > 25) {
                showError(input, "Tối đa 25 ký tự.");
                return false;
            }
            return true;
        }

        // 3. Validate ngày hẹn trả (>= ngày tiếp nhận)
        function validateReturnDate(input) {
            const selectedDate = new Date(input.val());
            selectedDate.setHours(0, 0, 0, 0); // Chuẩn hóa về 0h để so sánh ngày
            hideError(input);
            // So sánh ngày, bỏ qua giờ
            if (selectedDate.getTime() < receivedDate.getTime()) {
                showError(input, "Ngày hẹn trả không được sớm hơn ngày tiếp nhận.");
                return false;
            }
            return true;
        }

        // 4. Validate địa chỉ (chữ, số, (),.- , max 100)
        function validateAddress(input) {
            const value = input.val();
            hideError(input);
            const regex =
                /^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ().,\-]+$/;
            if (value && !regex.test(value)) {
                showError(input, "Chỉ cho phép chữ, số và các ký tự '().,-'");
                return false;
            }
            if (value.length > 100) {
                showError(input, "Tối đa 100 ký tự.");
                return false;
            }
            return true;
        }

        $(document).ready(function() {
            const componentList = {!! json_encode($linhkien) !!}; // view = 2
            const productList = {!! json_encode($sanpham) !!}; // view = 1 hoặc 3
            let currentReplacementList = componentList; // Mặc định là linh kiện
            let $activeSuggestionInput = null; // Biến toàn cục để lưu input đang gõ
            let $activeSuggestionBox = null; // Biến toàn cục để lưu box gợi ý

            // Hàm để gắn lại box gợi ý về chỗ cũ
            function reattachSuggestionBox() {
                if ($activeSuggestionInput && $activeSuggestionBox) {
                    $activeSuggestionBox.addClass('d-none').css({
                        top: '',
                        left: '',
                        width: '',
                        position: ''
                    });
                    // Gắn lại vào div cha của input
                    $activeSuggestionInput.parent().append($activeSuggestionBox);
                }
                $activeSuggestionInput = null;
                $activeSuggestionBox = null;
            }

            $('#components-container').on('input', '.replacement-input', function() {
                const $input = $(this);
                const keyword = $input.val().toLowerCase().trim();

                // Gắn lại box cũ nếu có
                reattachSuggestionBox();

                // Lưu lại input và box hiện tại
                $activeSuggestionInput = $input;
                $activeSuggestionBox = $input.siblings('.replacement-suggestions');

                const $form = $input.closest('form');
                $activeSuggestionBox.empty();

                if (!keyword) {
                    $activeSuggestionBox.addClass('d-none');
                    return;
                }

                // Lọc
                const matchedReplacement = currentReplacementList.filter(p =>
                    p.product_name.toLowerCase().includes(keyword)
                );

                if (matchedReplacement.length > 0) {
                    matchedReplacement.slice(0, 10).forEach(p => {
                        const button =
                            `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`;
                        $activeSuggestionBox.append(button);
                    });

                    // 1. Lấy vị trí input và form
                    const inputOffset = $input.offset();
                    const formOffset = $form.offset();

                    // 2. Di chuyển box ra khỏi vùng cuộn và gắn vào form
                    $form.append($activeSuggestionBox);

                    // 3. Set vị trí tuyệt đối cho box (so với form)
                    $activeSuggestionBox.css({
                        position: 'absolute',
                        top: (inputOffset.top - formOffset.top) + $input.outerHeight() + 'px',
                        left: (inputOffset.left - formOffset.left) + 'px',
                        width: $input.outerWidth() + 'px',
                        zIndex: 1051 // Đảm bảo nổi trên modal
                    });

                    $activeSuggestionBox.removeClass('d-none');
                } else {
                    $activeSuggestionBox.addClass('d-none');
                }
            });

            // Dùng $(document) vì box gợi ý giờ nằm ngoài #components-container
            $(document).on('click', '.replacement-suggestions button', function(e) {
                e.preventDefault();
                const $button = $(this);

                if ($activeSuggestionInput) {
                    $activeSuggestionInput.val($button.text());
                    $activeSuggestionInput.trigger('input'); // Kích hoạt validation
                }

                reattachSuggestionBox(); // Gắn lại box về chỗ cũ
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.replacement-input, .replacement-suggestions').length) {
                    // Nếu click ra ngoài, gắn lại box
                    reattachSuggestionBox();
                }
            });

            // Cập nhật danh sách gợi ý khi thay đổi "Cách xử lý"
            $('#solution').on('change', function() {
                const selectedValue = $(this).val();
                if (selectedValue === 'Đổi mới sản phẩm') {
                    currentReplacementList = productList;
                } else {
                    currentReplacementList = componentList;
                }
                // Xóa gợi ý hiện tại nếu có
                $('.replacement-suggestions').addClass('d-none').empty();
            });
        });

        function setupComponentActions() {
            // Sự kiện khi bấm nút THÊM
            $('#add-component-btn').on('click', function() {
                const lastRowIndex = $('.component-row-wrapper').length;

                // 1. Clone (sao chép) TOÀN BỘ .component-row-wrapper ĐẦU TIÊN
                const $newRowWrapper = $('.component-row-wrapper:first').clone(true);

                $newRowWrapper.find('input').val(''); // Xóa giá trị
                $newRowWrapper.find('input[name="quantity[]"]').val('0'); // Set SL = 0
                $newRowWrapper.find('input[name="unit_price[]"]').val('0'); // Set Giá = 0
                // Sửa logic: Dùng visibility
                $newRowWrapper.find('.error').text('').css('visibility', 'hidden');

                $newRowWrapper.find('.replacement-input').attr('id', 'replacement_' + lastRowIndex);
                $newRowWrapper.find('.quantity-input').attr('id', 'quantity_' + lastRowIndex);
                $newRowWrapper.find('.unit-price-input').attr('id', 'unit_price_' + lastRowIndex);

                $newRowWrapper.find('.remove-component-btn').show();

                // 5. Thêm dòng mới vào ĐÚNG vùng chứa
                $('#components-container').append($newRowWrapper);

                updateTotalPrice(); // Tính lại tổng tiền
            });

            // Sự kiện khi bấm nút XOÁ (X)
            $('#components-container').on('click', '.remove-component-btn', function() {
                // Tìm .component-row-wrapper gần nhất
                const $rowWrapper = $(this).closest('.component-row-wrapper');

                // Chỉ xoá khi có nhiều hơn 1 dòng
                if ($('.component-row-wrapper').length > 1) {
                    $rowWrapper.remove();
                } else {
                    // Nếu là dòng cuối cùng, chỉ xoá dữ liệu
                    $rowWrapper.find('input').val('');
                    $rowWrapper.find('input[name="quantity[]"]').val('0');
                    $rowWrapper.find('input[name="unit_price[]"]').val('0');
                    // Sửa logic: Dùng visibility
                    $rowWrapper.find('.error').text('').css('visibility', 'hidden');
                }
                updateTotalPrice(); // Tính lại tổng tiền
            });

            // Ẩn nút xoá của dòng đầu tiên khi tải trang
            $('.component-row-wrapper:first .remove-component-btn').hide();
        }

        function Edit(id) {
            // Sử dụng dữ liệu gốc (chưa nhóm) từ quatrinhsuaRaw
            const quatrinhsuaRaw = {!! json_encode($quatrinhsuaRaw) !!};
            const quatrinhsua = {!! json_encode($quatrinhsua) !!};
            const selectedData = quatrinhsua.find(item => item.id === id);
            resetRepairForm(); // Reset form trước khi điền
            
            // Tìm tất cả các bản ghi gốc liên quan (cùng error_type, solution, warranty_request_id, và Ngaytao)
            const relatedRecords = quatrinhsuaRaw.filter(item => 
                item.warranty_request_id === selectedData.warranty_request_id &&
                item.error_type === selectedData.error_type &&
                item.solution === selectedData.solution &&
                item.Ngaytao === selectedData.Ngaytao
            );

            $('#id').val(id);
            $('#warranty_request_id').val(selectedData.warranty_request_id);
            $('#error_type').val(selectedData.error_type);
            $('#solution').val(selectedData.solution);
            
            // Điền mô tả nếu là "Sửa chữa tại chỗ (lỗi nhẹ)"
            if (selectedData.solution === 'Sửa chữa tại chỗ (lỗi nhẹ)') {
                // Lấy replacement từ bản ghi đầu tiên (vì đã được nhóm)
                const firstRecord = relatedRecords.length > 0 ? relatedRecords[0] : null;
                if (firstRecord && firstRecord.replacement) {
                    $('#des_error_type').val(firstRecord.replacement);
                }
                // Xóa tất cả các dòng linh kiện và để trống
                $('.component-row-wrapper').remove();
            } else {
                // Xóa tất cả các dòng linh kiện trừ dòng đầu tiên
                $('.component-row-wrapper:not(:first)').remove();

                // Điền dữ liệu vào các dòng từ dữ liệu gốc
                if (relatedRecords.length > 0) {
                    // Điền dòng đầu tiên
                    const $firstRow = $('.component-row-wrapper:first');
                    fillComponentRow($firstRow, relatedRecords[0], 0);
                    
                    // Thêm các dòng còn lại
                    for (let i = 1; i < relatedRecords.length; i++) {
                        $('#add-component-btn').trigger('click');
                        const $newRow = $('.component-row-wrapper:last');
                        fillComponentRow($newRow, relatedRecords[i], i);
                    }
                } else {
                    // Nếu không có bản ghi nào, giữ lại dòng đầu tiên trống
                    const $firstRow = $('.component-row-wrapper:first');
                    fillComponentRow($firstRow, { replacement: '', quantity: 0, unit_price: 0 }, 0);
                }
            }

            // Kích hoạt sự kiện change để ẩn/hiện các trường đúng
            $('#solution').trigger('change');

            updateTotalPrice();

            $('#repairModal').modal('show');
        }

        // Hàm helper để điền dữ liệu vào một dòng linh kiện
        function fillComponentRow($row, record, index) {
            $row.find('.replacement-input').val(record.replacement || '');
            $row.find('.quantity-input').val(record.quantity || 0);
            const unitPriceInput = $row.find('.unit-price-input');
            unitPriceInput.val(record.unit_price || 0);
            formatCurrency(unitPriceInput);
            
            // Cập nhật ID cho các input
            $row.find('.replacement-input').attr('id', 'replacement_' + index);
            $row.find('.quantity-input').attr('id', 'quantity_' + index);
            $row.find('.unit-price-input').attr('id', 'unit_price_' + index);
            
            // Hiển thị nút xóa nếu không phải dòng đầu tiên
            if (index > 0) {
                $row.find('.remove-component-btn').show();
            } else {
                $row.find('.remove-component-btn').hide();
            }
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
                        url: '{{ route('warranty.delete') }}',
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
                                text: xhr.responseJSON?.message ||
                                    'Đã xảy ra lỗi khi xoá bản ghi.'
                            });
                        }
                    });
                }
            });
        }

        $(document).on('click', '.edit-serial-icon', function() {
            const td = $(this).closest('td');
            td.find('.serial-text').hide();
            $(this).hide(); // Ẩn icon bút chì khi bắt đầu sửa
            td.find('.serial-input').removeClass('d-none').focus();
        });

        $(document).on('input', '.serial-input', function() {
            const input = $(this);
            const type = input.closest('td').data('type');
            let isValid = true;

            // Chạy validation tương ứng để hiển thị/ẩn lỗi trực tiếp
            switch (type) {
                case 'serial_thanmay':
                    isValid = validateSerialThanMay(input);
                    break;
                case 'serial_number':
                    isValid = validateSerialNumber(input);
                    break;
                case 'return_date':
                    isValid = validateReturnDate(input);
                    break;
                case 'address':
                    isValid = validateAddress(input);
                    break;
            }
        });

        $(document).on('blur', '.serial-input', function() {
            const input = $(this);
            const newValue = input.val();
            const td = input.closest('td');
            const type = td.data('type');
            const id = td.data('id');
            const currentValue = td.find('.serial-text').text().trim();
            let isValid = true;

            // Chạy validation tương ứng
            switch (type) {
                case 'serial_thanmay':
                    isValid = validateSerialThanMay(input);
                    break;
                case 'serial_number':
                    isValid = validateSerialNumber(input);
                    break;
                case 'return_date':
                    isValid = validateReturnDate(input);
                    break;
                case 'address':
                    isValid = validateAddress(input);
                    break;
            }

            // Nếu không hợp lệ, hiển thị thông báo, hoàn lại giá trị cũ và không cập nhật
            if (!isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Dữ liệu không hợp lệ',
                    text: 'Vui lòng kiểm tra lại dữ liệu nhập.',
                    timer: 2000,
                    showConfirmButton: false
                });


                // Gỡ hiển thị lõi để giúp người dùng dễ nhìn hơn
                td.find('.invalid-feedback').remove();

                // 3. Hoàn lại giá trị cũ
                input.addClass('d-none');
                td.find('.serial-text').show();
                td.find('.edit-serial-icon').show();
                return;
            }

            // Nếu giá trị không đổi, không làm gì cả
            if (newValue === currentValue) {
                input.addClass('d-none');
                td.find('.serial-text').show();
                td.find('.edit-serial-icon').show();
                return;
            }

            $.ajax({
                url: '{{ route('warranty.updateserial') }}',
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
                    if (response.success) {
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
                            td.find('.edit-serial-icon').show();
                        });
                    } else {
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
                            td.find('.edit-serial-icon').show();
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

            if (!videoPath) {
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

        $('#videoModal').on('hidden.bs.modal', function() {
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

            const maxSize = 15 * 1024 * 1024;
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
                reader.onload = function(e) {
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
                url: '{{ route('photo.upload') }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    CloseWaitBox()
                    if (res.success) {
                        Swal.fire('Thành công', 'Lưu thành công!', 'success');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + res.message);
                    }
                },
                error: function(err) {
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
                url: '{{ route('video.upload') }}',
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

        // Chỉ gắn listener khi iframe được load (lazy load)
        const pdfViewer = document.getElementById('pdfViewer');
        if (pdfViewer) {
            pdfViewer.addEventListener('load', () => {
                try {
                    const iframeWindow = pdfViewer.contentWindow;
                    if (iframeWindow && iframeWindow.document) {
                        iframeWindow.document.addEventListener('keydown', handlePrintShortcut);
                    }
                } catch (e) {
                    console.warn('Không thể gắn listener bên trong iframe (có thể do cross-origin)');
                }
            }, { once: true });
        }
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
    <script>
        // 1. Cờ theo dõi trạng thái lỗi của form sửa chữa
        let repairFormErrors = {};

        // 2. Hàm hiển thị lỗi cho một trường trong form
        function showRepairFormError($field, message) {
            const fieldId = $field.attr('id');
            if (!fieldId) return;

            hideRepairFormError($field); // Xóa lỗi cũ

            let $errorDiv;
            const $wrapper = $field.closest('.component-row-wrapper');

            if ($wrapper.length > 0) {
                // Nếu là lỗi linh kiện, dùng ô chung
                $errorDiv = $wrapper.find('.component-error-row');
            } else {
                // Nếu là lỗi khác (Lỗi gặp phải, Cách xử lý)
                $errorDiv = $field.siblings('.error');
                if ($errorDiv.length === 0) $errorDiv = $field.closest('div').find('.error');
            }

            $errorDiv.text(message).css('visibility', 'visible'); // Luôn dùng VISIBILITY

            repairFormErrors[fieldId] = true; // Gắn cờ lỗi
            updateSaveButtonState();
        }

        // 3. Hàm ẩn lỗi cho một trường trong form
        function hideRepairFormError($field) {
            const fieldId = $field.attr('id');
            if (!fieldId) return;

            let $errorDiv;
            const $wrapper = $field.closest('.component-row-wrapper');

            if ($wrapper.length > 0) {
                // Nếu là lỗi linh kiện, kiểm tra các lỗi khác CÙNG HÀNG
                delete repairFormErrors[fieldId]; // Xóa lỗi của trường HIỆN TẠI

                $errorDiv = $wrapper.find('.component-error-row');

                const $rep = $wrapper.find('.replacement-input');
                const $qty = $wrapper.find('.quantity-input');
                const $price = $wrapper.find('.unit-price-input');

                // Re-validate trường khác nếu nó CÓ LỖI, để hiển thị lại lỗi đó
                if (repairFormErrors[$rep.attr('id')]) {
                    validateReplacement($rep);
                    return;
                }
                if (repairFormErrors[$qty.attr('id')]) {
                    validateQuantity($qty);
                    return;
                }
                if (repairFormErrors[$price.attr('id')]) {
                    validateUnitPrice($price);
                    return;
                }

                // Nếu không còn lỗi nào khác, ẩn ô chung
                $errorDiv.text('').css('visibility', 'hidden');

            } else {
                // Nếu là lỗi khác
                $errorDiv = $field.siblings('.error');
                if ($errorDiv.length === 0) $errorDiv = $field.closest('div').find('.error');
                $errorDiv.text('').css('visibility', 'hidden'); // Luôn dùng VISIBILITY
            }

            // Bỏ cờ lỗi (có thể đã bị xóa ở trên, nhưng ở đây để an toàn)
            delete repairFormErrors[fieldId];
            updateSaveButtonState();
        }

        // 4. Hàm cập nhật trạng thái nút "Lưu"
        function updateSaveButtonState() {
            const hasErrors = Object.keys(repairFormErrors).length > 0;
            $('#saveRepairBtn').prop('disabled', hasErrors);
        }


        // Lỗi gặp phải: chữ và số, max 150
         function validateErrorType() {
  const $input = $('#error_type');
  const value = $input.val().trim();
  hideRepairFormError($input);

  // Regex: cho phép chữ cái (có dấu), số và khoảng trắng
  const regex = /^[0-9a-zA-ZÀ-ỹ\s]+$/;

  if (value && !regex.test(value)) {
    showRepairFormError($input, "Chỉ được nhập chữ và số.");
  } else if (value.length > 150) {
    showRepairFormError($input, "Tối đa 150 ký tự.");
  }
}

        // Mô tả cách xử lý: chữ và số, max 100
        function validateDescription($input) {
    const value = ($input.val() || "").trim();
    hideRepairFormError($input);

    const regex = /^[\p{L}\p{N}\s,.\-]+$/u;

    if (value && !regex.test(value)) {
        return showRepairFormError($input, "Chỉ được nhập chữ và số.");
    }

    if (value.length > 500) {
        return showRepairFormError($input, "Tối đa 500 ký tự.");
    }
}


        // Lý do từ chối: chữ và số, max 100
        function validateRejectionReason($input) {
            const value = $input.val().trim();
            hideRepairFormError($input);
            if (value && !
                /^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ,.\- ]+$/
                .test(value)) {
                showRepairFormError($input, "Chỉ được nhập chữ, số và ký tự ,.-");
            } else if (value.length > 100) {
                showRepairFormError($input, "Tối đa 100 ký tự.");
            }
        }

        // Lý do KH từ chối: chữ và số, max 100
        function validateCustomerRefusalReason($input) {
            const value = $input.val().trim();
            hideRepairFormError($input);
            if (value && !
                /^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ,.\- ]+$/
                .test(value)) {
                showRepairFormError($input, "Chỉ được nhập chữ, số và ký tự ,.-");
            } else if (value.length > 100) {
                showRepairFormError($input, "Tối đa 100 ký tự.");
            }
        }

        // Linh kiện thay thế: chữ và số, max 100
        function validateReplacement($input) {
            const value = $input.val().trim();
            hideRepairFormError($input);
            if (value && !
                /^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ\-\_:;=+/,.() ]+$/
                .test(value)) {
                showRepairFormError($input, "Chỉ được nhập chữ và số.");
            } else if (value.length > 100) {
                showRepairFormError($input, "Tối đa 100 ký tự.");
            }
        }

        // Số lượng: số, max 5
        function validateQuantity($input) {
            const value = $input.val();
            hideRepairFormError($input);
            if (value && !/^\d+$/.test(value)) {
                showRepairFormError($input, "Chỉ được nhập số.");
            } else if (value.length > 5) {
                showRepairFormError($input, "Tối đa 5 chữ số.");
            }
        }

        // Đơn giá: số, max 8
        function validateUnitPrice($input) {
            const value = $input.val().replace(/[^0-9]/g, ''); // Lấy số thô
            hideRepairFormError($input);
            if (value.length > 8) {
                showRepairFormError($input, "Số quá lớn (tối đa 8 chữ số).");
            }
        }

        // Hàm kiểm tra các trường bắt buộc (dựa trên logic có sẵn)
        function validateRepairForm() {
            let isValid = true;
            // Reset lại toàn bộ cờ lỗi trước mỗi lần validate tổng thể
            repairFormErrors = {};
            $('#repairForm .error').text('').css('visibility', 'hidden');

            // Validate các trường đơn
            validateErrorType();
            validateDescription($('#des_error_type'));
            validateRejectionReason($('#rejection_reason'));
            validateCustomerRefusalReason($('#customer_refusal_reason'));

            // Kiểm tra các trường bắt buộc logic
            const $errorType = $('#error_type');
            if ($errorType.val().trim() === '') {
                showRepairFormError($errorType, 'Vui lòng nhập lỗi gặp phải');
                isValid = false;
            }

            const $solution = $('#solution');
            if (!$solution.val()) {
                showRepairFormError($solution, 'Vui lòng chọn cách xử lý');
                isValid = false;
            } else {
                hideRepairFormError($solution);
            }

            if ($solution.val() === 'Từ chối bảo hành' && $('#rejection_reason').val().trim() === '') {
                showRepairFormError($('#rejection_reason'), 'Vui lòng nhập lý do từ chối');
                isValid = false;
            }

            if ($solution.val() === 'KH không muốn bảo hành' && $('#customer_refusal_reason').val().trim() === '') {
                showRepairFormError($('#customer_refusal_reason'), 'Vui lòng nhập lý do khách hàng từ chối');
                isValid = false;
            }

            // --- LOGIC VALIDATE NHIỀU DÒNG ---
            // Lặp qua TẤT CẢ các hàng linh kiện
            $('.component-row-wrapper').each(function() {
                const $row = $(this);
                const $replacement = $row.find('.replacement-input');
                const $quantity = $row.find('.quantity-input');
                const $price = $row.find('.unit-price-input');

                // Chạy validate cơ bản (regex, độ dài)
                validateReplacement($replacement);
                validateQuantity($quantity);
                validateUnitPrice($price);

                // Kiểm tra logic bắt buộc
                if ($solution.val() === 'Thay thế linh kiện/hardware') {
                    // Nếu là dòng đầu tiên, HOẶC các dòng sau CÓ DỮ LIỆU
                    if ($row.is(':first-child') || $replacement.val().trim() !== '' || $quantity.val() !== '0' ||
                        $price.val() !== '0') {
                        if ($replacement.val().trim() === '') {
                            showRepairFormError($replacement, 'Vui lòng nhập linh kiện thay thế');
                            isValid = false;
                        }

                        if (parseInt($quantity.val()) < 1 || !$quantity.val()) {
                            showRepairFormError($quantity, 'Số lượng phải lớn hơn 0');
                            isValid = false;
                        }
                    }
                } else {
                    // Các trường hợp khác: nếu nhập linh kiện thì phải nhập SL
                    if ($replacement.val().trim() !== '' && (parseInt($quantity.val()) < 1 || !$quantity.val())) {
                        if (!repairFormErrors[$quantity.attr('id')]) {
                            showRepairFormError($quantity, 'Vui lòng nhập số lượng khi có linh kiện.');
                        }
                        isValid = false;
                    }
                }
            });

            // Kiểm tra lại cờ lỗi tổng thể
            if (Object.keys(repairFormErrors).length > 0) {
                isValid = false;
            }

            // Cập nhật lại trạng thái nút bấm sau khi đã kiểm tra tất cả lỗi
            updateSaveButtonState();

            return isValid;
        }

        // Helper để định dạng tiền và tính tổng
        function formatCurrency(input) {
            let value = input.val().replace(/[^0-9]/g, '');
            if (value) {
                input.val(parseInt(value, 10).toLocaleString('vi-VN'));
            } else {
                input.val('');
            }
        }

        function updateTotalPrice() {
            let total = 0;
            $('.component-row').each(function() {
                const quantity = parseInt($(this).find('.quantity-input').val()) || 0;
                const unitPrice = parseInt($(this).find('.unit-price-input').val().replace(/[^0-9]/g, '')) || 0;
                total += quantity * unitPrice;
            });
            $('#total_price').val(total.toLocaleString('vi-VN'));
        }

        // Gắn sự kiện
        $(document).ready(function() {
            $('#repairModal').on('input', '.replacement-input', function() {
                validateReplacement($(this));
            });
            $('#repairModal').on('input', '.quantity-input', function() {
                validateQuantity($(this));
                updateTotalPrice();
            });
            $('#repairModal').on('input', '.unit-price-input', function() {
                formatCurrency($(this));
                validateUnitPrice($(this));
                updateTotalPrice();
            });

            $('#error_type').on('input', function() {
                validateErrorType($(this));
            });
            $('#des_error_type').on('input', function() {
                validateDescription($(this));
            });
            $('#rejection_reason').on('input', function() {
                validateRejectionReason($(this));
            });
            $('#customer_refusal_reason').on('input', function() {
                validateCustomerRefusalReason($(this));
            });

            // Gỡ lỗi cho trường "Cách xử lý" ngay khi người dùng chọn
            $('#solution').on('change', function() {
                hideRepairFormError($(this));
                // Khi thay đổi lựa chọn, các trường yêu cầu có thể thay đổi, nên cần validate lại
                validateRepairForm();
            });
        });
    </script>
@endsection
