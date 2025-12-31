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
                                        @php
                                        // Ưu tiên từ request_agency (nếu có), sau đó installation_order, cuối cùng data gốc
                                        $productName = $requestAgency->product_name ?? $installationOrder->product ?? $data->product_name ?? $data->product ?? '';
                                        @endphp
                                        <input type="text" id="product_name" hidden value="{{ $productName }}">
                                        {{ $productName }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Khách hàng:</th>
                                    <td colspan="3" data-field="customer_name">
                                        @php
                                        // Ưu tiên từ request_agency (nếu có), sau đó installation_order, cuối cùng order/warranty_request
                                        $customerName = $requestAgency->customer_name ?? $installationOrder->full_name ?? $order->customer_name ?? $data->order->customer_name ?? $data->full_name ?? '';
                                        @endphp
                                        <span class="text-value">{{ $customerName }}</span>
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;" title="Sửa tên khách hàng"></i>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại:</th>
                                    <td colspan="3" data-field="customer_phone">
                                        @php
                                        // Ưu tiên từ request_agency (nếu có), sau đó installation_order, cuối cùng order/warranty_request
                                        $customerPhone = $requestAgency->customer_phone ?? $installationOrder->phone_number ?? $order->customer_phone ?? $data->order->customer_phone ?? $data->phone_number ?? '';
                                        @endphp
                                        <span class="text-value">{{ $customerPhone }}</span>
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;" title="Sửa số điện thoại khách hàng"></i>
                                    </td>
                                </tr>
                                {{-- Địa chỉ chi tiết (số nhà, đường...) --}}
                                <tr>
                                    <th>Địa chỉ:</th>
                                    <td colspan="3" data-field="customer_address">
                                        @php
                                        // CHỈ hiển thị trường address trong bảng installation_orders
                                        $customerAddress = $installationOrder->address ?? '';
                                        @endphp

                                        <span class="text-value">{{ $customerAddress }}</span>

                                        @if(($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                            <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;" title="Sửa địa chỉ chi tiết"></i>
                                        @endif

                                        <input type="hidden" id="customer_address_full" value="{{ $customerAddress }}">
                                    </td>
                                </tr>
                                
                                {{-- Tỉnh/Thành phố --}}
                                <tr>
                                    <th>Tỉnh/TP:</th>
                                    <td colspan="3">
                                        <span id="region_province_text" class="text-value">{{ $provinceName ?? '' }}</span>
                                        @if(($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                            <i class="bi bi-pencil ms-2 edit-icon region-edit-btn" data-field="province" style="cursor:pointer;" title="Sửa Tỉnh/TP"></i>
                                        @endif
                                    </td>
                                </tr>
                    
                                {{-- Quận/Huyện --}}
                                <tr>
                                    <th>Quận/Huyện:</th>
                                    <td colspan="3">
                                        <span id="region_district_text" class="text-value">{{ $districtName ?? '' }}</span>
                                        @if(($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                            <i class="bi bi-pencil ms-2 edit-icon region-edit-btn" data-field="district" style="cursor:pointer;" title="Sửa Quận/Huyện"></i>
                                        @endif
                                    </td>
                                </tr>
                    
                                {{-- Xã/Phường --}}
                                <tr>
                                    <th>Phường/Xã:</th>
                                    <td colspan="3">
                                        <span id="region_ward_text" class="text-value">{{ $wardName ?? '' }}</span>
                                        @if(($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null)
                                            <i class="bi bi-pencil ms-2 edit-icon region-edit-btn" data-field="ward" style="cursor:pointer;" title="Sửa Phường/Xã"></i>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Hidden lưu lại ID khu vực hiện tại để dùng cho JS --}}
                                <input type="hidden" id="current_province_id" value="{{ $provinceId ?? '' }}">
                                <input type="hidden" id="current_district_id" value="{{ $districtId ?? '' }}">
                                <input type="hidden" id="current_ward_id" value="{{ $wardId ?? '' }}">
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
                                    <th>Chủ TK:</th>
                                    <td id="bank_account" data-field="bank_account">
                                        @php
                                        $bankNameAccount = $ctv->bank_account ?? $ctv->bank_account ?? '';
                                        @endphp
                                        <span class="text-value">{{ $bankNameAccount }}</span>
                                        <img class="bank-logo ms-2" alt="logo ngân hàng" style="height:45px; display:none;"/>
                                        @if (empty($bankNameAccount))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
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
                @php
                    $requestAgencyType = $requestAgency->type ?? null;
                    $requestAgencyTypeLabelShort = match((string)$requestAgencyType) {
                        '0' => 'Đại lý tự lắp đặt',
                        '1' => 'Yêu cầu CTV',
                        default => 'Yêu cầu đại lý',
                    };
                    $requestAgencyTypeLabelFull = match((string)$requestAgencyType) {
                        '0' => 'Đại lý tự lắp đặt (đại lý tự thực hiện lắp đặt)',
                        '1' => 'Yêu cầu Kuchen cử CTV lắp đặt tại nhà',
                        default => 'Yêu cầu lắp đặt từ đại lý',
                    };
                    $requestAgencyTypeBadge = match((string)$requestAgencyType) {
                        '0' => 'info',
                        '1' => 'warning',
                        default => 'secondary',
                    };
                @endphp
                <div class="card-header bg-secondary text-white text-center position-relative">
                    <h5 class="mb-0">Thông tin đại lý</h5>
                    @if($requestAgency)
                    <span class="badge bg-{{ $requestAgencyTypeBadge }} position-absolute top-0 end-0 m-2" style="font-size: 0.7rem;" title="{{ $requestAgencyTypeLabelFull }}">
                        <i class="bi bi-info-circle me-1"></i>{{ $requestAgencyTypeLabelShort }}
                    </span>
                    @endif
                </div>
                <div class="card-body">
                    @if($requestAgency)
                    {{-- Hidden input để JavaScript sử dụng dữ liệu request_agency --}}
                    <div id="request_agency_data" 
                        data-agency-id="{{ $requestAgency->agency_id ?? '' }}"
                        data-agency-name="{{ $requestAgencyAgency->name ?? '' }}"
                        data-agency-phone="{{ $requestAgencyAgency->phone ?? '' }}"
                        data-agency-address="{{ $requestAgencyAgency->address ?? '' }}"
                        data-agency-bank="{{ $requestAgencyAgency->bank_name_agency ?? '' }}"
                        data-agency-bank-account="{{ $requestAgencyAgency->bank_account ?? '' }}"
                        data-agency-paynumber="{{ $requestAgencyAgency->sotaikhoan ?? '' }}"
                        data-agency-branch="{{ $requestAgencyAgency->chinhanh ?? '' }}"
                        data-agency-cccd="{{ $requestAgencyAgency->cccd ?? '' }}"
                        data-agency-release-date="{{ $requestAgencyAgency->ngaycap ?? '' }}"
                        data-agency-type="{{ $requestAgencyType ?? '' }}"
                        data-installation-address="{{ $requestAgency->installation_address ?? '' }}"
                        data-product-name="{{ $requestAgency->product_name ?? '' }}"
                        data-customer-name="{{ $requestAgency->customer_name ?? '' }}"
                        data-customer-phone="{{ $requestAgency->customer_phone ?? '' }}"
                        data-notes="{{ $requestAgency->notes ?? '' }}"
                        data-status="{{ $requestAgency->status ?? '' }}"
                        style="display: none;">
                    </div>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Thông báo:</strong>
                        <p class="mb-1"><strong>Loại yêu cầu:</strong> <span class="badge bg-{{ $requestAgencyTypeBadge }}">{{ $requestAgencyTypeLabelFull }}</span></p>
                        @if((string)$requestAgencyType === '0')
                            <p class="mb-1">Hệ thống ghi nhận đại lý tự thực hiện lắp đặt. Khi tích "Đại lý lắp đặt", thông tin sẽ được tự động điền từ yêu cầu.</p>
                        @elseif((string)$requestAgencyType === '1')
                            <p class="mb-1">Hệ thống ghi nhận đại lý yêu cầu Kuchen cử cộng tác viên đến lắp đặt tại nhà khách hàng. Vui lòng điều phối/ chọn CTV phù hợp (không cần bật "Đại lý lắp đặt" nếu CTV thực hiện).</p>
                        @else
                            <p class="mb-1">Hệ thống ghi nhận yêu cầu lắp đặt từ đại lý.</p>
                        @endif
                        <br><small class="text-muted">
                            <strong>Trạng thái:</strong> 
                            @if($requestAgency->status == 'chua_xac_nhan_daily')
                                <span class="badge bg-danger">Chưa xác nhận đại lý</span>
                            @elseif($requestAgency->status == 'da_xac_nhan_daily')
                                <span class="badge bg-warning">Đã xác nhận đại lý</span>
                            @elseif($requestAgency->status == 'da_dieu_phoi')
                                <span class="badge bg-info">Đã điều phối</span>
                            @elseif($requestAgency->status == 'hoan_thanh')
                                <span class="badge bg-success">Hoàn thành</span>
                            @elseif($requestAgency->status == 'da_thanh_toan')
                                <span class="badge bg-secondary">Đã thanh toán</span>
                            @else
                                <span class="badge bg-secondary">{{ $requestAgency->status_name }}</span>
                            @endif
                            @if($requestAgency->notes)
                            <br><strong>Ghi chú:</strong> {{ $requestAgency->notes }}
                            @endif
                        </small>
                    </div>
                    @endif
                    <div class="table-responsive col-12">
                        <table class="table table-striped">
                            <tbody>
                                @php
                                    $isAgencyInstall = !empty($installationOrder->agency_name) && empty($installationOrder->collaborator_id);
                                    $shouldLoadFromRequestAgency = ((string)$requestAgencyType !== '0') || $isAgencyInstall;
                                    $isAgencyFromRequest = ($agency && $requestAgencyAgency && $agency->id == $requestAgencyAgency->id);
                                    $shouldLoadFromAgency = $shouldLoadFromRequestAgency || !$isAgencyFromRequest;
                                @endphp
                                <tr>
                                    <th class="w-50">Tên đại lý:</th>
                                    <td class="w-50" data-agency="agency_name">
                                        @php
                                            $agencyNameValue = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->name ?? null) : null;
                                            $agencyNameValue = $agencyNameValue ?? $installationOrder->agency_name ?? $data->order->agency_name ?? $data->agency_name ?? '';
                                        @endphp
                                        <span class="text-value">{{ $agencyNameValue }}</span>
                                        @php
                                            $hasAgencyName = !empty($agencyNameValue);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyName && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại đại lý:</th>
                                    <td data-agency="agency_phone">
                                        @php
                                            $agencyPhoneValue = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->phone ?? null) : null;
                                            $agencyPhoneValue = $agencyPhoneValue ?? $installationOrder->agency_phone ?? $data->order->agency_phone ?? $data->agency_phone ?? '';
                                        @endphp
                                        <span class="text-value">{{ $agencyPhoneValue }}</span>
                                        @php
                                            $hasAgencyPhone = !empty($agencyPhoneValue);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyPhone && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ đại lý:</th>
                                    <td data-agency="agency_address">
                                        @php
                                            $agencyAddressValue = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->address ?? null) : null;
                                            $agencyAddressValue = $agencyAddressValue ?? $installationOrder->agency_address ?? $data->order->agency_address ?? $data->agency_address ?? ($shouldLoadFromAgency ? ($agency->address ?? '') : '');
                                        @endphp
                                        <span class="text-value">{{ $agencyAddressValue }}</span>
                                        @php
                                            $hasAgencyAddress = !empty($agencyAddressValue);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyAddress && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Chủ tài khoản ngân hàng:</th>
                                    <td data-agency="bank_account">
                                        @php
                                            $agencyBankAccountValue = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->bank_account ?? null) : null;
                                            $agencyBankAccountValue = $agencyBankAccountValue ?? $installationOrder->bank_account ?? $data->order->bank_account ?? $data->bank_account ?? ($shouldLoadFromAgency ? ($agency->bank_account ?? '') : '');
                                        @endphp
                                        <span class="text-value">{{ $agencyBankAccountValue }}</span>
                                        @php
                                            $hasAgencyBankAccount = !empty($agencyBankAccountValue);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyBankAccount && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ngân hàng:</th>
                                    <td data-agency="agency_bank">
                                        @php
                                            $agencyBankValue = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->bank_name_agency ?? null) : null;
                                            $agencyBankValue = $agencyBankValue ?? $installationOrder->agency_bank ?? $data->order->agency_bank ?? $data->agency_bank ?? ($shouldLoadFromAgency ? ($agency->bank_name_agency ?? $agency->nganhang ?? '') : '');
                                        @endphp
                                        <span class="text-value">{{ $agencyBankValue }}</span>
                                        <img class="bank-logo ms-2" alt="logo ngân hàng" style="height:45px; display:none;"/>
                                        @php
                                            $hasAgencyBank = !empty($agencyBankValue);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyBank && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số tài khoản:</th>
                                    <td data-agency="agency_paynumber">
                                        @php
                                            $agencyPaynumberValue = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->sotaikhoan ?? null) : null;
                                            $agencyPaynumberValue = $agencyPaynumberValue ?? $installationOrder->agency_paynumber ?? $data->order->agency_paynumber ?? $data->agency_paynumber ?? ($shouldLoadFromAgency ? ($agency->sotaikhoan ?? '') : '');
                                        @endphp
                                        <span class="text-value">{{ $agencyPaynumberValue }}</span>
                                        @php
                                            $hasAgencyPaynumber = !empty($agencyPaynumberValue);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyPaynumber && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Chi nhánh:</th>
                                    <td data-agency="agency_branch">
                                        @php
                                            $agencyBranchValue = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->chinhanh ?? null) : null;
                                            $agencyBranchValue = $agencyBranchValue ?? $installationOrder->agency_branch ?? $data->order->agency_branch ?? $data->agency_branch ?? ($shouldLoadFromAgency ? ($agency->chinhanh ?? '') : '');
                                        @endphp
                                        <span class="text-value">{{ $agencyBranchValue }}</span>
                                        @php
                                            $hasAgencyBranch = !empty($agencyBranchValue);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyBranch && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Căn cước công dân:</th>
                                    <td data-agency="agency_cccd">
                                        @php
                                            $agencyCccdValue = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->cccd ?? null) : null;
                                            $agencyCccdValue = $agencyCccdValue ?? $installationOrder->agency_cccd ?? $data->order->agency_cccd ?? $data->agency_cccd ?? ($shouldLoadFromAgency ? ($agency->cccd ?? '') : '');
                                        @endphp
                                        <span class="text-value">{{ $agencyCccdValue }}</span>
                                        @php
                                            $hasAgencyCccd = !empty($agencyCccdValue);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyCccd && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ngày cấp:</th>
                                    <td data-agency="agency_release_date">
                                        @php
                                            $agencyReleaseDate = $shouldLoadFromRequestAgency ? ($requestAgencyAgency->ngaycap ?? null) : null;
                                            $agencyReleaseDate = $agencyReleaseDate ?? $installationOrder->agency_release_date ?? $data->order->agency_release_date ?? $data->agency_release_date ?? ($shouldLoadFromAgency ? ($agency->ngaycap ?? '') : '');
                                            $agencyReleaseDateFormatted = $agencyReleaseDate ? (is_string($agencyReleaseDate) ? \Carbon\Carbon::parse($agencyReleaseDate)->format('d/m/Y') : '') : '';
                                        @endphp
                                        <span class="text-value">{{ $agencyReleaseDateFormatted }}</span>
                                        @php
                                            $hasAgencyReleaseDate = !empty($agencyReleaseDate);
                                            $canEditAgency = ($statusInstall ?? 0) != 0 && ($statusInstall ?? null) !== null;
                                        @endphp
                                        @if($hasAgencyReleaseDate && $canEditAgency)
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @elseif((($statusInstall ?? 0) == 0 || ($statusInstall ?? null) === null))
                                        <i class="bi bi-pencil ms-2 edit-icon agency-edit-icon" style="cursor:pointer; display:none;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        @php
                                            // Xác định xem đây có phải là đại lý lắp đặt không
                                            // Logic: có agency_name trong installationOrder và không có collaborator_id
                                            $isAgencyInstall = !empty($installationOrder->agency_name) && empty($installationOrder->collaborator_id);
                                        @endphp
                                        <div class="d-flex flex-wrap gap-3">
                                            <label class="d-flex align-items-center fw-bold" style="width: max-content;">
                                                <input type="checkbox" id="isInstallAgency" class="me-2" {{ $isAgencyInstall ? 'checked' : '' }}>
                                                Đại lý lắp đặt
                                            </label>
                                        </div>
                                        @if(isset($allRequestAgencies) && $allRequestAgencies->count() > 1)
                                        <div class="alert alert-info mt-2 mb-0" id="multiple-agencies-alert" style="display: {{ $isAgencyInstall ? 'block' : 'none' }};">
                                            <strong><i class="bi bi-info-circle me-2"></i>Có {{ $allRequestAgencies->count() }} đại lý gửi yêu cầu cho đơn hàng này:</strong>
                                            <ul class="mb-0 mt-2">
                                                @foreach($allRequestAgencies as $index => $reqAgency)
                                                    @php
                                                        $reqAgencyAgency = $reqAgency->agency ?? null;
                                                        $isFirst = $index === 0;
                                                        $statusBadge = match($reqAgency->status) {
                                                            \App\Models\KyThuat\RequestAgency::STATUS_CHUA_XAC_NHAN_AGENCY => 'bg-secondary',
                                                            \App\Models\KyThuat\RequestAgency::STATUS_DA_XAC_NHAN_AGENCY => 'bg-warning',
                                                            \App\Models\KyThuat\RequestAgency::STATUS_CHO_KIEM_TRA => 'bg-info',
                                                            default => 'bg-secondary'
                                                        };
                                                    @endphp
                                                    <li class="mb-1">
                                                        <strong>{{ $isFirst ? '✓ ' : '' }}{{ $reqAgencyAgency->name ?? 'N/A' }}</strong> 
                                                        ({{ $reqAgencyAgency->phone ?? 'N/A' }})
                                                        - <span class="badge {{ $statusBadge }}">{{ $reqAgency->status_name }}</span>
                                                        @if($isFirst)
                                                            <span class="text-success">(Đại lý đầu tiên - Đang sử dụng)</span>
                                                        @elseif($reqAgency->status === \App\Models\KyThuat\RequestAgency::STATUS_CHO_KIEM_TRA)
                                                            <span class="text-info">(Chờ kiểm tra)</span>
                                                        @endif
                                                        <br>
                                                        <small class="text-muted">Yêu cầu lúc: {{ $reqAgency->created_at->format('d/m/Y H:i') }}</small>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="installCostRow" style="display: none;">
                                    <th>Chi phí lắp đặt:</th>
                                    <td>
                                        @php
                                            $installCostAgency = $installationOrder->install_cost ?? $data->order->install_cost ?? $data->install_cost ?? 0;
                                        @endphp
                                        <input type="text" id="install_cost_agency" width="100%" class="form-control install_cost" name="install_cost_agency" value="{{ number_format($installCostAgency, 0, ',', '') }}" placeholder="Nhập chi phí">
                                        <div class="text-danger mt-1 error" id="install_cost_error" style="display:none;"></div>
                                    </td>
                                </tr>
                                <tr class="installCostRow" style="display: none;">
                                    <th>Ngày hoàn thành:</th>
                                    <td>
                                        @php
                                            $successedAtAgency = $installationOrder->successed_at ?? $data->order->successed_at ?? $data->successed_at ?? '';
                                            // Format date nếu có giá trị
                                            if ($successedAtAgency && is_string($successedAtAgency)) {
                                                try {
                                                    $date = new DateTime($successedAtAgency);
                                                    $successedAtAgency = $date->format('Y-m-d');
                                                } catch (Exception $e) {
                                                    // Giữ nguyên nếu không parse được
                                                }
                                            }
                                        @endphp
                                        <input type="date" id="successed_at" width="100%" class="form-control successed_at" name="successed_at" value="{{ $successedAtAgency }}">
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

@include('components.edit_ctv_history')

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
            case 'customer_name':
                // Tên khách hàng: chỉ cho phép chữ và khoảng trắng, tối đa 80 ký tự
                if (value && !/^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY]+$/.test(value)) { 
                    showError($input, "Tên khách hàng chỉ được chứa chữ và dấu cách.");
                } else if (value.length > 80) {
                    showError($input, "Tối đa 80 ký tự.");
                }
                break;

            case 'customer_phone':
                // SĐT khách hàng: chỉ số, độ dài 9-11
                if (value && !/^[0-9]+$/.test(value)) {
                    showError($input, "Số điện thoại chỉ được chứa số.");
                } else if (value && (value.length < 9 || value.length > 11)) {
                    showError($input, "Số điện thoại phải từ 9 đến 11 số.");
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
            
            // Hiển thị/ẩn thông báo nhiều đại lý
            if ($("#multiple-agencies-alert").length) {
                if ($(this).is(":checked")) {
                    $("#multiple-agencies-alert").slideDown();
                } else {
                    $("#multiple-agencies-alert").slideUp();
                }
            }
            
            if ($(this).is(":checked")) {
                // Clear các trường CTV (không lưu giá trị hiện tại)
                clearCtvData();
                
                // Hiển thị icon chỉnh sửa cho các trường đại lý khi checkbox được tích
                $(".agency-edit-icon").show();
                
                // LOGIC: Nếu có request_agency, tự động điền thông tin từ yêu cầu đại lý
                // Ưu tiên: request_agency > dữ liệu hiện có
                let $requestAgencyData = $("#request_agency_data");
                if ($requestAgencyData.length) {
                    let agencyId = $requestAgencyData.data('agency-id') || '';
                    let agencyName = $requestAgencyData.data('agency-name') || '';
                    let agencyPhone = $requestAgencyData.data('agency-phone') || '';
                    let agencyAddress = $requestAgencyData.data('agency-address') || '';
                    let agencyBank = $requestAgencyData.data('agency-bank') || '';
                    let agencyBankAccount = $requestAgencyData.data('agency-bank-account') || '';
                    let agencyPayNumber = $requestAgencyData.data('agency-paynumber') || '';
                    let agencyBranch = $requestAgencyData.data('agency-branch') || '';
                    let agencyCccd = $requestAgencyData.data('agency-cccd') || '';
                    let agencyReleaseDate = $requestAgencyData.data('agency-release-date') || '';
                    let installationAddress = $requestAgencyData.data('installation-address') || '';
                    let productName = $requestAgencyData.data('product-name') || '';
                    let customerName = $requestAgencyData.data('customer-name') || '';
                    let customerPhone = $requestAgencyData.data('customer-phone') || '';
                    let notes = $requestAgencyData.data('notes') || '';
                    
                    let hasChanges = false;
                    
                    // Cập nhật thông tin đại lý (ưu tiên từ request_agency)
                    if (agencyName) {
                        let currentAgencyName = $("td[data-agency='agency_name'] .text-value").text().trim();
                        if (agencyName !== currentAgencyName) {
                            $("td[data-agency='agency_name'] .text-value").text(agencyName);
                            hasChanges = true;
                        }
                    }
                    if (agencyPhone) {
                        let currentAgencyPhone = $("td[data-agency='agency_phone'] .text-value").text().trim();
                        if (agencyPhone !== currentAgencyPhone) {
                            $("td[data-agency='agency_phone'] .text-value").text(agencyPhone);
                            hasChanges = true;
                        }
                    }
                    if (agencyAddress) {
                        let currentAgencyAddress = $("td[data-agency='agency_address'] .text-value").text().trim();
                        if (agencyAddress !== currentAgencyAddress) {
                            $("td[data-agency='agency_address'] .text-value").text(agencyAddress);
                            hasChanges = true;
                        }
                    }
                    if (agencyBank) {
                        let currentAgencyBank = $("td[data-agency='agency_bank'] .text-value").text().trim();
                        if (agencyBank !== currentAgencyBank) {
                            $("td[data-agency='agency_bank'] .text-value").text(agencyBank);
                            hasChanges = true;
                        }
                    }
                    if (agencyBankAccount) {
                        let currentAgencyBankAccount = $("td[data-agency='bank_account'] .text-value").text().trim();
                        if (agencyBankAccount !== currentAgencyBankAccount) {
                            $("td[data-agency='bank_account'] .text-value").text(agencyBankAccount);
                            hasChanges = true;
                        }
                    }
                    if (agencyPayNumber) {
                        let currentAgencyPay = $("td[data-agency='agency_paynumber'] .text-value").text().trim();
                        if (agencyPayNumber !== currentAgencyPay) {
                            $("td[data-agency='agency_paynumber'] .text-value").text(agencyPayNumber);
                            hasChanges = true;
                        }
                    }
                    if (agencyBranch) {
                        let currentAgencyBranch = $("td[data-agency='agency_branch'] .text-value").text().trim();
                        if (agencyBranch !== currentAgencyBranch) {
                            $("td[data-agency='agency_branch'] .text-value").text(agencyBranch);
                            hasChanges = true;
                        }
                    }
                    if (agencyCccd) {
                        let currentAgencyCccd = $("td[data-agency='agency_cccd'] .text-value").text().trim();
                        if (agencyCccd !== currentAgencyCccd) {
                            $("td[data-agency='agency_cccd'] .text-value").text(agencyCccd);
                            hasChanges = true;
                        }
                    }
                    if (agencyReleaseDate) {
                        let currentAgencyReleaseDate = $("td[data-agency='agency_release_date'] .text-value").text().trim();
                        let formattedReleaseDate = agencyReleaseDate;
                        // Nếu ngày dạng YYYY-MM-DD, đổi sang dd/mm/YYYY cho đồng nhất
                        if (agencyReleaseDate.match(/^\\d{4}-\\d{2}-\\d{2}$/)) {
                            let parts = agencyReleaseDate.split('-');
                            formattedReleaseDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                        if (formattedReleaseDate !== currentAgencyReleaseDate) {
                            $("td[data-agency='agency_release_date'] .text-value").text(formattedReleaseDate);
                            hasChanges = true;
                        }
                    }
                    
                    // Cập nhật địa chỉ lắp đặt khách hàng (ưu tiên từ request_agency)
                    // TRÁNH nhân đôi địa chỉ: sử dụng trực tiếp installationAddress (đã đầy đủ)
                    if (installationAddress) {
                        let fullAddressText = installationAddress;
                        let currentAddress = $("td[data-field='customer_address'] .text-value").text().trim();
                        if (fullAddressText !== currentAddress) {
                            $("td[data-field='customer_address'] .text-value").text(fullAddressText);
                            $("#customer_address_full").val(fullAddressText);
                            hasChanges = true;
                        }
                    }
                    
                    // Cập nhật tên sản phẩm nếu có
                    if (productName && $("#product_name").length) {
                        let currentProduct = $("#product_name").val();
                        if (productName !== currentProduct) {
                            $("#product_name").val(productName);
                            hasChanges = true;
                        }
                    }
                    
                    // Hiển thị thông báo nếu có thay đổi
                    if (hasChanges && !Swal.isVisible()) {
                        let message = 'Thông tin đã được tự động điền từ yêu cầu lắp đặt của đại lý.';
                        if (notes) {
                            message += '\n\nGhi chú: ' + notes;
                        }
                        Swal.fire({
                            icon: 'info',
                            title: 'Đã điền thông tin từ yêu cầu đại lý',
                            text: message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    // Không có request_agency_data: Cho phép nhập thủ công
                    // Hiển thị icon chỉnh sửa để người dùng có thể nhập thông tin đại lý
                    $(".agency-edit-icon").show();
                }
                
                $(".installCostRow").show();
                $(".ctv_row").hide();
                $("#install_cost_row").hide();
                $("#install_file").hide();
                $("#table_collaborator").hide();
            } else {
                // Khôi phục giá trị ban đầu
                restoreOriginalCtvData();
                
                // Ẩn icon chỉnh sửa đại lý khi bỏ tích checkbox
                $(".agency-edit-icon").hide();
                
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

        // Khi trang load, nếu đã tích "Đại lý lắp đặt" và có request_agency, điền thông tin
        // LOGIC: Tự động điền thông tin từ request_agency khi đã tích checkbox
        // CHỈ load từ request_agency nếu requestAgencyType != '0' hoặc checkbox đã được tích
        if ($("#isInstallAgency").is(":checked")) {
            // Hiển thị icon chỉnh sửa cho các trường đại lý
            $(".agency-edit-icon").show();
            
            let $requestAgencyData = $("#request_agency_data");
            if ($requestAgencyData.length) {
                let agencyType = $requestAgencyData.data('agency-type') || '';
                // Chỉ load từ request_agency nếu không phải đại lý tự lắp đặt (type != '0') hoặc checkbox đã được tích
                let shouldLoadFromRequestAgency = (agencyType !== '0') || $("#isInstallAgency").is(":checked");
                
                if (shouldLoadFromRequestAgency) {
                    let agencyName = $requestAgencyData.data('agency-name') || '';
                    let agencyPhone = $requestAgencyData.data('agency-phone') || '';
                    let agencyAddress = $requestAgencyData.data('agency-address') || '';
                    let agencyBank = $requestAgencyData.data('agency-bank') || '';
                    let agencyBankAccount = $requestAgencyData.data('agency-bank-account') || '';
                    let agencyPayNumber = $requestAgencyData.data('agency-paynumber') || '';
                    let agencyBranch = $requestAgencyData.data('agency-branch') || '';
                    let agencyCccd = $requestAgencyData.data('agency-cccd') || '';
                    let agencyReleaseDate = $requestAgencyData.data('agency-release-date') || '';
                    let installationAddress = $requestAgencyData.data('installation-address') || '';
                    let productName = $requestAgencyData.data('product-name') || '';
                    
                    // Cập nhật thông tin đại lý (ưu tiên từ request_agency)
                    if (agencyName) {
                        $("td[data-agency='agency_name'] .text-value").text(agencyName);
                    }
                    if (agencyPhone) {
                        $("td[data-agency='agency_phone'] .text-value").text(agencyPhone);
                    }
                    if (agencyAddress) {
                        $("td[data-agency='agency_address'] .text-value").text(agencyAddress);
                    }
                    if (agencyBankAccount) {
                        $("td[data-agency='bank_account'] .text-value").text(agencyBankAccount);
                    }
                    if (agencyBank) {
                        $("td[data-agency='agency_bank'] .text-value").text(agencyBank);
                    }
                    if (agencyPayNumber) {
                        $("td[data-agency='agency_paynumber'] .text-value").text(agencyPayNumber);
                    }
                    if (agencyBranch) {
                        $("td[data-agency='agency_branch'] .text-value").text(agencyBranch);
                    }
                    if (agencyCccd) {
                        $("td[data-agency='agency_cccd'] .text-value").text(agencyCccd);
                    }
                    if (agencyReleaseDate) {
                        // Format ngày từ YYYY-MM-DD sang dd/mm/YYYY
                        let formattedReleaseDate = agencyReleaseDate;
                        if (agencyReleaseDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                            let parts = agencyReleaseDate.split('-');
                            formattedReleaseDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                        $("td[data-agency='agency_release_date'] .text-value").text(formattedReleaseDate);
                    }
                    
                    // Cập nhật địa chỉ lắp đặt
                    // TRÁNH nhân đôi địa chỉ: dùng trực tiếp installationAddress
                    if (installationAddress) {
                        let fullAddressText = installationAddress;
                        $("td[data-field='customer_address'] .text-value").text(fullAddressText);
                        $("#customer_address_full").val(fullAddressText);
                    }
                    
                    // Cập nhật tên sản phẩm
                    if (productName && $("#product_name").length) {
                        $("#product_name").val(productName);
                    }
                }
            }
            
            $(".installCostRow").show();
            $(".ctv_row").hide();
            $("#install_cost_row").hide();
            $("#install_file").hide();
            $("#table_collaborator").hide();
        } else {
            // Ẩn icon chỉnh sửa đại lý khi checkbox không được tích
            $(".agency-edit-icon").hide();
            
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
                    updateField("bank_account", res.bank_account || res.chutaikhoan || '');
                    updateBankLogoForCell($("#bank_account"));
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
            // Kiểm tra chi phí lắp đặt
            let installCost = parseInt(getCurrencyValue( $('#install_cost_agency') ), 10);
            if (installCost <= 0) {
                return false;
            }
            
            // Kiểm tra thông tin đại lý: cần ít nhất tên hoặc số điện thoại
            let agencyName = $("td[data-agency='agency_name'] .text-value").text().trim();
            let agencyPhone = $("td[data-agency='agency_phone'] .text-value").text().trim();
            
            if (!agencyName && !agencyPhone) {
                Swal.fire({
                    icon: 'error',
                    title: 'Thiếu thông tin đại lý',
                    text: 'Vui lòng nhập tên đại lý hoặc số điện thoại đại lý.',
                    timer: 3000,
                    showConfirmButton: false
                });
                return false;
            }
            
            return true;
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

    // Function để cập nhật UI trạng thái sau khi cập nhật thành công
    function updateStatusUI(action) {
        let newStatus = null;
        let statusText = '';
        let statusClass = '';
        
        // Lấy status hiện tại từ badge
        const $statusBadge = $('.d-flex.justify-content-between span:last-child');
        let currentStatusText = $statusBadge.length ? $statusBadge.text().trim() : '';
        let currentStatus = null;
        
        // Xác định status hiện tại dựa trên text
        if (currentStatusText === 'Đã Thanh Toán') {
            currentStatus = 3;
        } else if (currentStatusText === 'Đã Hoàn Thành') {
            currentStatus = 2;
        } else if (currentStatusText === 'Đã Điều Phối') {
            currentStatus = 1;
        } else {
            currentStatus = 0;
        }
        
        // Xác định trạng thái mới dựa trên action
        if (action === 'complete') {
            newStatus = 2;
            statusText = 'Đã Hoàn Thành';
            statusClass = 'bg-success fw-bold p-1 rounded-2';
        } else if (action === 'payment') {
            newStatus = 3;
            statusText = 'Đã Thanh Toán';
            statusClass = 'bg-info fw-bold p-1 rounded-2';
        } else if (action === 'update') {
            if (currentStatus === 3) {
                $('#btnUpdate').show();
                $('#btnComplete').hide();
                $('#btnPay').hide();
                return;
            }
            newStatus = 1;
            statusText = 'Đã Điều Phối';
            statusClass = 'bg-warning fw-bold p-1 rounded-2';
        }
        
        if (newStatus !== null) {
            // Cập nhật badge trạng thái
            if ($statusBadge.length) {
                $statusBadge.removeClass('bg-warning bg-success bg-info bg-secondary')
                           .addClass(statusClass)
                           .text(statusText);
            }
            
            // Cập nhật button visibility
            if (newStatus === 2) {
                // Đã hoàn thành: ẩn btnUpdate, hiện btnComplete và btnPay
                $('#btnUpdate').hide();
                $('#btnComplete').show();
                $('#btnPay').show();
            } else if (newStatus === 3) {
                // Đã thanh toán: chỉ hiện btnUpdate
                $('#btnUpdate').show();
                $('#btnComplete').hide();
                $('#btnPay').hide();
            } else if (newStatus === 1) {
                // Đã điều phối: hiện tất cả button
                $('#btnUpdate').show();
                $('#btnComplete').show();
                $('#btnPay').show();
            }
        }
    }

    function UpdateCollaborator() {
        let id = $("#ctv_id").val();
        
        // Kiểm tra nếu không có id hợp lệ, không gửi request
        if (!id || id === '' || id === null || id === undefined) {
            console.log('UpdateCollaborator: Không có ctv_id, bỏ qua cập nhật');
            return $.Deferred().resolve().promise();
        }
        
        let orderCode = "{{ $code }}"; // Lấy order_code từ PHP
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            id: parseInt(id), // Đảm bảo id là số nguyên
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

        // Ensure bank_account is sent when the UI field is 'chutaikhoan'
        if (!data.bank_account && data.chutaikhoan) {
            data.bank_account = data.chutaikhoan;
        }

        return $.ajax({
            url: "{{ route('ctv.update') }}",
            method: "POST",
            data: data,
            success: function(response) {
                if (response && response.success) {
                    const collab = response.data || {};
                    const map = {
                        nganhang: collab.bank_name || '',
                        sotaikhoan: collab.sotaikhoan || '',
                        chutaikhoan: collab.bank_account || collab.chutaikhoan || '',
                        bank_account: collab.bank_account || collab.chutaikhoan || '',
                        chinhanh: collab.chinhanh || '',
                        cccd: collab.cccd || '',
                        ngaycap: collab.ngaycap || ''
                    };

                    Object.keys(map).forEach(function(field) {
                        const $td = $('td[data-field="' + field + '"]');
                        if ($td.length) {
                            let val = map[field];
                            if (field === 'ngaycap' && val) {
                                const parts = String(val).split('-');
                                if (parts.length === 3) val = parts[2] + '/' + parts[1] + '/' + parts[0];
                            }
                            $td.find('.text-value').text(val);
                            if ($td.find('input').length) {
                                $td.find('input').val(val);
                            }
                        }
                    });

                    // Update top-level collaborator metadata if present
                    if (collab.id) $('#ctv_id').val(collab.id);
                    if (collab.full_name) $('#ctv_name').text(collab.full_name);
                    if (collab.phone) $('#ctv_phone').text(collab.phone);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Có lỗi xảy ra khi cập nhật thông tin cộng tác viên';
                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        const errors = Object.values(response.errors).flat();
                        errorMessage = errors.length > 0 ? errors[0] : (response.message || errorMessage);
                    } else if (response && response.message) {
                        errorMessage = response.message;
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: errorMessage,
                        confirmButtonText: 'Đóng'
                    });
                } else {
                    alert(errorMessage);
                }
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
            return $.Deferred().resolve().promise();
        }

        return $.ajax({
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
                    @php
                    // Ưu tiên ID từ installationOrder, sau đó order, cuối cùng data gốc
                    $modelId = $installationOrder->id ?? $order->id ?? $data->order->id ?? $data->id ?? null;
                    @endphp
                    formData.append("id", "{{ $modelId }}");
                    formData.append("action", action);
                    formData.append("type", type);
                    formData.append("product", $('#product_name').val());
                    

                    if (isInstallAgency === 1) {
                        // Case ĐẠI LÝ LẮP ĐẶT:
                        // - KHÔNG dùng collaborator_id = 1 làm flag nữa
                        // - Gửi rỗng để backend hiểu là không có CTV
                        formData.append("ctv_id", "");
                        formData.append("successed_at", $("#successed_at").val().trim());
                        formData.append("installcost", getCurrencyValue( $('#install_cost_agency') ));
                        
                        // Gửi kèm thông tin đại lý đang hiển thị để backend lưu vào installation_orders và agency
                        let agencyName  = $("td[data-agency='agency_name'] .text-value").text().trim();
                        let agencyPhone = $("td[data-agency='agency_phone'] .text-value").text().trim();
                        let agencyAddress = $("td[data-agency='agency_address'] .text-value").text().trim();
                        let agencyBank = $("td[data-agency='agency_bank'] .text-value").text().trim();
                        let bankAccount = $("td[data-agency='bank_account'] .text-value").text().trim();
                        let agencyPaynumber = $("td[data-agency='agency_paynumber'] .text-value").text().trim();
                        let agencyBranch = $("td[data-agency='agency_branch'] .text-value").text().trim();
                        let agencyCccd = $("td[data-agency='agency_cccd'] .text-value").text().trim();
                        let agencyReleaseDate = $("td[data-agency='agency_release_date'] .text-value").text().trim();
                        
                        // Chuyển đổi ngày từ d/m/Y sang Y-m-d nếu có
                        if (agencyReleaseDate && agencyReleaseDate.includes('/')) {
                            let parts = agencyReleaseDate.split('/');
                            if (parts.length === 3) {
                                let day = parts[0].padStart(2, '0');
                                let month = parts[1].padStart(2, '0');
                                let year = parts[2];
                                agencyReleaseDate = year + '-' + month + '-' + day;
                            }
                        }
                        
                        if (agencyName) {
                            formData.append("agency_name", agencyName);
                        }
                        if (agencyPhone) {
                            formData.append("agency_phone", agencyPhone);
                        }
                        if (agencyAddress) {
                            formData.append("agency_address", agencyAddress);
                        }
                        if (agencyBank) {
                            formData.append("agency_bank", agencyBank);
                        }
                        if (bankAccount) {
                            formData.append("bank_account", bankAccount);
                        }
                        if (agencyPaynumber) {
                            formData.append("agency_paynumber", agencyPaynumber);
                        }
                        if (agencyBranch) {
                            formData.append("agency_branch", agencyBranch);
                        }
                        if (agencyCccd) {
                            formData.append("agency_cccd", agencyCccd);
                        }
                        if (agencyReleaseDate) {
                            formData.append("agency_release_date", agencyReleaseDate);
                        }
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
                                // Cập nhật trạng thái button và badge ngay lập tức
                                updateStatusUI(action);
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    const ctvId = $("#ctv_id").val();
                                    const collabReq = (ctvId && ctvId !== '' && ctvId !== null) 
                                        ? UpdateCollaborator() 
                                        : $.Deferred().resolve().promise();
                                    const agencyReq = hasAgencyChanges() ? UpdateAgency() : $.Deferred().resolve().promise();

                                    $.when(collabReq, agencyReq).done(function() {
                                    });
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

        // ================== CHỈNH SỬA KHU VỰC LẮP ĐẶT (TỈNH/HUYỆN/XÃ) - INLINE TỪNG DÒNG ==================
        const orderCodeRegion = "{{ $code ?? $orderCode ?? '' }}";

        function saveRegionField(field, value, text) {
            if (!orderCodeRegion || !field) return;

            let payload = {
                _token: '{{ csrf_token() }}',
                order_code: orderCodeRegion,
                product: $("#product_name").val() || ''
            };

            if (field === 'province') {
                payload.province_id = value;
            } else if (field === 'district') {
                payload.district_id = value;
            } else if (field === 'ward') {
                payload.ward_id = value;
            }

            $.ajax({
                url: "{{ route('dieuphoi.update.address') }}",
                type: 'POST',
                data: payload,
                success: function(res) {
                    if (res.success) {
                        if (field === 'province') {
                            $('#current_province_id').val(value);
                            $('#region_province_text').text(text);
                        } else if (field === 'district') {
                            $('#current_district_id').val(value);
                            $('#region_district_text').text(text);
                        } else if (field === 'ward') {
                            $('#current_ward_id').val(value);
                            $('#region_ward_text').text(text);
                        }
                    }
                }
            });
        }

        // Bấm từng bút chì riêng cho Tỉnh/Huyện/Xã -> tạo select ngay trong ô đó
        $(document).on('click', '.region-edit-btn', function() {
            let field = $(this).data('field'); // province | district | ward
            let $icon = $(this);
            let $td = $icon.closest('td');
            let $span = $td.find('.text-value');

            // Nếu đang có select rồi thì không tạo thêm
            if ($td.find('select.region-inline-select').length) {
                return;
            }

            let $select = $('<select class="form-control region-inline-select"></select>');

            if (field === 'province') {
                // Dùng lại options từ bộ lọc province ở dưới
                $('#province option').each(function() {
                    let val = $(this).val();
                    let text = $(this).text();
                    if (val === '') return; // bỏ option trống
                    $select.append('<option value="' + val + '">' + text + '</option>');
                });
                let currentProvinceId = $('#current_province_id').val();
                if (currentProvinceId) {
                    $select.val(currentProvinceId);
                }
            } else if (field === 'district') {
                let provinceId = $('#current_province_id').val();
                if (!provinceId) {
                    alert('Vui lòng chọn Tỉnh/TP trước.');
                    return;
                }
                let url = '{{ route("ctv.getdistrict", ":province_id") }}'.replace(':province_id', provinceId);
                let currentDistrictId = $('#current_district_id').val();
                $.get(url, function(data) {
                    data.forEach(function(item) {
                        let opt = $('<option>')
                            .attr('value', item.district_id)
                            .text(item.name);
                        $select.append(opt);
                    });
                    if (currentDistrictId) {
                        $select.val(currentDistrictId);
                    }
                });
            } else if (field === 'ward') {
                let districtId = $('#current_district_id').val();
                if (!districtId) {
                    alert('Vui lòng chọn Quận/Huyện trước.');
                    return;
                }
                let url = '{{ route("ctv.getward", ":district_id") }}'.replace(':district_id', districtId);
                let currentWardId = $('#current_ward_id').val();
                $.get(url, function(data) {
                    data.forEach(function(item) {
                        let opt = $('<option>')
                            .attr('value', item.wards_id)
                            .text(item.name);
                        $select.append(opt);
                    });
                    if (currentWardId) {
                        $select.val(currentWardId);
                    }
                });
            }

            $select.on('change', function() {
                let val = $(this).val();
                let text = $(this).find('option:selected').text().trim();
                if (!val) return;
                saveRegionField(field, val, text);
            });

            // Khi blur thì bỏ select, hiện lại text + icon
            $select.on('blur', function() {
                $(this).remove();
                $span.show();
                $icon.show();
            });

            $span.hide();
            $icon.hide();
            $td.append($select);
            $select.focus();
        });
        // ================== KẾT THÚC CHỈNH SỬA KHU VỰC LẮP ĐẶT INLINE ==================
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
            
            // Địa chỉ: chỉ dùng giá trị address trong installation_orders, không ghép khu vực
            let oldDisplayValue = $("#customer_address_full").val() || oldValue;

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
                
                // Lưu thông tin khách hàng vào bảng installation_orders (KHÔNG sửa bảng orders)
                if (['customer_address','customer_name','customer_phone'].includes(fieldName)) {
                    let orderCode = "{{ $code }}";
                    if (orderCode) {
                        // Chuẩn bị payload theo từng trường
                        let payload = {
                            _token: $('meta[name="csrf-token"]').attr("content"),
                            order_code: orderCode,
                            product: $("#product_name").val() || ''
                        };
                        if (fieldName === 'customer_address') {
                            payload.address = newValue;
                        } else if (fieldName === 'customer_name') {
                            payload.full_name = newValue;
                        } else if (fieldName === 'customer_phone') {
                            payload.phone_number = newValue;
                        }

                        // console.log('Sending customer update', fieldName, payload);

                        $.ajax({
                            url: "{{ route('dieuphoi.update.address') }}",
                            method: "POST",
                            data: payload,
                            success: function(response) {
                                // console.log('Customer update response', fieldName, response);
                                if (response.success) {
                                    if (fieldName === 'customer_address') {
                                        // Chỉ hiển thị lại đúng trường address trong installation_orders
                                        $span.text(newValue).show();
                                        $("#customer_address_full").val(newValue);
                                        $("#customer_address_detail").val(newValue);
                                    } else {
                                        // Tên và SĐT: chỉ cần hiển thị giá trị mới
                                        $span.text(newValue).show();
                                    }
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Lỗi',
                                        text: response.message || 'Không thể cập nhật thông tin khách hàng',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    // Quay về giá trị cũ nếu lưu thất bại
                                    let displayValue = (fieldName === 'customer_address') ? oldDisplayValue : oldValue;
                                    $span.text(displayValue).show();
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: 'Có lỗi xảy ra khi cập nhật thông tin khách hàng',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Quay về giá trị cũ nếu lưu thất bại
                                let displayValue = (fieldName === 'customer_address') ? oldDisplayValue : oldValue;
                                $span.text(displayValue).show();
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

    

    // Hàm kiểm tra xem có thay đổi thông tin đại lý không (không thay đổi)
    function hasAgencyChanges() {
        // Lấy giá trị hiện tại của các trường đại lý
        let currentAgencyData = {
            agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
            agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
            agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
            agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
            agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
            bank_account: $("td[data-agency='bank_account'] .text-value").text().trim(),
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
