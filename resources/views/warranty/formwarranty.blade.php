@extends('layout.layout')

@section('content')
    <div class="container mt-4">
        <div class="card h-100 pb-3">
            <div class="card-header bg-primary text-white position-relative">
                <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" onclick="window.history.back()" title="Quay lại"
                    style="height: 15px; filter: brightness(0) invert(1); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                <h5 class="mb-0 text-center">Phiếu bảo hành</h5>
            </div>
            <div class="card-body">
                <div id="warrantyCard">
                    <div class="form-group">
                        <label for="product" class="form-label mt-1">Tên sản phẩm. (<span
                                style="color: red;">*</span>)</label>
                        @if(!empty($lstproduct) && count($lstproduct) > 0)
                            <select id="product" name="product" class="form-control mb-3" >
                                <option value="" disabled selected>Chọn sản phẩm</option>
                                @foreach($lstproduct as $product)
                                    <option value="{{ $product->product_name }}" data-serial="{{ $product->warranty_code }}">
                                        {{ $product->product_name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div style="position: relative;">
                                <input type="text" id="product" name="product" class="form-control" 
                                    placeholder="Nhập tên sản phẩm" required>
                                <div id="product-suggestions" class="list-group position-absolute w-100 d-none"></div>
                            </div>
                        @endif
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group mt-3 mb-2">
                        <input class="form-check-input" type="checkbox" id="chkseri" name="chkseri" value="HÀNG KHÔNG CÓ MÃ SERI">
                        <label class="form-check-label" for="chkseri"> HÀNG KHÔNG CÓ MÃ SERI </label>
                    </div>
                    <div class="form-group" id="serialGroup">
                        <label for="serial_number" class="form-label mt-1">Mã seri tem bảo hành (<span 
                                style="color: red;">*</span>)</label>
                        <input id="serial_number" name="serial" type="text" class="form-control"
                            placeholder="Nhập mã seri tem bảo hành" style="text-transform: uppercase;" required>
                        <div class="error text-danger small mt-1"></div>
                        <label class="form-label mt-1 d-none" id="text_title"></label>
                    </div>
                    <div class="form-group d-none" id="serialthanmayGroup">
                        <label for="serial_thanmay" class="form-label mt-1">Mã seri thân máy (để trống nếu chưa có)</label>
                        <input id="serial_thanmay" name="serial_thanmay" type="text" class="form-control" placeholder="Nhập mã seri thân máy" style="text-transform: uppercase;">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="type" class="form-label mt-1">Hình thức tiếp nhận bảo hành. (<span
                                style="color: red;">*</span>)</label>
                        <select id="type" name="type" class="form-control " required>
                            <option value="" disabled selected>Chọn hình thức tiếp nhận bảo hành</option>
                            <option value="branch">Khách đến trực tiếp tại chi nhánh bảo hành</option>
                            <option value="remote">Khách gửi sản phẩm đến TT bảo hành</option>
                            <option value="customer_home">Tiếp nhận bảo hành tại nhà khách hàng</option>
                            <option value="agent_home">Giao CTV bảo hành tại nhà khách hàng</option>
                            <option value="agent_component">Gửi phụ kiện cho cộng tác viên</option>
                        </select>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="full_name" class="form-label mt-1">Họ tên khách hàng. (<span
                                style="color: red;">*</span>)</label>
                        <input id="full_name" name="full_name" type="text" class="form-control"
                            placeholder="Nhập họ tên khách hàng" required
                            value="{{ $warranty->order_product->order->customer_name ?? $warranty->full_name ?? '' }}">
                            <!--value="{{ $warranty->order_product->order->customer_name ?? '' }}">-->
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="phone_number" class="form-label mt-1">Số điện thoại. (<span
                                style="color: red;">*</span>)</label>
                        <input id="phone_number" name="phone_number" type="text" class="form-control"
                            placeholder="Nhập số điện thoại" required
                            value="{{ $warranty->order_product->order->customer_phone ?? $warranty->phone_number ?? '' }}">
                            <!--value="{{ $warranty->order_product->order->customer_phone ?? '' }}">-->
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group d-none addressprovince">
                        <label for="address" class="form-label mt-1">Địa chỉ khách hàng (<span style="color: red;">*</span>)</label>
                        <div class="row">
                            <div class="col-md-4 col-12 mb-2">
                                <select class="form-control" id="province" name="province">
                                    <option value="" {{ request('province') == '' ? 'selected' : '' }}>-- Chọn Tỉnh --</option>
                                    @foreach($provinces as $province)
                                    <option value="{{ $province->province_id }}">{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-12 mb-2">
                                <select class="form-control" id="district" name="district">
                                    <option value="">-- Chọn Huyện --</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-12 mb-2">
                                <select class="form-control" id="ward" name="ward">
                                    <option value="">-- Chọn Xã --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address" class="form-label mt-1">Địa chỉ (<span style="color: red;">*</span>)</label>
                        <input id="address" name="address" type="text" class="form-control" placeholder="Nhập địa chỉ" required
                             value="{{ $warranty->order_product->order->customer_address ?? $warranty->address ?? '' }}">
                             <!--value="{{ $warranty->order_product->order->customer_address ?? '' }}">-->
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="staff_received" class="form-label mt-1">Nhân viên tiếp nhận.</label>
                        <input id="staff_received" name="staff_received" type="text" class="form-control mb-3"
                            value="{{ session('user') }}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="received_date" class="form-label mt-1">Ngày tiếp nhận.</label>
                        <input id="received_date" name="received_date" type="text" class="form-control mb-3" maxlength="10"
                            disabled placeholder="dd/mm/yyyy" value="{{ \Carbon\Carbon::today()->format('d/m/Y') }}">
                    </div>
                    <div class="form-group">
                        <label for="branch" class="form-label mt-1">Chi nhánh.</label>
                        <input id="branch" name="branch" type="text" class="form-control mb-3" value="{{ $chinhanh }}"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="shipment_date" class="form-label mt-1">Ngày xuất kho (Nhập bất kỳ ngày nào trong quá khứ
                            nếu sản phẩm đã hết hạn bảo hành nhưng không tìm được ngày xuất kho). (<span
                                style="color: red;">*</span>)</label>
                        <input id="shipment_date" name="shipment_date" type="text" class="form-control date-input" placeholder="dd/mm/yyyy" maxlength="10" required
                            value="{{ !empty($warranty->created_at) ? \Carbon\Carbon::parse($warranty->created_at)->format('d/m/Y') 
                                    : (!empty($warranty->shipment_date) ? \Carbon\Carbon::parse($warranty->shipment_date)->format('d/m/Y'): '') }}">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="return_date" class="form-label mt-1">Ngày hẹn trả. (<span
                                style="color: red;">*</span>)</label>
                        <input id="return_date" name="return_date" type="text" class="form-control date-input"
                            placeholder="dd/mm/yyyy" maxlength="10" required>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group description_error">
                        <label for="initial_fault_condition" class="form-label mt-1">Tình trạng lỗi ban đầu (nếu có từ phản
                            ánh của KH). (<span style="color: red;">*</span>)</label>
                        <textarea id="initial_fault_condition" name="initial_fault_condition" class="form-control" rows="2" required
                             maxlength="1024"></textarea>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group description_error">
                        <label for="product_fault_condition" class="form-label mt-1">Mô tả ngoại quan sản phẩm khi tiếp nhận
                            (VD: vỏ móp méo, gãy đế tay cầm ....) * (<span style="color: red;">*</span>)</label>
                        <textarea class="form-control" id="product_fault_condition" name="product_fault_condition" rows="2" required
                             maxlength="1024"></textarea>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group description_error">
                        <label for="product_quantity_description" class="form-label mt-1">Điền số lượng tên sản phẩm khi
                            nhận bàn giao (VD: 1 robot 2909; 1 đế sạc...). (<span style="color: red;">*</span>)</label>
                        <textarea class="form-control" id="product_quantity_description" name="product_quantity_description" required
                            rows="2"  maxlength="1024"></textarea>
                        <div class="error text-danger small mb-3"></div>
                    </div>
                    <div class="form-group ctv-fields">
                        <input type="number" id="collaborator_id" name="collaborator_id" value="" hidden>
                        <label for="ctv_phone" class="form-label mt-1">Số điện thoại CTV (<span
                                style="color: red;">*</span>)</label>
                        <input id="ctv_phone" name="ctv_phone" type="text" class="form-control"
                            placeholder="Nhập số điện thoại ctv"  value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group ctv-fields">
                        <label for="ctv_name" class="form-label mt-1">Họ tên CTV (<span
                                style="color: red;">*</span>)</label>
                        <input id="ctv_name" name="ctv_name" type="text" class="form-control" placeholder="Nhập họ tên ctv"
                             value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group ctv-fields">
                        <label for="ctv_address" class="form-label mt-1">Địa chỉ CTV (<span
                                style="color: red;">*</span>)</label>
                        <input id="ctv_address" name="ctv_address" type="text" class="form-control"
                            placeholder="Nhập địa chỉ ctv" value="">
                        <div class="error text-danger small mt-1 mb-3"></div>
                    </div>
                    <button id="hoantat" class="btn btn-primary w-100">Hoàn tất</button>
                </div>
            </div>
        </div>
    </div>
    <style>
        #product-suggestions {
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            background-color: #fff;
            border: 1px solid #ced4da;
        }
    </style>
    <script>
        $(document).ready(function () {
            SelectProduct();
            ClickCheckBox();
            ValidateInputDate();
            ShowCTVFiles();
            
            $('#province').on('change', function() {
                let provinceId = $(this).val();
                $('#district').empty().append('<option value="" selected>-- Chọn Huyện --</option>');
                $('#ward').empty().append('<option value="" selected>-- Chọn Xã --</option>');
                let url = '{{ route("ctv.getdistrict", ":province_id") }}'.replace(':province_id', provinceId);
                if (provinceId) {
                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function(data) {
                            let $district = $('#district');
                            $district.empty();
                            $district.append('<option value="" disabled selected>-- Chọn Huyện --</option>');
                            data.forEach(function(item) {
                                $district.append('<option value="' + item.district_id + '">' + item.name + '</option>');
                            });
                        },
                    });
                }
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
                            $ward.append('<option value="" disabled selected>-- Chọn Xã --</option>');
                            data.forEach(function(item) {
                                $ward.append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                            });
                        },
                    });
                }
            });

            // Gắn sự kiện submit form
            $('#hoantat').on('click', function (e) {
                e.preventDefault();
                if (validateAllFields()) {
                    createWarrantyRequest();
                }
            });
        });

        function ClickCheckBox(){
            $('#chkseri').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#serialGroup').hide();
                    $('#serial_number').val('HÀNG KHÔNG CÓ MÃ SERI');
                } else {
                    $('#serialGroup').show();
                    $('#serial_number').val('');
                }
            });
        }

        function SelectProduct(){
            const productList = {!! json_encode($lstproduct) !!};
            if (Array.isArray(productList) && productList.length === 1) {
                const $select = $('#product');
                $select.find('option:eq(1)').prop('selected', true).trigger('change');
                $('#serial_number').val(productList[0].warranty_code);
            }
        }

        function createWarrantyRequest() {
            let formData = {
                product: $('#product').val(),
                serial_number: $('#serial_number').val(),
                serial_thanmay: $('#serial_thanmay').val(),
                type: $('#type').val(),
                full_name: $('#full_name').val(),
                phone_number: $('#phone_number').val(),
                province_id: $('#province').val(),
                district_id: $('#district').val(),
                ward_id: $('#ward').val(),
                address: $('#address').val(),
                branch: $('#branch').val(),
                shipment_date: $('#shipment_date').val(),
                return_date: $('#return_date').val(),
                collaborator_id: $('#collaborator_id').val(),
                collaborator_name: $('#ctv_name').val(),
                collaborator_phone: $('#ctv_phone').val(),
                collaborator_address: $('#ctv_address').val(),
                initial_fault_condition: $('#initial_fault_condition').val(),
                product_fault_condition: $('#product_fault_condition').val(),
                product_quantity_description: $('#product_quantity_description').val()
            };
            OpenWaitBox();
            $.ajax({
                url: "{{ route('warranty.createwarranty') }}",
                type: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    CloseWaitBox();
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tạo phiếu thành công!',
                            timer: 2000,
                        }).then(() => {
                            window.location.href = "{{ route('warranty.takephoto') }}?sophieu=" + res.id;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: res.message,
                            timer: 2000,
                        });
                    }
                },
                error: function (xhr) {
                    CloseWaitBox();
                    let msg = "Đã xảy ra lỗi!";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: msg
                    });
                }
            });
        }

        function ValidateInputDate() {
            $('.date-input').on('input', function () {
                let val = $(this).val();

                // Chỉ giữ lại số và dấu "/"
                val = val.replace(/[^\d\/]/g, '');

                // Tự động thêm dấu '/' sau ngày
                if (val.length > 2 && val[2] !== '/') {
                    val = val.slice(0, 2) + '/' + val.slice(2);
                }

                // Tự động thêm dấu '/' sau tháng
                if (val.length > 5 && val[5] !== '/') {
                    val = val.slice(0, 5) + '/' + val.slice(5);
                }

                // Giới hạn độ dài 10 ký tự
                if (val.length > 10) {
                    val = val.slice(0, 10);
                }

                $(this).val(val);
            });
        }

        function ShowCTVFiles() {
            let type = $('#type').val();
            if (type === 'agent_component') {
                $('.ctv-fields').show();
                $('.ctv-fields input').attr('required', true);
                $('.description_error').hide();
                $('.description_error textarea').val('');
                $('.description_error textarea').removeAttr('required');
            }
            else{
                $('.ctv-fields').hide();
                $('.ctv-fields input').removeAttr('required');
                $('.ctv-fields input').val('');
                $('.description_error').show();
                $('.description_error').attr('required', true);
            }
            // Lắng nghe sự kiện thay đổi của select
            $('#type').on('change', function() {
                let selected = $(this).val();
                if (selected === 'agent_component') {
                    $('.ctv-fields').show();
                    $('.ctv-fields input').attr('required', true);
                    $('.description_error').hide();
                    $('.description_error textarea').val('');
                    $('.description_error textarea').removeAttr('required');
                    $('label[for="serial_number"]').html('Mã seri tem bảo hành');
                    $('.addressprovince').addClass('d-none');
                } 
                else if(selected === 'agent_home'){
                    $('.addressprovince').removeClass('d-none');
                    $('.ctv-fields').hide();
                    $('.ctv-fields input').removeAttr('required');
                    $('.ctv-fields input').val('');
                    $('#collaborator_id').val('');
                    $('.description_error').show();
                    $('.description_error').attr('required', true);
                    $('label[for="serial_number"]').html('Mã seri tem bảo hành (<span style="color: red;">*</span>)');
                }
                else {
                    $('.addressprovince').addClass('d-none');
                    $('.ctv-fields').hide();
                    $('.ctv-fields input').removeAttr('required');
                    $('.ctv-fields input').val('');
                    $('#collaborator_id').val('');
                    $('.description_error').show();
                    $('.description_error').attr('required', true);
                    $('label[for="serial_number"]').html('Mã seri tem bảo hành (<span style="color: red;">*</span>)');
                }
            });

            //sau khi nhập xong mã seri
            $('#serial_number').on('blur', function() {
                let serial = $(this).val();
                if (serial && serial!= '') {
                    OpenWaitBox();
                    $.ajax({
                        url: "{{ route('warranty.findold') }}",
                        method: 'POST',
                        data: {
                            serial: serial,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            CloseWaitBox();
                            if (!response.success) {
                                if(response.type == 0){
                                    $('#text_title').addClass('d-none');
                                }else{
                                    $('#text_title').removeClass('d-none');
                                    $('#text_title').removeClass('text-success');
                                    $('#text_title').addClass('text-danger');
                                    $('#text_title').html('Mã serial không đúng hoặc chưa được kích hoạt');
                                }
                            } 
                            else {
                                if(!response.tem){
                                    $('#text_title').addClass('d-none');
                                    $('#shipment_date').val('');
                                    $('#full_name').val('');
                                    $('#phone_number').val('');
                                    $('#address').val('');
                                    return;
                                }
                                const today = new Date();
                                const warrantyend = new Date(response.tem.han_bao_hanh);
                                let baohanh = '';
                                if (today <= warrantyend) {
                                    baohanh = 'Còn hạn bảo hành';
                                    $('#text_title').removeClass('text-danger');
                                    $('#text_title').addClass('text-success');
                                } else {
                                    baohanh = 'Hết hạn bảo hành';
                                    $('#text_title').removeClass('text-success');
                                    $('#text_title').addClass('text-danger');
                                }
                                const NgayNhap = response.tem.ngay_nhap_kho ? formatDateToDMY(response.tem.ngay_nhap_kho) : '';
                                const NgayKichHoat = response.tem.ngay_kich_hoat ? formatDateToDMY(response.tem.ngay_kich_hoat) : '';
                                $('#text_title').html(`Loại sản phẩm: ${response.tem.ten_san_pham}, ngày xuất kho: ${NgayNhap}, ngày kích hoạt: ${NgayKichHoat}, ${baohanh}`);
                                $('#text_title').removeClass('d-none');
                                $('#shipment_date').val(NgayNhap);
                                if (response.khach_hang) {
                                    $('#full_name').val(response.khach_hang.ho_ten ?? '');
                                    $('#phone_number').val(response.khach_hang.so_dien_thoai ?? '');
                                    $('#address').val(response.khach_hang.dia_chi ?? '');
                                }
                            }
                        },
                        error: function(xhr) {
                            CloseWaitBox();
                            console.error('Đã xảy ra lỗi:', xhr.responseText);
                        }
                    });
                }
            });
        
            function formatDateToDMY(dateString) {
                const date = new Date(dateString);
                if (isNaN(date)) return '';
        
                const day = ('0' + date.getDate()).slice(-2);
                const month = ('0' + (date.getMonth() + 1)).slice(-2);
                const year = date.getFullYear();    
        
                return `${day}/${month}/${year}`;
            }

            // sau khi nhập số điện thoại
            $('#ctv_phone').on('blur', function () {
                let phone = $(this).val();
                if(phone){
                    $.ajax({
                        url: "{{ route('getcollaborator') }}",
                        method: 'POST',
                        data: {
                            phone: phone,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            if(!response.success){
                                Swal.fire({
                                    icon: 'warning',
                                    title: response.message,
                                    timer: 1500,
                                });
                            }
                            else{
                                $('#collaborator_id').val(response.data.id ?? '');
                                $('#ctv_name').val(response.data.full_name ?? '');
                                $('#ctv_address').val(response.data.address ?? '');
                            }
                        },
                        error: function (xhr) {
                            console.error('Đã xảy ra lỗi:', xhr.responseText);
                        }
                    });
                }
            });
        }
    </script>
    <script>

        // Validation form tạo phiếu bảo hành
        let formErrors = {};

        // Hàm hiển thị lỗi
        function showError($field, message) {
            const fieldId = $field.attr('id');
            if (!fieldId) return;
            hideError($field);
            $field.closest('.form-group').find('.error').text(message);
            formErrors[fieldId] = true;
            updateSubmitButtonState();
        }

        // Hàm ẩn lỗi
        function hideError($field) {
            const fieldId = $field.attr('id');
            if (!fieldId) return;
            $field.closest('.form-group').find('.error').text('');
            delete formErrors[fieldId];
            updateSubmitButtonState();
        }

        // Cập nhật trạng thái nút Hoàn tất
        function updateSubmitButtonState() {
            const hasErrors = Object.keys(formErrors).length > 0;
            $('#hoantat').prop('disabled', hasErrors);
        }


        // Tên sản phẩm: chữ và số, max 80
        function validateProduct() {
            const $input = $('#product');
            const value = $input.val();
            if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ,.\-()/]+$/.test(value)) {
                showError($input, "Chỉ được nhập chữ, số và các ký tự .,-()/");
            } else if (value.length > 80) {
                showError($input, "Tối đa 80 ký tự.");
            } else {
                hideError($input);
            }
        }

        // Mã seri: chữ và số, max 25
        function validateSerialNumber() {
            const $input = $('#serial_number');
            const value = $input.val();
            if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
                showError($input, "Chỉ được nhập chữ và số.");
            } else if (value.length > 25) {
                showError($input, "Tối đa 25 ký tự.");
            } else {
                hideError($input);
            }
        }

        // Họ tên: chỉ chữ, max 50
        function validateFullName() {
            const $input = $('#full_name');
            const value = $input.val();
            if (value && !/^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ]+$/.test(value)) {
                showError($input, "Chỉ được nhập chữ.");
            } else if (value.length > 50) {
                showError($input, "Tối đa 50 ký tự.");
            } else {
                hideError($input);
            }
        }

        // Số điện thoại: đúng 10 số
        function validatePhoneNumber() {
            const $input = $('#phone_number');
            const value = $input.val();
            if (value && !/^\d{10}$/.test(value)) {
                showError($input, "Số điện thoại phải có đúng 10 chữ số.");
            } else {
                hideError($input);
            }
        }

        // Địa chỉ: chữ, số, .,- và max 150
        function validateAddress() {
            const $input = $('#address');
            const value = $input.val();
            if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ,.\-\/]+$/.test(value)) {
                showError($input, "Chỉ nhập chữ, số và các ký tự .,-/");
            } else if (value.length > 150) {
                showError($input, "Tối đa 150 ký tự.");
            } else {
                hideError($input);
            }
        }

        // Ngày: Ngày xuất kho < Ngày tiếp nhận, Ngày hẹn trả >= Ngày tiếp nhận
        function validateDates() {
            const $shipmentDate = $('#shipment_date');
            const $returnDate = $('#return_date');
            const receivedDateStr = $('#received_date').val();

            const receivedDate = parseDate(receivedDateStr);
            const shipmentDate = parseDate($shipmentDate.val());
            const returnDate = parseDate($returnDate.val());

            // Validate ngày xuất kho
            if (shipmentDate && receivedDate && shipmentDate >= receivedDate) {
                showError($shipmentDate, "Ngày xuất kho phải nhỏ hơn ngày tiếp nhận.");
            } else {
                hideError($shipmentDate);
            }

            // Validate ngày hẹn trả
            if (returnDate && receivedDate && returnDate < receivedDate) {
                showError($returnDate, "Ngày hẹn trả phải lớn hơn hoặc bằng ngày tiếp nhận.");
            } else {
                hideError($returnDate);
            }
        }

        // Hàm Mô tả sài chung
        function validateTextarea(selector) {
            const $input = $(selector);
            const value = $input.val();
            
            if (value && !/^[a-zA-Z0-9\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸỴ,.\-)(\/]+$/.test(value)) {
                showError($input, "Chỉ được nhập chữ, số và các ký tự .,-()/");
            } else if (value.length > 150) {
                showError($input, "Tối đa 150 ký tự.");
            } else {
                hideError($input);
            }
        }

        // Hàm kiểm tra các trường bắt buộc
        function validateRequiredFields() {
            let isValid = true;
            $('#warrantyCard [required]').each(function() {
                const $field = $(this);
                // Chỉ kiểm tra các trường đang hiển thị
                if ($field.is(':visible') && !$field.val()) {
                    showError($field, "Trường này là bắt buộc.");
                    isValid = false;
                } else if ($field.is(':visible') && $field.val()) {
                    // Xóa lỗi "bắt buộc" nếu đã điền, nhưng giữ lại các lỗi khác
                    if ($field.closest('.form-group').find('.error').text() === "Trường này là bắt buộc.") {
                        hideError($field);
                    }
                }
            });
            return isValid;
        }

        // Hàm kiểm tra tổng thể khi submit
        function validateAllFields() {
            validateRequiredFields();
            validateProduct();
            validateSerialNumber();
            validateFullName();
            validatePhoneNumber();
            validateAddress();
            validateDates();
            validateTextarea('#initial_fault_condition');
            validateTextarea('#product_fault_condition');
            validateTextarea('#product_quantity_description');

            // Nếu còn lỗi, focus vào trường lỗi đầu tiên
            if (Object.keys(formErrors).length > 0) {
                const firstErrorId = Object.keys(formErrors)[0];
                $('#' + firstErrorId).focus();
                return false;
            }
            return true;
        }

        // Helper functions
        function parseDate(dateStr) {
            if (!dateStr || !/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) return null;
            const [day, month, year] = dateStr.split('/');
            const date = new Date(`${year}-${month}-${day}`);
            date.setHours(0, 0, 0, 0);
            return date;
        }

        $(document).ready(function() {
            // Gắn sự kiện 'input' cho các trường text và textarea
            $('#product').on('input', validateProduct);
            $('#serial_number').on('input', validateSerialNumber);
            $('#full_name').on('input', validateFullName);
            $('#phone_number').on('input', validatePhoneNumber);
            $('#address').on('input', validateAddress);
            $('#initial_fault_condition').on('input', () => validateTextarea('#initial_fault_condition'));
            $('#product_fault_condition').on('input', () => validateTextarea('#product_fault_condition'));
            $('#product_quantity_description').on('input', () => validateTextarea('#product_quantity_description'));

            // Gắn sự kiện 'change' cho các trường date và select
            $('#shipment_date, #return_date, #received_date').on('change', validateDates);
            $('#type').on('change', function() {
                // Khi chọn, xóa lỗi và kiểm tra lại các trường bắt buộc
                hideError($(this));
                validateRequiredFields();
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            const productList = {!! json_encode($products) !!};
            $('#product').on('input', function () {
                const keyword = $(this).val().toLowerCase().trim();
                const $suggestionsBox = $('#product-suggestions');
                $suggestionsBox.empty();

                if (!keyword) {
                    $suggestionsBox.addClass('d-none');
                    return;
                }

                const matchedProducts = productList.filter(p =>
                    p.product_name.toLowerCase().includes(keyword)
                );

                if (matchedProducts.length > 0) {
                    matchedProducts.slice(0, 10).forEach(p => {
                        $suggestionsBox.append(
                            `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                        );
                    });
                    $suggestionsBox.removeClass('d-none');
                } else {
                    $suggestionsBox.addClass('d-none');
                }
            });

            // Khi người dùng chọn sản phẩm gợi ý
            $(document).on('mousedown', '#product-suggestions button', function () {
                $('#product').val($(this).text());
                if($(this).data('product-id') == 1){
                    $('#serialthanmayGroup').remove('d-none');
                }
                $('#serialthanmayGroup').addClass('d-none');
                $('#product-suggestions').addClass('d-none');
            });

            $('#product').on('blur', function () {
                const inputVal = $(this).val().trim().replace(/\r?\n|\r/g, '');
                const matchedProduct = productList.find(product =>
                    product.product_name.trim().replace(/\r?\n|\r/g, '') === inputVal
                );
                if (!matchedProduct && inputVal !== '') {
                    $('#product').val('');
                    Swal.fire({
                        icon: 'warning',
                        title: "Sản phẩm cũ không có trong hệ thống .Vui lòng liên hệ quản trị viên CNTT để được hỗ trợt.",
                        timer: 1500,
                    });
                }
                if(matchedProduct.check_seri == 1){
                    $('#serialthanmayGroup').removeClass('d-none');
                }
                else{ $('#serialthanmayGroup').addClass('d-none'); }
            });

            // Ẩn gợi ý khi click ra ngoài
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#product, #product-suggestions').length) {
                    $('#product-suggestions').addClass('d-none');
                }
            });
            // auto fill vào serial
            $('#product').on('change', function () {
                var serial = $(this).find(':selected').data('serial') || '';
                $('#serial_number').val(serial);
            });
        });
    </script>

@endsection