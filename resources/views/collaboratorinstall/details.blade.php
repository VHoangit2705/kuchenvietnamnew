@extends('layout.layout')

@section('content')
<div class="container-fluid mt-2">
    <div class="row g-4">
        <div class="col-12 col-md-6">
            <div class="card h-100">
            {{-- Thông tin khách hàng --}}
                <div class="card-header bg-secondary text-white position-relative">
                    <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" title="Quay lại" onclick="window.location.href='{{ route('dieuphoi.index') }}'"
                        style="height:15px; filter:brightness(0) invert(1); position:absolute; left:15px; top:50%; transform:translateY(-50%); cursor:pointer;">
                    <h5 class="mb-0 text-center">Thông tin khách hàng</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive col-12">
                        <table class="table table-striped">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 30%;">
                                <col style="width: 20%;">
                                <col style="width: 50%;">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <th>Mã đơn hàng</th>
                                    <td colspan="3">
                                        @php
                                        // Ưu tiên lấy từ installation_order, nếu không có mới lấy từ order/warranty_request
                                        $code = $installationOrder->order_code ?? $orderCode ?? $order->order_code2 ?? $order->order_code1 ?? $data->serial_number ?? $data->order_code ?? '';
                                        $zone = $installationOrder->zone ?? $order->zone ?? $data->zone ?? '';
                                        $created_at = $installationOrder->created_at ?? $order->created_at ?? $data->received_date ?? $data->created_at ?? null;
                                        $statusInstall = $installationOrder->status_install ?? $order->status_install ?? $data->status_install ?? 0;
                                        $type = isset($data->VAT) ? 'donhang' : (isset($data->warranty_end) ? 'baohanh' : 'danhsach');
                                        @endphp
                                        <div class="d-flex justify-content-between">
                                            <span>{{ $code }}</span>
                                            @php
                                            switch ($statusInstall) {
                                            case 1:
                                            $statusText = 'Đã Điều Phối';
                                            $statusClass = 'bg-warning fw-bold p-1 rounded-2';
                                            break;
                                            case 2:
                                            $statusText = 'Đã Hoàn Thành';
                                            $statusClass = 'bg-success fw-bold p-1 rounded-2';
                                            break;
                                            case 3:
                                            $statusText = 'Đã Thanh Toán';
                                            $statusClass = 'bg-info fw-bold p-1 rounded-2';
                                            break;
                                            default:
                                            $statusText = 'Chưa Điều Phối';
                                            $statusClass = 'bg-secondary fw-bold p-1 rounded-2';
                                            break;
                                            }
                                            @endphp
                                            <span class="{{ $statusClass }}">{{ $statusText }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Kho đi đơn:</th>
                                    <td colspan="3">
                                        {{ $zone }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tên sản phẩm:</th>
                                    <td colspan="3">
                                        @php
                                        // Ưu tiên từ installation_order, nếu không có mới lấy từ data gốc
                                        $productName = $installationOrder->product ?? $data->product_name ?? $data->product ?? '';
                                        @endphp
                                        <input type="text" id="product_name" hidden value="{{ $productName }}">
                                        {{ $productName }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Khách hàng:</th>
                                    <td colspan="3">
                                        @php
                                        // Ưu tiên từ installation_order, nếu không có mới lấy từ order/warranty_request
                                        $customerName = $installationOrder->full_name ?? $order->customer_name ?? $data->order->customer_name ?? $data->full_name ?? '';
                                        @endphp
                                        {{ $customerName }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại:</th>
                                    <td colspan="3">
                                        @php
                                        // Ưu tiên từ installation_order, nếu không có mới lấy từ order/warranty_request
                                        $customerPhone = $installationOrder->phone_number ?? $order->customer_phone ?? $data->order->customer_phone ?? $data->phone_number ?? '';
                                        @endphp
                                        {{ $customerPhone }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ:</th>
                                    {{-- Nâng cấp: Thêm chức năng chỉnh sửa cho địa chỉ --}}
                                    <td colspan="3" data-field="customer_address">
                                        @php
                                        // Ưu tiên địa chỉ từ installation_orders (nếu đã cập nhật), sau đó mới lấy từ orders/warranty_requests
                                        $customerAddress = $installationOrder->address ?? $data->order->customer_address ?? $data->address ?? '';
                                        @endphp
                                        <span class="text-value">{{ $customerAddress }}</span>, {{ $fullAddress }}
                                        {{-- Icon chỉnh sửa - chỉ hiển thị khi status_install != 0 và != null --}}
                                        @if(($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;" title="Sửa địa chỉ chi tiết"></i>
                                        @endif
                                        {{-- Input ẩn để lưu giá trị gốc --}}
                                        <input type="hidden" id="customer_address_full" value="{{ $customerAddress }}, {{ $fullAddress }}">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            {{-- Thông tin cộng tác viên --}}
                <div class="card-header bg-secondary text-white position-relative">
                    <h5 class="mb-0 text-center">Thông tin cộng tác viên</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive col-12">
                        <table class="table table-striped">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 30%;">
                                <col style="width: 20%;">
                                <col style="width: 50%;">
                            </colgroup>
                            <tbody>
                                <tr class="ctv_row">
                                    <th>CTV lắp đặt:</th>
                                    @php
                                    // Ưu tiên từ installationOrder, sau đó order, cuối cùng data gốc
                                    $ctvId = $installationOrder->collaborator_id ?? $order->collaborator_id ?? $data->order->collaborator_id ?? $data->collaborator_id ?? null;
                                    $ctv = $installationOrder->collaborator ?? $order->collaborator ?? $data->order->collaborator ?? $data->collaborator ?? null;
                                    @endphp
                                    <td id="ctv_name">{{ $ctv->full_name ?? 'N/A' }}</td>
                                    <input type="hidden" id="ctv_id" name="ctv_id" value="{{ $ctvId }}">
                                    <th>SĐT CTV:</th>
                                    <td id="ctv_phone">{{ $ctv->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr class="ctv_row">
                                    <th>Ngân hàng:</th>
                                    <td id="nganhang" data-field="nganhang">
                                        @php
                                        $bankName = $ctv->bank_name ?? $ctv->nganhang ?? '';
                                        @endphp
                                        <span class="text-value">{{ $bankName }}</span>
                                        <img class="bank-logo ms-2" alt="logo ngân hàng" style="height:45px; display:none;"/>
                                        @if (empty($bankName))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                    <th>Số tài khoản:</th>
                                    <td id="sotaikhoan" data-field="sotaikhoan" colspan="3">
                                        @php
                                        $soTaiKhoan = $ctv->sotaikhoan ?? '';
                                        @endphp
                                        <span class="text-value">{{ $soTaiKhoan }}</span>
                                        @if (empty($soTaiKhoan))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="ctv_row">
                                    <th>Chi nhánh:</th>
                                    <td id="chinhanh" data-field="chinhanh" colspan="3">
                                        @php
                                        $chiNhanh = $ctv->chinhanh ?? '';
                                        @endphp
                                        <span class="text-value">{{ $chiNhanh }}</span>
                                        @if (empty($chiNhanh))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="ctv_row">
                                    <th>Số CCCD:</th>
                                    <td id="cccd" data-field="cccd">
                                        @php
                                        $cccd = $ctv->cccd ?? '';
                                        @endphp
                                        <span class="text-value">{{ $cccd }}</span>
                                        @if (empty($cccd))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                    <th>Ngày cấp:</th>
                                    <td id="ngaycap" data-field="ngaycap">
                                        @php
                                        $ngayCap = $ctv->ngaycap ?? null;
                                        @endphp
                                        <span class="text-value">
                                            {{ $ngayCap ? \Carbon\Carbon::parse($ngayCap)->format('d/m/Y') : '' }}
                                        </span>
                                        @if (empty($ngayCap))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr id="install_cost_row">
                                    <th>Chi phí lắp đặt:</th>
                                    <td>
                                        @php
                                        $installCost = $installationOrder->install_cost ?? $order->install_cost ?? $data->order->install_cost ?? $data->install_cost ?? 0;
                                        @endphp
                                        <input type="text" id="install_cost_ctv" class="form-control install_cost" name="install_cost_ctv" value="{{ number_format($installCost, 0, ',', '') }}" placeholder="Nhập chi phí">
                                        <div class="text-danger mt-1 error" id="install_cost_ctv_error" style="display:none;"></div>
                                    </td>
                                    <th>Ngày hoàn thành:</th>
                                    <td>
                                        @php
                                        $successedAt = $installationOrder->successed_at ?? $order->successed_at ?? $data->order->successed_at ?? $data->successed_at ?? '';
                                        @endphp
                                        <input type="date" id="successed_at_ctv" width="100%" class="form-control successed_at_ctv" name="successed_at_ctv" value="{{ $successedAt }}">
                                    </td>
                                </tr>
                                <tr id="install_file">
                                    <th>File đánh giá:</th>
                                    <td colspan="3">
                                        <input type="file" id="install_review" class="form-control" name="install_review" accept=".pdf,.jpg,.jpeg,.png">
                                        @php
                                        $reviewsInstall = $installationOrder->reviews_install ?? $order->reviews_install ?? $data->order->reviews_install ?? $data->reviews_install ?? null;
                                        @endphp
                                        @if (!empty($reviewsInstall))
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/install_reviews/' . $reviewsInstall) }}" target="_blank">
                                                {{ $reviewsInstall }}
                                            </a>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white text-center">
                    <h5 class="mb-0">Thông tin đại lý</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive col-12">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th class="w-50">Tên đại lý:</th>
                                    <td class="w-50" data-agency="agency_name">
                                        <span class="text-value">{{ $data->order->agency_name ?? $data->agency_name}}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại đại lý:</th>
                                    <td data-agency="agency_phone">
                                        <span class="text-value">{{ $data->order->agency_phone ?? $data->agency_phone }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ đại lý:</th>
                                    <td data-agency="agency_address">
                                        <span class="text-value">{{ $agency->address ?? '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone) && ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ngân hàng:</th>
                                    <td data-agency="agency_bank">
                                        <span class="text-value">{{ $agency->bank_name_agency ?? $agency->nganhang ?? '' }}</span>
                                        <img class="bank-logo ms-2" alt="logo ngân hàng" style="height:45px; display:none;"/>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone) && ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số tài khoản:</th>
                                    <td data-agency="agency_paynumber">
                                        <span class="text-value">{{ $agency->sotaikhoan ?? '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone) && ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Chi nhánh:</th>
                                    <td data-agency="agency_branch">
                                        <span class="text-value">{{ $agency->chinhanh ?? '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone) && ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Căn cước công dân:</th>
                                    <td data-agency="agency_cccd">
                                        <span class="text-value">{{ $agency->cccd ?? '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone) && ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ngày cấp:</th>
                                    <td data-agency="agency_release_date">
                                        <span class="text-value">{{ optional($agency)->ngaycap ? \Carbon\Carbon::parse($agency->ngaycap)->format('d/m/Y') : '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone) && ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <label class="d-flex align-items-center fw-bold" style="width: max-content;">
                                            <input type="checkbox" id="isInstallAgency" class="me-2" {{ ($data->order->collaborator_id ?? $data->collaborator_id) == 1 ? 'checked' : '' }}> Đại lý lắp đặt
                                        </label>
                                    </td>
                                </tr>
                                <tr class="installCostRow" style="display: none;">
                                    <th>Chi phí lắp đặt:</th>
                                    <td>
                                        <input type="text" id="install_cost_agency" width="100%" class="form-control install_cost" name="install_cost_agency" value="{{ number_format($data->order->install_cost ?? $data->install_cost, 0, ',', '') ?? '' }}" placeholder="Nhập chi phí">
                                        <div class="text-danger mt-1 error" id="install_cost_error" style="display:none;"></div>
                                    </td>
                                </tr>
                                <tr class="installCostRow" style="display: none;">
                                    <th>Ngày hoàn thành:</th>
                                    <td>
                                        <input type="date" id="successed_at" width="100%" class="form-control successed_at" name="successed_at" value="{{ $data->order->successed_at ?? $data->successed_at ?? '' }}">
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
<div class="container-fluid mt-2" id="table_collaborator">
    <div class="col-12 col-md-12">
        <div class="card h-100">
            <div class="card-header bg-secondary text-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <h5 class="mb-2 mb-md-0">Danh sách cộng tác viên gần khách hàng</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="flex-grow-1" style="min-width: 150px;">
                            <select id="province" name="province" class="form-control">
                                <option value="" {{ request('province') == '' ? 'selected' : '' }}>Tỉnh/Thành phố</option>
                                @foreach($provinces as $province)
                                <option value="{{ $province->province_id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex-grow-1" style="min-width: 150px;">
                            <select id="district" name="district" class="form-control">
                                <option value="">Quận/Huyện</option>
                            </select>
                        </div>

                        <div class="flex-grow-1" style="min-width: 150px;">
                            <select id="ward" name="ward" class="form-control">
                                <option value="">Xã/Phường</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive col-12">
                    @include('collaboratorinstall.tablecollaborator', ['lstCollaborator' => $lstCollaborator])
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-2 d-flex justify-content-between">
    <div>
        <button id="btnViewHistory" class="mt-2 btn btn-outline-secondary fw-bold">
            <i class="bi bi-clock-history me-1"></i> Xem lịch sử thay đổi
        </button>
    </div>
    <div class="d-flex">
      @if($statusInstall < 2) {{-- Trạng thái 0: Chưa ĐP, 1: Đã ĐP --}}

        <button id="btnUpdate" class="mt-2 btn btn-outline-primary fw-bold" data-action="update">Cập nhật</button>
        <button id="btnComplete" class="mt-2 ms-1 btn btn-outline-success fw-bold" data-action="complete">Hoàn thành</button>
        <button id="btnPay" class="mt-2 ms-1 btn btn-outline-info fw-bold" data-action="payment">Đã thanh toán</button>

    @elseif($statusInstall == 2) {{-- Trạng thái 2: Đã Hoàn Thành --}}
    <button id="btnComplete" class="mt-2 ms-1 btn btn-outline-success fw-bold" data-action="complete">Hoàn thành</button>
    <button id="btnPay" class="mt-2 ms-1 btn btn-outline-info fw-bold" data-action="payment">Đã thanh toán</button>

    @elseif($statusInstall == 3) {{-- Trạng thái 3: Đã Thanh Toán --}}
        <button id="btnUpdate" class="mt-2 btn btn-outline-primary fw-bold" data-action="update">Cập nhật</button>

    @endif 
    </div>
</div>

<!-- Modal Lịch sử thay đổi -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="historyModalLabel">
                    <i class="bi bi-clock-history me-2"></i>Lịch sử thay đổi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải lịch sử thay đổi...</p>
                </div>
                <div id="historyContent" style="display: none;">
                    <div id="historyList"></div>
                </div>
                <div id="historyEmpty" class="text-center py-4" style="display: none;">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-2">Chưa có lịch sử thay đổi nào</p>
                </div>
            </div>
            <div class="modal-footer">
                <p>Lưu ý các trường trống có thể là do đồng bộ từ file excel mà ko có đầy đủ thông tin của đại lý hoặc cộng tác viên</p>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Datalist for bank names (populated via VietQR API) -->
<datalist id="bankList"></datalist>

<script src="{{ asset('js/common.js') }}"></script>
<!-- Validation được load đầu tiên sau common.js -->
<script src="{{ asset('js/validate_input/details.js') }}"></script>
<script src="{{ asset('js/collaboratorinstall/modules/validation/setup.js') }}"></script>
<script>
    // Global variables for JavaScript
    window.ORDER_CODE = '{{ $code }}';
    window.CREATION_DATE = '{{ $created_at }}';
    window.FULL_ADDRESS = '{{ $fullAddress }}';
    window.MODEL_ID = '{{ $modelId ?? $installationOrder->id ?? $order->id ?? $data->order->id ?? $data->id ?? null }}';
    window.ROUTES = {
        agency_update: "{{ route('agency.update') }}",
        ctv_switch: "{{ route('ctv.switch') }}",
        ctv_clear: "{{ route('ctv.clear') }}",
        collaborator_show: "{{ route('collaborator.show', ':id') }}",
        ctv_update: "{{ route('ctv.update') }}",
        dieuphoi_update: "{{ route('dieuphoi.update') }}",
        dieuphoi_update_address: "{{ route('dieuphoi.update.address') }}",
        ctv_getdistrict: "{{ route('ctv.getdistrict', ':province_id') }}",
        ctv_getward: "{{ route('ctv.getward', ':district_id') }}",
        collaborators_filter: "{{ route('collaborators.filter') }}",
        ctv_order_history: "{{ route('ctv.order.history', ':order_code') }}"
    };
    window.BANKS_URL = "{{ config('services.vietqr.banks_url', 'https://api.vietqr.io/v2/banks') }}";
</script>
<script src="{{ asset('js/Collaborator_Install/validation.js') }}"></script>
<script src="{{ asset('js/Collaborator_Install/details_Collaborator_Install/data-management.js') }}"></script>
<script src="{{ asset('js/Collaborator_Install/details_Collaborator_Install/update-functions.js') }}"></script>
<script src="{{ asset('js/Collaborator_Install/details_Collaborator_Install/field-editing.js') }}"></script>
<script src="{{ asset('js/Collaborator_Install/details_Collaborator_Install/history.js') }}"></script>
<script src="{{ asset('js/Collaborator_Install/details_Collaborator_Install/bank-logo.js') }}"></script>
<script src="{{ asset('js/Collaborator_Install/details_Collaborator_Install/location-filter.js') }}"></script>
<script src="{{ asset('js/Collaborator_Install/details_Collaborator_Install/main.js') }}"></script>
@endsection