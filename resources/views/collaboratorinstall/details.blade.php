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
                                        $code = $data->order->order_code2 ?? $data->serial_number ?? $data->order_code;
                                        $zone = $data->order->zone ?? $data->zone ?? '';
                                        $created_at = $data->order->created_at ?? $data->received_date ?? $data->created_at;
                                        $statusInstall = $data->order->status_install ?? $data->status_install;
                                        $type = $data->VAT ? 'donhang' : ($data->warranty_end ? 'baohanh' : 'danhsach');
                                        @endphp
                                        <script>const CREATION_DATE = '{{ $created_at }}';</script>
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
                                        <input type="text" id="product_name" hidden value="{{ $data->product_name ?? $data->product }}">
                                        {{ $data->product_name ?? $data->product }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Khách hàng:</th>
                                    <td colspan="3">{{ $data->order->customer_name ?? $data->full_name }}</td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại:</th>
                                    <td colspan="3">{{ $data->order->customer_phone ?? $data->phone_number}}</td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ:</th>
                                    {{-- Nâng cấp: Thêm chức năng chỉnh sửa cho địa chỉ --}}
                                    <td colspan="3" data-field="customer_address">
                                        <span class="text-value">{{ $data->order->customer_address ?? $data->address }}</span>, {{ $fullAddress }}
                                        {{-- Icon chỉnh sửa --}}
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;" title="Sửa địa chỉ chi tiết"></i>
                                        {{-- Input ẩn để lưu giá trị gốc --}}
                                        <input type="hidden" id="customer_address_full" value="{{ $data->order->customer_address ?? $data->address}}, {{ $fullAddress }}">
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
                                    <td id="ctv_name">{{ $data->order->collaborator->full_name ?? $data->collaborator->full_name ?? 'N/A' }}</td>
                                    <input type="hidden" id="ctv_id" name="ctv_id" value="{{ $data->order->collaborator_id ?? $data->collaborator_id }}">
                                    <th>SĐT CTV:</th>
                                    <td id="ctv_phone">{{ $data->order->collaborator->phone ?? $data->collaborator->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr class="ctv_row">
                                    <th>Ngân hàng:</th>
                                    <td id="nganhang" data-field="nganhang">
                                        <span class="text-value">{{ $data->order->collaborator->bank_name ?? $data->order->collaborator->nganhang ?? $data->collaborator->bank_name ?? $data->collaborator->nganhang ?? '' }}</span>
                                        <img class="bank-logo ms-2" alt="logo ngân hàng" style="height:45px; display:none;"/>
                                        @if (empty(optional(optional($data->order)->collaborator)->bank_name ?? optional(optional($data->order)->collaborator)->nganhang ?? optional($data->collaborator)->bank_name ?? optional($data->collaborator)->nganhang))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                    <th>Số tài khoản:</th>
                                    <td id="sotaikhoan" data-field="sotaikhoan" colspan="3">
                                        <span class="text-value">{{ $data->order->collaborator->sotaikhoan ?? $data->collaborator->sotaikhoan ?? ''}}</span>
                                        @if (empty(optional(optional($data->order)->collaborator)->sotaikhoan ?? optional($data->collaborator)->sotaikhoan))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="ctv_row">
                                    <th>Chi nhánh:</th>
                                    <td id="chinhanh" data-field="chinhanh" colspan="3">
                                        <span class="text-value">{{ $data->order->collaborator->chinhanh ?? $data->collaborator->chinhanh ?? ''}}</span>
                                        @if (empty(optional(optional($data->order)->collaborator)->chinhanh ?? optional($data->collaborator)->chinhanh))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="ctv_row">
                                    <th>Số CCCD:</th>
                                    <td id="cccd" data-field="cccd">
                                        <span class="text-value">{{ $data->order->collaborator->cccd ?? $data->collaborator->cccd ?? ''}}</span>
                                        @if (empty(optional(optional($data->order)->collaborator)->cccd ?? optional($data->collaborator)->cccd))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                    <th>Ngày cấp:</th>
                                    <td id="ngaycap" data-field="ngaycap">
                                        <span class="text-value">
                                            {{ optional(optional($data->order)->collaborator)->ngaycap
                                                ? \Carbon\Carbon::parse(optional($data->order->collaborator)->ngaycap)->format('d/m/Y')
                                                : (optional($data->collaborator)->ngaycap
                                                    ? \Carbon\Carbon::parse($data->collaborator->ngaycap)->format('d/m/Y')
                                                    : ''
                                                  )
                                            }}
                                        </span>
                                        @if (empty(optional(optional($data->order)->collaborator)->ngaycap ?? optional($data->collaborator)->ngaycap))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr id="install_cost_row">
                                    <th>Chi phí lắp đặt:</th>
                                    <td>
                                        <input type="text" id="install_cost_ctv" class="form-control install_cost" name="install_cost_ctv" value="{{ number_format($data->order->install_cost ?? $data->install_cost, 0, ',', '') ?? '' }}" placeholder="Nhập chi phí">
                                        <div class="text-danger mt-1 error" id="install_cost_ctv_error" style="display:none;"></div>
                                    </td>
                                    <th>Ngày hoàn thành:</th>
                                    <td>
                                        <input type="date" id="successed_at_ctv" width="100%" class="form-control successed_at_ctv" name="successed_at_ctv" value="{{ $data->order->successed_at ?? $data->successed_at ?? '' }}">
                                    </td>
                                </tr>
                                <tr id="install_file">
                                    <th>File đánh giá:</th>
                                    <td colspan="3">
                                        <input type="file" id="install_review" class="form-control" name="install_review" accept=".pdf,.jpg,.jpeg,.png">
                                        @if (!empty($data->order->reviews_install ?? $data->reviews_install))
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/install_reviews/' . (optional($data->order)->reviews_install ?? $data->reviews_install)) }}" target="_blank">
                                                {{ $data->order->reviews_install ?? $data->reviews_install}}
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
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ngân hàng:</th>
                                    <td data-agency="agency_bank">
                                        <span class="text-value">{{ $agency->nganhang ?? '' }}</span>
                                        <img class="bank-logo ms-2" alt="logo ngân hàng" style="height:45px; display:none;"/>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số tài khoản:</th>
                                    <td data-agency="agency_paynumber">
                                        <span class="text-value">{{ $agency->sotaikhoan ?? '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Chi nhánh:</th>
                                    <td data-agency="agency_branch">
                                        <span class="text-value">{{ $agency->chinhanh ?? '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Căn cước công dân:</th>
                                    <td data-agency="agency_cccd">
                                        <span class="text-value">{{ $agency->cccd ?? '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ngày cấp:</th>
                                    <td data-agency="agency_release_date">
                                        <span class="text-value">{{ optional($agency)->ngaycap ? \Carbon\Carbon::parse($agency->ngaycap)->format('d/m/Y') : '' }}</span>
                                        @if (!empty($data->order->agency_phone ?? $data->agency_phone))
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

<script>
    // Lưu trữ giá trị ban đầu của các trường CTV và Đại lý
    let originalCtvData = {};
    let originalAgencyData = {};

    // 1. "Cờ" validation: Lưu trạng thái lỗi của các trường
    let validationErrors = {};

    // 2. Hàm định dạng tiền VNĐ (1,000,000)
    function formatCurrency(input) {
        let value = input.val().replace(/[^0-9]/g, ''); // Chỉ giữ lại số
        if (!value) {
            input.val('');
            return;
        }
        let num = parseInt(value, 10);
        if (isNaN(num)) {
            input.val('');
            return;
        }
        input.val(num.toLocaleString('vi-VN')); // Định dạng kiểu VN
    }

    // 3. Hàm lấy giá trị số thô từ trường tiền tệ (1000000)
    function getCurrencyValue($input) { 
    let value = $input.val() || ''; // Lấy giá trị trực tiếp từ $input
    return value.replace(/[^0-9]/g, '') || '0';
}

    // 4. Hàm hiển thị lỗi
    function showError($field, message) {
        // Xác định ID định danh duy nhất cho trường
        let fieldId = $field.attr('id') || $field.closest('td').data('field') || $field.closest('td').data('agency');
        if (!fieldId) fieldId = $field.attr('name'); // Dự phòng

        hideError($field); // Xóa lỗi cũ trước
        let $error = $(`<div class="text-danger mt-1 validation-error" data-error-for="${fieldId}">${message}</div>`);
        
        // Thêm lỗi vào đúng vị trí
        if ($field.hasClass('form-control')) {
            $field.closest('td').append($error);
        } else {
            $field.parent().append($error);
        }
        
        validationErrors[fieldId] = true; // Gắn cờ lỗi
        updateSubmitButtons(); // Cập nhật trạng thái nút
    }

    // 5. Hàm ẩn lỗi
    function hideError($field) {
        let fieldId = $field.attr('id') || $field.closest('td').data('field') || $field.closest('td').data('agency');
        if (!fieldId) fieldId = $field.attr('name');

        if ($field.hasClass('form-control')) {
            $field.closest('td').find('.validation-error').remove();
        } else {
             $field.parent().find('.validation-error').remove();
        }

        delete validationErrors[fieldId]; // Bỏ cờ lỗi
        updateSubmitButtons(); // Cập nhật trạng thái nút
    }

    // 6. Hàm cập nhật trạng thái các nút Submit
    function updateSubmitButtons() {
        // Kiểm tra xem có lỗi nào không
        let hasErrors = Object.keys(validationErrors).length > 0;
        let $buttons = $("#btnUpdate, #btnComplete, #btnPay");

        if (hasErrors) {
            $buttons.prop('disabled', true).css('opacity', '0.65').css('cursor', 'not-allowed');
        } else {
            $buttons.prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');
        }
    }

    // 7. Hàm xác thực cho các trường động (sotaikhoan, cccd, v.v.)
    function validateDynamicField($input, fieldName) {
        if (!$input || $input.length === 0) return; // Trường không tồn tại
        
        let value = $input.val().trim();
        let $td = $input.closest('td');
        hideError($input); // Xóa lỗi cũ

        switch (fieldName) {
            case 'sotaikhoan':
            case 'agency_paynumber':
                if (value && !/^[0-9]+$/.test(value)) {
                    showError($input, "Chỉ được nhập số.");
                } else if (value.length > 20) {
                    showError($input, "Tối đa 20 ký tự.");
                }
                // THAY ĐỔI: Sửa logic tìm kiếm để đảm bảo tìm đúng input (nếu nó đang được edit)
                let $chinhanhInput = $td.closest('tbody').find('input[data-field="chinhanh"], input[data-agency="agency_branch"]');
                if($chinhanhInput.length) validateDynamicField($chinhanhInput, $chinhanhInput.data('field') || $chinhanhInput.data('agency'));
                break;

            case 'chinhanh':
            case 'agency_branch':
                // THAY ĐỔI: Tìm sotaikhoanCell trong toàn bộ <tbody>, không phải <tr>
                let $sotaikhoanCell = $td.closest('tbody').find('td[data-field="sotaikhoan"], td[data-agency="agency_paynumber"]');
                let sotaikhoanValue = $sotaikhoanCell.find('input').length ? $sotaikhoanCell.find('input').val().trim() : $sotaikhoanCell.find('.text-value').text().trim();

                if (!sotaikhoanValue) {
                    showError($input, "Vui lòng nhập Số tài khoản trước.");
                } else if (value && !/^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY]+$/.test(value)) { 
                    showError($input, "Chỉ nhập chữ tiếng Việt và dấu cách, không nhập số hoặc ký tự đặc biệt.");
                } else if (value.length > 80) {
                    showError($input, "Tối đa 80 ký tự.");
                }
                break;
            case 'nganhang':
            case 'agency_bank':
                // Cho phép chữ, số, dấu cách và các ký tự (.,-/&)
                if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY.,\-\/&]+$/.test(value)) {
                    showError($input, "Tên ngân hàng chỉ được chứa chữ, số, dấu cách và (.,-/&).");
                } else if (value.length > 80) {
                    showError($input, "Tối đa 80 ký tự.");
                }
                break;
            case 'agency_name':
            case 'agency_address':
                // Lưu ý: Cho phép chữ tiếng Việt, số, dấu cách, và các ký tự .,-/
                if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY.,-/]+$/.test(value)) { 
                    showError($input, "Chỉ nhập chữ, số, dấu cách và ký tự (.,-/).");
                } else if (value.length > 80) {
                    showError($input, "Tối đa 80 ký tự.");
                }
                break;
            case 'customer_address':
                // Validation cho địa chỉ khách hàng
                // Cho phép chữ, số, dấu cách và các ký tự .,-/
                if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY.,\-\/]+$/.test(value)) {
                    showError($input, "Chỉ nhập chữ, số, dấu cách và các ký tự (.,-/).");
                } else if (value.length > 150) {
                    showError($input, "Tối đa 150 ký tự.");
                }
                break;

            case 'cccd':
            case 'agency_cccd':
                if (value && !/^[0-9]+$/.test(value)) {
                    showError($input, "Chỉ được nhập số.");
                } else if (value && value.length !== 12) {
                    showError($input, "Bắt buộc đủ 12 số.");
                }
                 // Xác thực lại trường 'ngày cấp' phụ thuộc
                 // THAY ĐỔI: Sửa logic tìm kiếm để đảm bảo tìm đúng input (nếu nó đang được edit)
                let $ngaycapInput = $td.closest('tbody').find('input[data-field="ngaycap"], input[data-agency="agency_release_date"]');
                if($ngaycapInput.length) validateDynamicField($ngaycapInput, $ngaycapInput.data('field') || $ngaycapInput.data('agency'));
                break;

            case 'ngaycap':
            case 'agency_release_date':
                // THAY ĐỔI: Tìm cccdCell trong toàn bộ <tbody>, không phải <tr>
                let $cccdCell = $td.closest('tbody').find('td[data-field="cccd"], td[data-agency="agency_cccd"]');
                let cccdValue = $cccdCell.find('input').length ? $cccdCell.find('input').val().trim() : $cccdCell.find('.text-value').text().trim();
                
                if (!cccdValue || cccdValue.length !== 12 || !/^[0-9]+$/.test(cccdValue)) {
                    showError($input, "Vui lòng nhập CCCD (12 số) hợp lệ trước.");
                } else if (value) {
                    try {
                        let today = new Date();
                        today.setHours(0, 0, 0, 0); // Đặt về nửa đêm
                        let selectedDate = new Date(value);
                        
                        if (selectedDate > today) {
                            showError($input, "Ngày cấp không được quá ngày hiện tại.");
                        }
                    } catch(e) {
                        showError($input, "Ngày không hợp lệ.");
                    }
                }
                break;
        }
    }

    // 8. Hàm xác thực cho Chi phí lắp đặt
    function validateInstallCost($input) {
    hideError($input);
    let valueStr = $input.val().trim();
    
    // THAY ĐỔI: Chuyển từ getCurrencyValue($input.selector) sang getCurrencyValue($input)
    let numValue = parseInt(getCurrencyValue($input), 10); // Lấy số thô

    if (!valueStr) {
        showError($input, "Chi phí không được để trống.");
    } else if (isNaN(numValue)) { 
        // Trường hợp này gần như không xảy ra vì getCurrencyValue luôn trả về chuỗi số hoặc '0'
        showError($input, "Vui lòng nhập số hợp lệ.");
    } else if (numValue <= 0) {
        showError($input, "Chi phí phải là số nguyên dương.");
    }
}

    // 9. Hàm xác thực cho Ngày hoàn thành
    function validateCompletionDate($input) {
        hideError($input);
        let completionDateStr = $input.val();
        if (!completionDateStr || typeof CREATION_DATE === 'undefined' || !CREATION_DATE) return; // Không có gì để so sánh

        try {
            let completionDate = new Date(completionDateStr);
            let creationDate = new Date(CREATION_DATE);
            
            // Đặt về 0 giờ để so sánh ngày
            completionDate.setHours(0, 0, 0, 0);
            creationDate.setHours(0, 0, 0, 0);

            if (completionDate < creationDate) {
                let creationDateFormatted = new Date(CREATION_DATE).toLocaleDateString('vi-VN');
                showError($input, `Ngày hoàn thành không được sớm hơn ngày tạo đơn (${creationDateFormatted}).`);
            }
        } catch (e) {
            showError($input, "Ngày không hợp lệ.");
        }
    }
    
    // 10. Chạy tất cả validation cho các trường input tĩnh khi tải trang
    function runAllInitialValidations() {
        if ($("#install_cost_ctv").is(":visible")) validateInstallCost($('#install_cost_ctv'));
        if ($("#successed_at_ctv").is(":visible")) validateCompletionDate($('#successed_at_ctv'));
        if ($("#install_cost_agency").is(":visible")) validateInstallCost($('#install_cost_agency'));
        if ($("#successed_at").is(":visible")) validateCompletionDate($('#successed_at'));
        
        updateSubmitButtons(); // Cập nhật nút bấm dựa trên cờ lỗi
    }

    $(document).ready(function() {
        // Lưu giá trị ban đầu của các trường CTV
        function saveOriginalCtvData() {
            originalCtvData = {
                ctv_name: $("#ctv_name").text().trim(),
                ctv_phone: $("#ctv_phone").text().trim(),
                ctv_id: $("#ctv_id").val(),
                sotaikhoan: $("#sotaikhoan .text-value").text().trim(),
                chinhanh: $("#chinhanh .text-value").text().trim(),
                nganhang: $("#nganhang .text-value").text().trim(),
                cccd: $("#cccd .text-value").text().trim(),
                ngaycap: $("#ngaycap .text-value").text().trim(),
                install_cost_ctv: $("#install_cost_ctv").val(),
                successed_at_ctv: $("#successed_at_ctv").val()
            };
        }

        // Lưu giá trị ban đầu của các trường Đại lý
        function saveOriginalAgencyData() {
            originalAgencyData = {
                agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
                agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
                agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
                agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
                agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
                agency_branch: $("td[data-agency='agency_branch'] .text-value").text().trim(),
                agency_cccd: $("td[data-agency='agency_cccd'] .text-value").text().trim(),
                agency_release_date: $("td[data-agency='agency_release_date'] .text-value").text().trim()
            };
        }

        // Khôi phục giá trị ban đầu của các trường CTV
        function restoreOriginalCtvData() {
            // Lưu thông tin đại lý hiện tại trước khi chuyển về CTV
            saveAgencyDataBeforeSwitch();
            
            $("#ctv_name").text(originalCtvData.ctv_name);
            $("#ctv_phone").text(originalCtvData.ctv_phone);
            $("#ctv_id").val(originalCtvData.ctv_id);
            updateField("sotaikhoan", originalCtvData.sotaikhoan);
            updateField("chinhanh", originalCtvData.chinhanh);
            updateField("nganhang", originalCtvData.nganhang);
            updateField("cccd", originalCtvData.cccd);
            updateField("ngaycap", originalCtvData.ngaycap);
            $("#install_cost_ctv").val(originalCtvData.install_cost_ctv);
            $("#successed_at_ctv").val(originalCtvData.successed_at_ctv);
            
            // Ghi log việc chuyển từ "Đại lý lắp đặt" về CTV
            logSwitchToCtv();
        }

        // Lưu thông tin đại lý trước khi chuyển về CTV
        function saveAgencyDataBeforeSwitch() {
            let orderCode = "{{ $code }}";
            let data = {
                _token: $('meta[name="csrf-token"]').attr("content"),
                order_code: orderCode
            };
            
            // Thu thập thông tin đại lý hiện tại
            $("td[data-agency]").each(function() {
                let $td = $(this);
                let agency = $td.data("agency");
                let value;
                if ($td.find("input").length) {
                    value = $td.find("input").val().trim();
                } else {
                    value = $td.find(".text-value").text().trim();
                }
                
                // Xử lý format ngày tháng cho agency_release_date
                if (agency === "agency_release_date" && value && value.includes('/')) {
                    // Chuyển từ d/m/Y sang Y-m-d cho database
                    let parts = value.split('/');
                    if (parts.length === 3) {
                        let day = parts[0].padStart(2, '0');
                        let month = parts[1].padStart(2, '0');
                        let year = parts[2];
                        value = year + '-' + month + '-' + day;
                    }
                }
                
                data[agency] = value;
            });
            
            // Gửi AJAX để lưu thông tin đại lý trước khi chuyển
            $.ajax({
                url: "{{ route('agency.update') }}",
                method: "POST",
                data: data,
                success: function(response) {
                    console.log('Agency data saved before switch to CTV');
                },
                error: function(xhr, status, error) {
                    console.log('Error saving agency data before switch:', error);
                }
            });
        }

        // AJAX call để ghi log chuyển về CTV
        function logSwitchToCtv() {
            let orderCode = "{{ $code }}";
            $.ajax({
                url: "{{ route('ctv.switch') }}",
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    order_code: orderCode
                },
                success: function(response) {
                    console.log('Logged switch to CTV');
                },
                error: function(xhr, status, error) {
                    console.log('Error logging switch to CTV:', error);
                }
            });
        }

        // Clear các trường CTV về rỗng
        function clearCtvData() {
            $("#ctv_name").text('');
            $("#ctv_phone").text('');
            $("#ctv_id").val('');
            updateField("sotaikhoan", '');
            updateField("chinhanh", '');
            updateField("nganhang", '');
            updateField("cccd", '');
            updateField("ngaycap", '');
            $("#install_cost_ctv").val('');
            $("#successed_at_ctv").val('');
            
            // Clear file input
            $("#install_review").val('');
            
            // Gửi AJAX để clear CTV data trên server nếu cần
            clearCtvDataOnServer();
        }

        // AJAX call để clear CTV data trên server
        function clearCtvDataOnServer() {
            let orderCode = "{{ $code }}";
            $.ajax({
                url: "{{ route('ctv.clear') }}",
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    order_code: orderCode
                },
                success: function(response) {
                    // CTV data cleared successfully
                    console.log('CTV data cleared on server');
                },
                error: function(xhr, status, error) {
                    console.log('Error clearing CTV data:', error);
                }
            });
        }

        // Lưu giá trị ban đầu khi trang load
        saveOriginalCtvData();
        saveOriginalAgencyData();

        $("#isInstallAgency").on("change", function() {
            // NÂNG CẤP: Xóa tất cả cờ lỗi và chạy lại validation
            validationErrors = {};
            $('.validation-error').remove();
            
            if ($(this).is(":checked")) {
                // Clear các trường CTV (không lưu giá trị hiện tại)
                clearCtvData();
                
                $(".installCostRow").show();
                $(".ctv_row").hide();
                $("#install_cost_row").hide();
                $("#install_file").hide();
                $("#table_collaborator").hide();
            } else {
                // Khôi phục giá trị ban đầu
                restoreOriginalCtvData();
                
                $(".installCostRow").hide();
                $(".error").hide(); // 'error' là class cũ, có thể xóa
                $("#table_collaborator").show();
                $(".ctv_row").show();
                $("#install_cost_row").show();
                $("#install_file").show();
            }
            
            // NÂNG CẤP: Chạy lại validation cho các trường
            runAllInitialValidations();
        });

        if ($("#isInstallAgency").is(":checked")) {
            $(".installCostRow").show();
            $(".ctv_row").hide();
            $("#install_cost_row").hide();
            $("#install_file").hide();
            $("#table_collaborator").hide();
        } else {
            $(".installCostRow").hide();
            $(".error").hide(); // 'error' là class cũ, có thể xóa
            $("#table_collaborator").show();
        }

        $('#tablecollaborator').on('click', '.choose-ctv', function() {
            let id = $(this).data("id");
            $.ajax({
                url: "{{ route('collaborator.show', ':id') }}".replace(':id', id),
                method: "GET",
                success: function(res) {
                    $("#ctv_name").text(res.full_name);
                    $("#ctv_phone").text(res.phone);
                    updateField("sotaikhoan", res.sotaikhoan);
                    updateField("chinhanh", res.chinhanh);
                    const bankName = res.nganhang || res.bank_name || '';
                    updateField("nganhang", bankName);
                    // Đảm bảo cập nhật logo ngay lập tức
                    updateBankLogoForCell($("#nganhang"));
                    updateField("cccd", res.cccd);
                    updateField("ngaycap", res.ngaycap);

                    $(".ctv_row").show();
                    $("#install_cost_row").show();
                    $("#ctv_id").val(id);
                },
                error: function() {
                    alert("Lỗi!");
                }
            });
        });

        function updateField(fieldId, value) {
            let td = $("#" + fieldId);
            let html = `<span class=\"text-value\">${value ?? ''}</span>`;
            if (fieldId === 'nganhang') {
                html += ` <img class=\"bank-logo ms-2\" alt=\"logo ngân hàng\" style=\"height:50px; display:none;\"/>`;
            }
            if (!value) {
                html += `<i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>`;
            }
            td.html(html);
            if (fieldId === 'nganhang') {
                updateBankLogoForCell(td);
            }
        }

        // --- NÂNG CẤP: Gắn validation cho các trường tĩnh ---

        // 1. Chi phí lắp đặt (cả CTV và Đại lý)
        $(".install_cost").on("input", function() {
            formatCurrency($(this)); // Định dạng tiền
            validateInstallCost($(this)); // Xác thực
        }).on("blur", function() {
            validateInstallCost($(this)); // Xác thực khi rời đi
        });
        
        // 2. Ngày hoàn thành (cả CTV và Đại lý)
        $("#successed_at_ctv, #successed_at").on("change", function() {
            validateCompletionDate($(this));
        }).on("blur", function() {
            validateCompletionDate($(this)); // Xác thực khi rời đi
        });
        
        // 3. Chạy validation ban đầu khi tải trang
        runAllInitialValidations();
        
        // --- KẾT THÚC NÂNG CẤP TRƯỜNG TĨNH ---


        Update();
        
        // Xử lý nút xem lịch sử
        $('#btnViewHistory').on('click', function() {
            loadHistory();
            $('#historyModal').modal('show');
        });

        // Nạp danh sách ngân hàng từ VietQR API vào datalist
        const banksUrl = "{{ config('services.vietqr.banks_url', 'https://api.vietqr.io/v2/banks') }}";
        window.bankNameToLogo = window.bankNameToLogo || {};
        window.bankShortToLogo = window.bankShortToLogo || {};
        window.bankCodeToLogo = window.bankCodeToLogo || {};
        try {
            fetch(banksUrl)
                .then(res => res.json())
                .then(json => {
                    if (!json || !json.data) return;
                    const list = document.getElementById('bankList');
                    if (!list) return;
                    list.innerHTML = '';
                    json.data.forEach(function(b){
                        const opt = document.createElement('option');
                        opt.value = (b.shortName ? b.shortName : b.name);
                        opt.label = b.name || b.shortName || '';
                        list.appendChild(opt);
                        const logo = b.logo || '';
                        if (b.name && logo) window.bankNameToLogo[b.name.toLowerCase()] = logo;
                        if (b.shortName && logo) window.bankShortToLogo[b.shortName.toLowerCase()] = logo;
                        if (b.code && logo) window.bankCodeToLogo[b.code.toLowerCase()] = logo;
                    });
                    // Cập nhật logo ban đầu nếu có giá trị sẵn
                    updateBankLogoForCell($("#nganhang"));
                    updateBankLogoForCell($("td[data-agency='agency_bank']"));
                })
                .catch(() => {});
        } catch (e) {}

        window.resolveBankLogoByText = function(text){
            if (!text) return null;
            const key = text.toLowerCase();
            return window.bankShortToLogo[key] || window.bankNameToLogo[key] || window.bankCodeToLogo[key] || null;
        };

        window.updateBankLogoForCell = function($td){
            if (!$td || !$td.length) return;
            const text = $td.find('.text-value').text().trim();
            const logo = window.resolveBankLogoByText(text);
            const $img = $td.find('img.bank-logo');
            if (!$img.length) return;
            if (logo) {
                $img.attr('src', logo).show();
            } else {
                $img.hide().attr('src', '');
            }
        };
    });

    function validateBasicInfo() {
        if ($("#isInstallAgency").is(":checked")) {
            // SỬA LỖI: Thêm $() để truyền vào một jQuery object, không phải string
            return parseInt(getCurrencyValue( $('#install_cost_agency') ), 10) > 0;
        } else {
            // SỬA LỖI: Thêm $() để truyền vào một jQuery object, không phải string
            return $("#ctv_id").val() !== '' && parseInt(getCurrencyValue( $('#install_cost_ctv') ), 10) > 0;
        }
    }
    
    // NÂNG CẤP: Hàm kiểm tra tổng thể mới
    function validateAll() {
        // 1. Chạy lại tất cả validation để bắt lỗi
        runAllInitialValidations();
        
        // 2. Kiểm tra thông tin cơ bản (CTV, chi phí...)
        if (!validateBasicInfo()) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi thông tin cơ bản',
                text: 'Vui lòng chọn CTV và nhập chi phí, hoặc chọn "Đại lý lắp đặt" và nhập chi phí.',
                timer: 3000,
                showConfirmButton: false
            });
            return false;
        }
        
        // 3. Kiểm tra cờ lỗi
        let hasErrors = Object.keys(validationErrors).length > 0;
        if (hasErrors) {
             Swal.fire({
                icon: 'error',
                title: 'Lỗi điền thông tin',
                text: 'Vui lòng sửa các lỗi được tô đỏ trước khi tiếp tục.',
                timer: 3000,
                showConfirmButton: false
            });
            return false;
        }
        
        return true; // Tất cả đều hợp lệ
    }


    function UpdateCollaborator() {
        let id = $("#ctv_id").val();
        let orderCode = "{{ $code }}"; // Lấy order_code từ PHP
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            id: id,
            order_code: orderCode
        };
        $("td[data-field]").each(function() {
            let $td = $(this);
            let field = $td.data("field");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
            }
            
            // NÂNG CẤP: Gửi ngày tháng đúng định dạng Y-m-d
            if (field === 'ngaycap' && value && value.includes('/')) {
                 let parts = value.split('/');
                 if (parts.length === 3) value = parts[2] + '-' + parts[1] + '-' + parts[0];
            }
            
            data[field] = value;
        });

        $.ajax({
            url: "{{ route('ctv.update') }}",
            method: "POST",
            data: data,
            success: function(response) {
                // Collaborator updated successfully
            },
            error: function(xhr, status, error) {
                // Error updating collaborator
            }
        });
    }

    function UpdateAgency() {
        let orderCode = "{{ $code }}"; // Lấy order_code từ PHP
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            order_code: orderCode
        };
        
        // Đảm bảo agency_phone luôn được gửi
        let agencyPhone = '';
        $("td[data-agency]").each(function() {
            let $td = $(this);
            let agency = $td.data("agency");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
            }
            
            if (agency === "agency_phone") {
                agencyPhone = value;
            }
            
            // Xử lý format ngày tháng cho agency_release_date
            if (agency === "agency_release_date" && value && value.includes('/')) {
                // Chuyển từ d/m/Y sang Y-m-d cho database
                let parts = value.split('/');
                if (parts.length === 3) {
                    let day = parts[0].padStart(2, '0');
                    let month = parts[1].padStart(2, '0');
                    let year = parts[2];
                    value = year + '-' + month + '-' + day;
                }
            }
            
            data[agency] = value;
        });
        
        // Kiểm tra nếu không có agency_phone
        if (!agencyPhone) {
            return;
        }
        
        $.ajax({
            url: "{{ route('agency.update') }}",
            method: "POST",
            data: data,
            success: function(response) {
                if (response.success) {
                    // Agency update successful
                }
            },
            error: function(xhr, status, error) {
                // Error updating agency
            }
        });
    }

    function Update() {
        $("#btnUpdate, #btnComplete, #btnPay").on("click", function(e) {
            e.preventDefault();
            
            // 1. Kiểm tra validation tổng thể (đã sửa ở bước trước)
            if (!validateAll()) {
                return; // Dừng lại nếu có lỗi
            }
            
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                text: "Hành động này không thể hoàn tác!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Có, tiếp tục!',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    const urlParams = new URLSearchParams(window.location.search);
                    const type = urlParams.get('type');
                    
                    let action = $(this).data('action');
                    let isInstallAgency = $("#isInstallAgency").is(":checked") ? 1 : 0;
                    let formData = new FormData();
                    formData.append("_token", "{{ csrf_token() }}");
                    formData.append("id", "{{ $data->order->id ?? $data->id }}");
                    formData.append("action", action);
                    formData.append("type", type);
                    formData.append("product", $('#product_name').val());
                    

                    if (isInstallAgency === 1) {
                        formData.append("ctv_id", 1);
                        formData.append("successed_at", $("#successed_at").val().trim());
                        
                        // SỬA LỖI TẠI ĐÂY: Thêm $()
                        formData.append("installcost", getCurrencyValue( $('#install_cost_agency') ));
                    
                    } else {
                        formData.append("ctv_id", $("#ctv_id").val());
                        formData.append("successed_at", $("#successed_at_ctv").val().trim());
                        
                        // SỬA LỖI TẠI ĐÂY: Thêm $()
                        formData.append("installcost", getCurrencyValue( $('#install_cost_ctv') ));
                        
                        let file = $("#install_review")[0].files[0];
                        if (file) {
                            formData.append("installreview", file);
                        }
                    }
                    OpenWaitBox();
                    $.ajax({
                        url: "{{ route('dieuphoi.update') }}",
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            CloseWaitBox();
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    UpdateCollaborator();
                                    if (hasAgencyChanges()) {
                                        UpdateAgency();
                                    }
                                    location.reload();
                                    loadTableData();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                })
                            }
                        },
                        error: function(xhr) {
                            CloseWaitBox();
                            alert("Có lỗi xảy ra khi cập nhật!");
                        }
                    });
                }
            });
        });
    }

    $(document).ready(function() {
        $('#province').on('change', function() {
            let provinceId = $(this).val();
            $('#district').empty().append('<option value="" selected>Quận/Huyện</option>');
            $('#ward').empty().append('<option value="" selected>Phường/Xã</option>');
            let url = '{{ route("ctv.getdistrict", ":province_id") }}'.replace(':province_id', provinceId);
            if (provinceId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        let $district = $('#district');
                        $district.empty();
                        $district.append('<option value="" disabled selected>Quận/Huyện</option>');
                        data.forEach(function(item) {
                            $district.append('<option value="' + item.district_id + '">' + item.name + '</option>');
                        });
                    },
                });
            }
            filterCollaborators()
        });

        $('#district').on('change', function() {
            let districtId = $(this).val();
            let url = '{{ route("ctv.getward", ":district_id") }}'.replace(':district_id', districtId);
            if (districtId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        let $ward = $('#ward');
                        $ward.empty();
                        $ward.append('<option value="" disabled selected>Xã/Phường</option>');
                        data.forEach(function(item) {
                            $ward.append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                        });
                    },
                });
            }
            filterCollaborators();
        });

        $('#ward').change(function() {
            filterCollaborators();
        });

        function filterCollaborators() {
            let province = $('#province').val();
            let district = $('#district').val();
            let ward = $('#ward').val();
            $.ajax({
                url: '{{ route("collaborators.filter") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    province: province,
                    district: district,
                    ward: ward
                },
                success: function(res) {
                    $('#tablecollaborator').html(res.html);
                },
                error: function(xhr) {
                    alert('Lỗi khi xử lý');
                }
            });
        }
    });

    // NÂNG CẤP: Gắn validation vào trình xử lý .edit-icon
    $(document).on("click", ".edit-icon", function() {
        let $td = $(this).closest("td");
        let $span = $td.find(".text-value");
        let oldValue = $span.text().trim();

        let field = $td.data("field");
        let agency = $td.data("agency");
        let fieldName = field || agency; // Tên định danh của trường

        let $input = $("<input>", {
            // Sửa đổi: Nếu là địa chỉ khách hàng, dùng textarea để có nhiều không gian hơn
            type: (fieldName === 'customer_address') ? 'textarea' : 'text',
            value: oldValue,
            class: "form-control d-inline-block w-100"
        });
        
        // Gắn data-field/data-agency vào input để dễ truy xuất
        if (field) $input.attr('data-field', field);
        if (agency) $input.attr('data-agency', agency);

        if (fieldName === "ngaycap" || fieldName === "agency_release_date") {
            $input.attr("type", "date");
            // Chuyển đổi format từ d/m/Y sang Y-m-d cho input date
            if (oldValue && oldValue.includes('/')) {
                let parts = oldValue.split('/');
                if (parts.length === 3) {
                    let day = parts[0].padStart(2, '0');
                    let month = parts[1].padStart(2, '0');
                    let year = parts[2];
                    $input.val(year + '-' + month + '-' + day);
                }
            }
        }
        // Xử lý khi người dùng nhập liệu
        $input.on("input change", function() {
            validateDynamicField($(this), fieldName);
        });
        
        // Gắn data-field/data-agency vào input để dễ truy xuất
        if (field) $input.attr('data-field', field);
        if (agency) $input.attr('data-agency', agency);

        if (fieldName === "ngaycap" || fieldName === "agency_release_date") {
            $input.attr("type", "date");
            // Chuyển đổi format từ d/m/Y sang Y-m-d cho input date
            if (oldValue && oldValue.includes('/')) {
                let parts = oldValue.split('/');
                if (parts.length === 3) {
                    let day = parts[0].padStart(2, '0');
                    let month = parts[1].padStart(2, '0');
                    let year = parts[2];
                    $input.val(year + '-' + month + '-' + day);
                }
            }
        }
        if (fieldName === "nganhang" || fieldName === "agency_bank") {
            $input.attr('list', 'bankList');
        }

        // --- BẮT ĐẦU GẮN VALIDATION ---
        $input.on("input change", function() {
            validateDynamicField($(this), fieldName);
        });
        // --- KẾT THÚC GẮN VALIDATION ---

        // Xử lý khi blur (rời input) - ĐÃ NÂNG CẤP
        $input.on("blur", function() {
            validateDynamicField($(this), fieldName); // Chạy validation lần cuối
            let newValue = $(this).val().trim();
            
            // Lưu giá trị hiển thị đầy đủ ban đầu để khôi phục khi lỗi
            let oldDisplayValue = $("#customer_address_full").val() || oldValue;
            if (fieldName === 'customer_address' && !oldDisplayValue) {
                // Fallback: ghép oldValue với fullAddress
                let fullAddress = "{{ $fullAddress }}";
                if (oldValue && fullAddress) {
                    oldDisplayValue = oldValue + ", " + fullAddress;
                } else if (fullAddress) {
                    oldDisplayValue = fullAddress;
                } else {
                    oldDisplayValue = oldValue;
                }
            }

            // Trường hợp 1: Người dùng xóa rỗng -> Luôn gỡ lỗi và cập nhật
            if (newValue === '') {
                hideError($(this)); // Gỡ lỗi
                $span.text('').show(); // Cập nhật span thành rỗng
            
            // Trường hợp 2: Người dùng nhập đúng (không rỗng VÀ không có cờ lỗi)
            } else if (!validationErrors[fieldName]) {
                // Xử lý format ngày tháng trước khi hiển thị
                if (fieldName === "ngaycap" || fieldName === "agency_release_date") {
                    if (newValue && newValue.includes('-')) {
                        let parts = newValue.split('-');
                        if (parts.length === 3) {
                            let year = parts[0];
                            let month = parts[1];
                            let day = parts[2];
                            newValue = day + '/' + month + '/' + year;
                        }
                    }
                }
                
                // Lưu địa chỉ khách hàng vào database nếu là customer_address
                if (fieldName === 'customer_address') {
                    let orderCode = "{{ $code }}";
                    if (orderCode) {
                        $.ajax({
                            url: "{{ route('dieuphoi.update.address') }}",
                            method: "POST",
                            data: {
                                _token: $('meta[name="csrf-token"]').attr("content"),
                                order_code: orderCode,
                                address: newValue
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Cập nhật lại full address với phần địa chỉ mới
                                    let fullAddress = "{{ $fullAddress }}";
                                    let fullAddressText = newValue;
                                    if (newValue && fullAddress) {
                                        fullAddressText = newValue + ", " + fullAddress;
                                    } else if (fullAddress) {
                                        fullAddressText = fullAddress;
                                    }
                                    $span.text(fullAddressText).show();
                                    // Cập nhật lại hidden inputs
                                    $("#customer_address_full").val(fullAddressText);
                                    $("#customer_address_detail").val(newValue);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Lỗi',
                                        text: response.message || 'Không thể cập nhật địa chỉ',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    // Quay về giá trị cũ nếu lưu thất bại
                                    $span.text(oldDisplayValue).show();
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: 'Có lỗi xảy ra khi cập nhật địa chỉ',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Quay về giá trị cũ nếu lưu thất bại
                                $span.text(oldDisplayValue).show();
                            }
                        });
                    } else {
                        // Nếu không có order_code, vẫn hiển thị giá trị mới
                        $span.text(newValue).show();
                    }
                } else {
                    $span.text(newValue).show(); // Cập nhật span với giá trị mới cho các trường khác
                }
            
            // Trường hợp 3: Người dùng nhập sai và rời đi (không rỗng VÀ có cờ lỗi)
            } else {
                hideError($(this)); // Gỡ lỗi (vì chúng ta không lưu giá trị sai)
                // Quay về giá trị cũ (dùng oldDisplayValue cho customer_address)
                let displayValue = (fieldName === 'customer_address') ? oldDisplayValue : oldValue;
                $span.text(displayValue).show();
            }

            $td.find(".edit-icon").show();
            $(this).remove();

            // Cập nhật logo ngân hàng sau khi rời input ở cả 2 trường hợp
            if (fieldName === 'nganhang' || fieldName === 'agency_bank') {
                updateBankLogoForCell($td);
            }
        });

        // Xử lý nhấn Enter
        $input.on("keypress", function(e) {
            if (e.which === 13) $(this).blur();
        });

        // Ẩn span và icon, hiển thị input
        $span.hide();
         // Ẩn icon bút// Nếu là địa chỉ khách hàng, ẩn cả phần địa chỉ tĩnh (tỉnh/huyện/xã)
        if (fieldName === 'customer_address') {
            $td.contents().filter(function() { return this.nodeType === 3; }).remove(); // Xóa text node ", {{ $fullAddress }}"
        }      
        $(this).hide();
        $td.prepend($input);
        $input.focus();
        
        // Chạy validation ngay khi input xuất hiện
        validateDynamicField($input, fieldName);
    });

    // Hàm load lịch sử thay đổi (không thay đổi)
    function loadHistory() {
        $('#historyLoading').show();
        $('#historyContent').hide();
        $('#historyEmpty').hide();
        
        let orderCode = "{{ $code }}";
        if (!orderCode) {
            $('#historyLoading').hide();
            $('#historyEmpty').show();
            return;
        }
        
        $.ajax({
            url: "{{ route('ctv.order.history', ':order_code') }}".replace(':order_code', orderCode),
            method: "GET",
            success: function(response) {
                $('#historyLoading').hide();
                if (response.success && response.data.history.length > 0) {
                    displayHistory(response.data.history);
                    $('#historyContent').show();
                } else {
                    $('#historyEmpty').show();
                }
            },
            error: function(xhr, status, error) {
                $('#historyLoading').hide();
                $('#historyEmpty').show();
                console.error('Lỗi khi tải lịch sử:', error);
            }
        });
    }

    // Hàm hiển thị lịch sử (không thay đổi)
    function displayHistory(history) {
        let html = '';
        
        history.forEach(function(item, index) {
            html += `
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="bi bi-${getActionIcon(item.action_type)} me-2"></i>
                                ${item.action_type_text || item.action_type}
                            </h6>
                            <small class="text-muted">${item.formatted_edited_at}</small>
                        </div>
                        <div>
                            <span class="badge bg-${getActionBadgeColor(item.action_type)}">${item.action_type_text || item.action_type}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text">${formatStatusComment(item.comments || 'Không có ghi chú')}</p>
                        <p class="card-text"><strong>Người thực hiện:</strong> ${item.edited_by || 'Hệ thống'}</p>
                        
                        ${item.changes_detail && item.changes_detail.length > 0 ? `
                            <div class="mt-3">
                                <h6>Chi tiết thay đổi:</h6>
                                
                                ${getCtvChanges(item.changes_detail).length > 0 ? `
                                    <div class="mb-3">
                                        <h6 class="text-primary">
                                            <i class="bi bi-person me-1"></i>Thông tin CTV
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th style="width: 25%;">Trường</th>
                                                        <th style="width: 35%;">Giá trị cũ</th>
                                                        <th style="width: 35%;">Giá trị mới</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${getCtvChanges(item.changes_detail).map(change => `
                                                        <tr>
                                                            <td><strong>${change.field_name}</strong></td>
                                                            <td>
                                                                <span class="text-muted">${change.old_value || 'Trống'}</span>
                                                            </td>
                                                            <td>
                                                                <span class="text-success">${change.new_value || 'Trống'}</span>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${getAgencyChanges(item.changes_detail).length > 0 ? `
                                    <div class="mb-3">
                                        <h6 class="text-info">
                                            <i class="bi bi-building me-1"></i>Thông tin Đại lý
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-info">
                                                    <tr>
                                                        <th style="width: 25%;">Trường</th>
                                                        <th style="width: 35%;">Giá trị cũ</th>
                                                        <th style="width: 35%;">Giá trị mới</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${getAgencyChanges(item.changes_detail).map(change => `
                                                        <tr>
                                                            <td><strong>${change.field_name}</strong></td>
                                                            <td>
                                                                <span class="text-muted">${change.old_value || 'Trống'}</span>
                                                            </td>
                                                            <td>
                                                                <span class="text-success">${change.new_value || 'Trống'}</span>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        $('#historyList').html(html);
    }

    // Các hàm helper cho lịch sử (không thay đổi)
    function getActionIcon(actionType) {
        const icons = {
            'create': 'plus-circle',
            'update': 'pencil-square',
            'delete': 'trash',
            'update_agency': 'building',
            'switch_to_agency': 'arrow-right-circle',
            'switch_to_ctv': 'arrow-left-circle',
            'clear': 'x-circle',
            'status_change': 'arrow-repeat',
            'complete': 'check-circle',
            'payment': 'credit-card'
        };
        return icons[actionType] || 'info-circle';
    }

    function getActionBadgeColor(actionType) {
        const colors = {
            'create': 'success',
            'update': 'primary',
            'delete': 'danger',
            'update_agency': 'info',
            'switch_to_agency': 'warning',
            'switch_to_ctv': 'secondary',
            'clear': 'dark',
            'status_change': 'primary',
            'complete': 'success',
            'payment': 'info'
        };
        return colors[actionType] || 'secondary';
    }

    function getStatusColor(statusText) {
        const colors = {
            'Chưa điều phối': 'secondary',
            'Đã điều phối': 'primary',
            'Đã hoàn thành': 'success',
            'Đã thanh toán': 'info'
        };
        return colors[statusText] || 'muted';
    }

    function formatStatusComment(comment) {
        const regex = /Thay đổi trạng thái: (.+) → (.+)/;
        const match = comment.match(regex);

        if (match && match.length === 3) {
            const oldStatusText = match[1].trim();
            const newStatusText = match[2].trim();
            const oldStatusColor = getStatusColor(oldStatusText);
            const newStatusColor = getStatusColor(newStatusText);
            return `Thay đổi trạng thái: <span class="text-${oldStatusColor} fw-bold">${oldStatusText}</span> → <span class="text-${newStatusColor} fw-bold">${newStatusText}</span>`;
        }
        return comment; // Return original comment if not a status change format
    }

    function getCtvChanges(changes) {
        return changes.filter(change => 
            change.field_name.includes('CTV') || 
            (!change.field_name.includes('đại lý') && 
             !change.field_name.includes('Đại lý') &&
             !change.field_name.includes('agency'))
        );
    }

    function getAgencyChanges(changes) {
        return changes.filter(change => 
            change.field_name.includes('đại lý') || 
            change.field_name.includes('Đại lý') ||
            change.field_name.includes('agency')
        );
    }

    // Hàm kiểm tra xem có thay đổi thông tin đại lý không (không thay đổi)
    function hasAgencyChanges() {
        // Lấy giá trị hiện tại của các trường đại lý
        let currentAgencyData = {
            agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
            agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
            agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
            agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
            agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
            agency_branch: $("td[data-agency='agency_branch'] .text-value").text().trim(),
            agency_cccd: $("td[data-agency='agency_cccd'] .text-value").text().trim(),
            agency_release_date: $("td[data-agency='agency_release_date'] .text-value").text().trim()
        };

        // So sánh với giá trị ban đầu đã lưu
        for (let field in originalAgencyData) {
            if (originalAgencyData[field] !== currentAgencyData[field]) {
                return true; // Có thay đổi
            }
        }
        
        return false; // Không có thay đổi
    }
</script>
@endsection