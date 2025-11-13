@extends('layout.layout')

@section('content')
<div class="container mt-2">
    <form id="searchCollaborator">
        <div class="row">
            <div class="col-md-4 mb-1">
                <select id="province" name="province" class="form-control">
                    <option value="" selected>Tỉnh/TP</option>
                    @foreach ($lstProvince as $item)
                    <option value="{{ $item->province_id}}" {{ request('province') == $item->province_id ? 'selected' : '' }}>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-1">
                <select id="district" name="district" class="form-control">
                    <option value="" selected>Quận/Huyện</option>
                </select>
            </div>
            <div class="col-md-4 mb-1">
                <select id="ward" name="ward" class="form-control">
                    <option value="" selected>Phường/Xã</option>
                </select>
            </div>
            <div class="col-md-4 mb-1">
                <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Họ tên" value="{{ request('full_name') }}">
            </div>
            <div class="col-md-4 mb-1">
                <input type="text" id="phone" name="phone" class="form-control" placeholder="Số điện thoại" value="{{ request('phone') }}">
            </div>
            <div class="col-md-4 mb-1">
                <button id="searchBtn" class="btn btn-primary w-100">Tìm kiếm</button>
            </div>
        </div>
    </form>
</div>
<div class="container-fluid mt-2">
    <div class="mb-1">
        <a href="#" class="btn btn-primary" id="openModalBtn">Thêm mới</a>
    </div>
    <!-- Nội dung tab -->
    <div id="tabContent">
        @include('collaborator.tablecontent', ['data' => $data])
    </div>
</div>
<!-- Include modal -->
@include('collaborator.formcreate', ['lstProvince' => $lstProvince])
<script>

    // 1. Tạo cờ chung để theo dõi lỗi validation
    searchValidationErrors = {};


    //Hàm hiển hi lỗi
    function showError($field, message) {
        let fieldId = $field.attr('id');
        if (!fieldId) return;

        hideError($field);

        // Tạo thẻ div lỗi
        let $error = $(`<div class="text-danger mt-1 validation-error" data-error-for="${fieldId}">${message}</div>`);

        $field.closest('.col-md-4').append($error);
        $field.css('border-color', 'red');

        searchValidationErrors[fieldId] = true;
        updateSearchButtonState();
    }

    // 3. Hàm ẩn lỗi
    function hideError($field) {
        let fieldId = $field.attr('id');
        if (!fieldId) return;

        // Tìm và xóa thẻ lỗi tương ứng
        $field.closest('.col-md-4').find(`.validation-error[data-error-for="${fieldId}"]`).remove();
        $field.css('border-color', '');

        delete searchValidationErrors[fieldId];
        updateSearchButtonState();
    }

    // 4. Hàm cập nhật trạng thái nút Tìm kiếm
    function updateSearchButtonState() {
        // Kiểm tra xem có bất kỳ lỗi nào trong cờ chung không
        let hasErrors = Object.keys(searchValidationErrors).length > 0;
        
        // Vô hiệu hóa nút nếu có lỗi
        $("#searchBtn").prop('disabled', hasErrors);
    }
    // 5. Hàm logic validate cho Họ Tên
    function validateFullName() {
        const nameRegex = /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;
        let $input = $('#full_name');
        let name = $input.val().trim();
        
        hideError($input);

        if (name.length > 50) {
            showError($input, "Họ tên tối đa 50 ký tự.");
        } else if (name.length > 0 && !nameRegex.test(name)) {
            // Chỉ validate regex nếu có nhập
            showError($input, "Họ tên chỉ được chữ.");
        }
    }

    // 6. Hàm logic validate cho Số Điện Thoại
    function validatePhone() {
        let $input = $('#phone');
        let phone = $input.val();

        hideError($input);

    
        if (phone.length > 0) {
            if (!/^\d+$/.test(phone)) {
                showError($input, "Số điện thoại chỉ được chứa số, không khoảng cách.");
            } else if (phone.length < 9 || phone.length > 10) {
                showError($input, "Số điện thoại phải từ 9 đến 10 số.");
            }
        }
    }


    $(document).ready(function() {
        // Thêm CSRF token vào tất cả các header của request AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Hàm định dạng ngày tháng Y-m-d để gán cho input type="date"
        function formatDateToInput(dateString) {
            return dateString ? new Date(dateString).toISOString().split('T')[0] : '';
        }

        $('#openModalBtn').on('click', function(e) {
            e.preventDefault();
            $('#tieude').text("Thêm mới cộng tác viên");
            $('#hoantat').text('Thêm mới');
            $('#addCollaboratorModal').modal('show');
        });
        setRequset();

        $('#full_name').on('keyup input', validateFullName);
        $('#phone').on('keyup input', validatePhone);

    });

    // Cập nhật lại hàm submit
    $('#searchCollaborator').on('submit', function(e) {
        e.preventDefault();
        
        // Kiểm tra cờ lỗi chung
        if (Object.keys(searchValidationErrors).length > 0) {
            return; // Dừng lại nếu form không hợp lệ
        }

        let formData = $(this).serialize();
        let url = "{{ route('ctv.getlist') }}?" + formData;

        // Vô hiệu hóa nút tạm thời khi đang gửi request
        $('#searchBtn').prop('disabled', true).text('Đang tìm...');

        $.get(url, function(response) {
            $('#tabContent').html(response); // chỉ render HTML
        }).fail(function() {
            alert("Không thể tải dữ liệu");
        }).always(function() {
            // Dù thành công hay thất bại, bật lại nút
            // và chạy lại validate để set trạng thái disable chính xác
            $('#searchBtn').text('Tìm kiếm');
        });
    });

    function setRequset() {
        let selectedProvince = "{{ request('province') }}";
        let selectedDistrict = "{{ request('district') }}";
        let selectedWard = "{{ request('ward') }}";
        let url = '{{ route("ctv.getdistrict", ":province_id") }}'.replace(':province_id', selectedProvince);
        if (selectedProvince) {
            $.ajax({
                url: url,
                type: 'GET',
                success: function(data) {
                    let $district = $('#district');
                    $district.empty();
                    $district.append('<option value="" disabled>Quận/Huyện</option>');
                    data.forEach(function(item) {
                        let selected = item.district_id == selectedDistrict ? 'selected' : '';
                        $district.append('<option value="' + item.district_id + '" ' + selected + '>' + item.name + '</option>');
                    });

                    // Gọi tiếp API để load ward nếu district có sẵn
                    if (selectedDistrict) {
                        let url = '{{ route("ctv.getward", ":district_id") }}'.replace(':district_id', selectedDistrict);
                        $.ajax({
                            url: url,
                            type: 'GET',
                            success: function(data) {
                                let $ward = $('#ward');
                                $ward.empty();
                                $ward.append('<option value="" disabled>Phường/Xã</option>');
                                data.forEach(function(item) {
                                    let selected = item.wards_id == selectedWard ? 'selected' : '';
                                    $ward.append('<option value="' + item.wards_id + '" ' + selected + '>' + item.name + '</option>');
                                });
                            }
                        });
                    }
                }
            });
        }
    }

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
    });
</script>
@endsection