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
                                    placeholder="Nhập tên sản phẩm">
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
                            placeholder="Nhập mã seri tem bảo hành" style="text-transform: uppercase;">
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
                        <select id="type" name="type" class="form-control " >
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
                            placeholder="Nhập họ tên khách hàng"
                            value="{{ $warranty->order_product->order->customer_name ?? $warranty->full_name ?? '' }}">
                            <!--value="{{ $warranty->order_product->order->customer_name ?? '' }}">-->
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="phone_number" class="form-label mt-1">Số điện thoại. (<span
                                style="color: red;">*</span>)</label>
                        <input id="phone_number" name="phone_number" type="number" min="0" class="form-control"
                            placeholder="Nhập số điện thoại"
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
                        <input id="address" name="address" type="text" class="form-control" placeholder="Nhập địa chỉ"
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
                        
                        <input id="shipment_date" name="shipment_date" type="text" class="form-control date-input" placeholder="dd/mm/yyyy" maxlength="10"
                            value="{{ !empty($warranty->created_at) ? \Carbon\Carbon::parse($warranty->created_at)->format('d/m/Y') 
                                    : (!empty($warranty->shipment_date) ? \Carbon\Carbon::parse($warranty->shipment_date)->format('d/m/Y'): '') }}">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="return_date" class="form-label mt-1">Ngày hẹn trả. (<span
                                style="color: red;">*</span>)</label>
                        <input id="return_date" name="return_date" type="text" class="form-control date-input"
                            placeholder="dd/mm/yyyy" maxlength="10" readonly required>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group description_error">
                        <label for="initial_fault_condition" class="form-label mt-1">Tình trạng lỗi ban đầu (nếu có từ phản
                            ánh của KH). (<span style="color: red;">*</span>)</label>
                        <textarea id="initial_fault_condition" name="initial_fault_condition" class="form-control" rows="2"
                             maxlength="1024"></textarea>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group description_error">
                        <label for="product_fault_condition" class="form-label mt-1">Mô tả ngoại quan sản phẩm khi tiếp nhận
                            (VD: vỏ móp méo, gãy đế tay cầm ....) * (<span style="color: red;">*</span>)</label>
                        <textarea class="form-control" id="product_fault_condition" name="product_fault_condition" rows="2"
                             maxlength="1024"></textarea>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group description_error">
                        <label for="product_quantity_description" class="form-label mt-1">Điền số lượng tên sản phẩm khi
                            nhận bàn giao (VD: 1 robot 2909; 1 đế sạc...). (<span style="color: red;">*</span>)</label>
                        <textarea class="form-control" id="product_quantity_description" name="product_quantity_description"
                            rows="2"  maxlength="1024"></textarea>
                        <div class="error text-danger small mb-3"></div>
                    </div>
                    <div class="form-group ctv-fields">
                        <input type="number" id="collaborator_id" name="collaborator_id" value="" hidden>
                        <label for="ctv_phone" class="form-label mt-1">Số điện thoại CTV</label>
                        <input id="ctv_phone" name="ctv_phone" type="text" class="form-control"
                            placeholder="Nhập số điện thoại ctv"  value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group ctv-fields">
                        <label for="ctv_name" class="form-label mt-1">Họ tên CTV</label>
                        <input id="ctv_name" name="ctv_name" type="text" class="form-control" placeholder="Nhập họ tên ctv"
                             value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group ctv-fields">
                        <label for="ctv_address" class="form-label mt-1">Địa chỉ CTV</label>
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
        window.FORM_WARRANTY_CONFIG = {
            routes: {
                getDistrict: '{{ route("ctv.getdistrict", ":province_id") }}',
                getWard: '{{ route("ctv.getward", ":district_id") }}',
                createWarranty: '{{ route("warranty.createwarranty") }}',
                findOld: '{{ route("warranty.findold") }}',
                getCollaborator: '{{ route("getcollaborator") }}',
                takePhoto: '{{ route("warranty.takephoto") }}',
            },
            csrfToken: '{{ csrf_token() }}',
            lstproduct: {!! json_encode($lstproduct ?? []) !!},
            products: {!! json_encode($products ?? []) !!}
        };
    </script>
    <script src="{{ asset('js/warranty/formwarranty.js') }}"></script>

@endsection
