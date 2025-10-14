@extends('layout.layout')

@section('content')
<div class="container-fluid mt-2">
    <div class="row g-4">
        <div class="col-12 col-md-6">
            <div class="card h-100">
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
                                    <td colspan="3">{{ $data->order->customer_address ?? $data->address}}, {{ $fullAddress }}</td>
                                </tr>
                                <tr class="ctv_row">
                                    <th>CTV lắp đặt:</th>
                                    <td id="ctv_name">{{ $data->order->collaborator->full_name ?? $data->collaborator->full_name ?? '' }}</td>
                                    <input type="hidden" id="ctv_id" name="ctv_id" value="{{ $data->order->collaborator_id ?? $data->collaborator_id }}">
                                    <th>SĐT CTV:</th>
                                    <td id="ctv_phone">{{ $data->order->collaborator->phone ?? $data->collaborator->phone ?? '' }}</td>
                                </tr>
                                <tr class="ctv_row">
                                    <th>Số tài khoản:</th>
                                    <td id="sotaikhoan" data-field="sotaikhoan">
                                        <span class="text-value">{{ $data->order->collaborator->sotaikhoan ?? $data->collaborator->sotaikhoan ?? ''}}</span>
                                        @if (empty(optional(optional($data->order)->collaborator)->sotaikhoan ?? optional($data->collaborator)->sotaikhoan))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                    <th>Chi nhánh:</th>
                                    <td id="chinhanh" data-field="chinhanh">
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
                                        @if (empty($agency->address) && !empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số tài khoản:</th>
                                    <td data-agency="agency_paynumber">
                                        <span class="text-value">{{ $agency->sotaikhoan ?? '' }}</span>
                                        @if (empty($agency->sotaikhoan) && !empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Chi nhánh:</th>
                                    <td data-agency="agency_branch">
                                        <span class="text-value">{{ $agency->chinhanh ?? '' }}</span>
                                        @if (empty($agency->chinhanh) && !empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Căn cước công dân:</th>
                                    <td data-agency="agency_cccd">
                                        <span class="text-value">{{ $agency->cccd ?? '' }}</span>
                                        @if (empty($agency->cccd) && !empty($data->order->agency_phone ?? $data->agency_phone))
                                        <i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ngày cấp:</th>
                                    <td data-agency="agency_release_date">
                                        <span class="text-value">{{ optional($agency)->ngaycap ? \Carbon\Carbon::parse($agency->ngaycap)->format('d/m/Y') : '' }}</span>
                                        @if (empty($agency->ngaycap) && !empty($data->order->agency_phone ?? $data->agency_phone))
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
<div class="container-fluid mt-2 d-flex justify-content-end">
    <button id="btnUpdate" class="mt-2 btn btn-outline-primary fw-bold" data-action="update">Cập nhật</button>
    <button id="btnComplete" class="mt-2 ms-1 btn btn-outline-success fw-bold" data-action="complete">Hoàn thành</button>
    <button id="btnPay" class="mt-2 ms-1 btn btn-outline-info fw-bold" data-action="payment">Đã thanh toán</button>
</div>
<script>
    $(document).ready(function() {
        $("#isInstallAgency").on("change", function() {
            if ($(this).is(":checked")) {
                $(".installCostRow").show();
                $(".ctv_row").hide();
                $("#install_cost_row").hide();
                $("#install_file").hide();
                $("#table_collaborator").hide();
            } else {
                $(".installCostRow").hide();
                $(".error").hide();
                $("#table_collaborator").show();
                $(".ctv_row").show();
                $("#install_cost_row").show();
                $("#install_file").show();

            }
        });

        if ($("#isInstallAgency").is(":checked")) {
            $(".installCostRow").show();
            $(".ctv_row").hide();
            $("#install_cost_row").hide();
            $("#install_file").hide();
            $("#table_collaborator").hide();
        } else {
            $(".installCostRow").hide();
            $(".error").hide();
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
            let html = `<span class="text-value">${value ?? ''}</span>`;
            if (!value) {
                html += `<i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>`;
            }
            td.html(html);
        }

        $(".install_cost").on("input", function() {
            let value = $(this).val().trim();
            let errorBox = $(".error");
            let isValid = /^[0-9]+$/.test(value);

            if (!isValid) {
                errorBox.text("Vui lòng nhập số nguyên dương, không chứa dấu , hoặc .").show();
            } else if (parseInt(value) < 0) {
                errorBox.text("Chi phí lắp đặt không được âm.").show();
            } else {
                errorBox.hide();
            }
        });

        Update();
    });

    function validate() {
        if ($("#isInstallAgency").is(":checked")) {
            return $("#install_cost_agency").val().trim() > 0;
        } else {
            return $("#ctv_id").val() !== '' && $("#install_cost_ctv").val().trim() > 0;
        }
    }

    function UpdateCollaborator() {
        let id = $("#ctv_id").val();
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            id: id
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
            data[field] = value;
        });

        $.post("{{ route('ctv.update') }}", data);
    }

    function UpdateAgency() {
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
        };
        $("td[data-agency]").each(function() {
            let $td = $(this);
            let agency = $td.data("agency");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
            }
            data[agency] = value;
        });
        $.post("{{ route('agency.update') }}", data);
    }

    function Update() {
        $("#btnUpdate, #btnComplete, #btnPay").on("click", function(e) {
            e.preventDefault();
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
                    if (!validate()) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi điền thông tin',
                            timer: 1500,
                            showConfirmButton: false
                        })
                        return;
                    }

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
                        formData.append("installcost", $("#install_cost_agency").val().trim());
                    } else {
                        formData.append("ctv_id", $("#ctv_id").val());
                        formData.append("successed_at", $("#successed_at_ctv").val().trim());
                        formData.append("installcost", $("#install_cost_ctv").val().trim());
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
                                    UpdateAgency();
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
                            debugger;
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

    $(document).on("click", ".edit-icon", function() {
        let $td = $(this).closest("td");
        let $span = $td.find(".text-value");
        let oldValue = $span.text().trim();

        let field = $td.data("field");
        let agency = $td.data("agency");

        let $input = $("<input>", {
            type: "text",
            value: oldValue,
            class: "form-control d-inline-block w-auto"
        });

        if (field === "ngaycap" || agency === "agency_release_date") {
            $input.attr("type", "date");
        }

        // Xử lý khi blur (rời input)
        // $input.on("blur", function() {
        //     let newValue = $(this).val().trim() || oldValue;

        //     if (field === "ngaycap" || agency === "agency_release_date") {
        //         let regex = /^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/\d{4}$/;
        //         if (!regex.test(newValue)) {
        //             alert("Vui lòng nhập đúng định dạng dd/mm/yyyy");
        //             return;
        //         }
        //     }

        //     $span.text(newValue).show();
        //     $td.find(".edit-icon").show();
        //     $(this).remove();
        // });

        // Xử lý nhấn Enter
        $input.on("keypress", function(e) {
            if (e.which === 13) $(this).blur();
        });

        // Ẩn span và icon, hiển thị input
        $span.hide();
        $(this).hide();
        $td.prepend($input);
        $input.focus();
    });
</script>
@endsection