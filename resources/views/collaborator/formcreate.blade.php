<!-- Modal thêm mới cộng tác viên -->
<div class="modal fade" id="addCollaboratorModal" tabindex="-1" aria-labelledby="addCollaboratorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 id="tieude" class="modal-title" id="addCollaboratorLabel">Thêm mới cộng tác viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div id="formCreateCollaborator" data-id="">
                    <input type="text" id="id" name="id" value="" hidden>
                    <div class="form-group">
                        <label for="full_name" class="form-label mt-1">Họ tên (<span style="color: red;">*</span>)</label>
                        <input id="full_nameForm" name="full_name" type="text" class="form-control" placeholder="Họ tên" value="" required>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <!--<div class="form-group">-->
                    <!--    <label for="date_of_birth" class="form-label mt-1">Ngày sinh (<span style="color: red;">*</span>)</label>-->
                    <!--    <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" placeholder="Ngày sinh" value="" required>-->
                    <!--    <div class="error text-danger small mt-1"></div>-->
                    <!--</div>-->
                    <div class="form-group">
                        <label for="phone" class="form-label mt-1">Số điện thoại (<span style="color: red;">*</span>)</label>
                        <input id="phoneForm" name="phone" type="text" class="form-control" placeholder="Số điện thoại" value="" required>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="province" class="form-label mt-1">Chọn Tỉnh/TP (<span style="color: red;">*</span>)</label>
                        <select id="provinceForm" name="province" class="form-control" required>
                            <option value="" disabled selected>Tỉnh/TP</option>
                            @foreach ($lstProvince as $item)
                            <option value="{{ $item->province_id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="district" class="form-label mt-1">Chọn Quận/Huyện (<span style="color: red;">*</span>)</label>
                        <select id="districtForm" name="district" class="form-control" required>
                            <option value="" disabled selected>Quận/Huyện</option>
                        </select>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="ward" class="form-label mt-1">Chọn Xã/Phường (<span style="color: red;">*</span>)</label>
                        <select id="wardForm" name="ward" class="form-control" required>
                            <option value="" disabled selected>Xã/Phường</option>
                        </select>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="address" class="form-label mt-1">Địa chỉ (<span style="color: red;">*</span>)</label>
                        <textarea id="address" name="address" class="form-control" rows="2" maxlength="1024" required></textarea>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <hr class="my-3">
                    <h6 class="mb-3">Thông tin ngân hàng</h6>
                    
                    <div class="form-group">
                        <label for="bank_account" class="form-label mt-1">Chủ tài khoản</label>
                        <input id="bank_account" name="bank_account" type="text" class="form-control" placeholder="Chủ tài khoản" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bank_name" class="form-label mt-1">Ngân hàng</label>
                        <input id="bank_name" name="bank_name" type="text" class="form-control" placeholder="Tên ngân hàng" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sotaikhoan" class="form-label mt-1">Số tài khoản</label>
                        <input id="sotaikhoan" name="sotaikhoan" type="text" class="form-control" placeholder="Số tài khoản" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="chinhanh" class="form-label mt-1">Chi nhánh</label>
                        <input id="chinhanh" name="chinhanh" type="text" class="form-control" placeholder="Chi nhánh" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <hr class="my-3">
                    <h6 class="mb-3">Thông tin CCCD/CMND</h6>
                    
                    <div class="form-group">
                        <label for="cccd" class="form-label mt-1">Số CCCD/CMND</label>
                        <input id="cccd" name="cccd" type="text" class="form-control" placeholder="Số CCCD/CMND" value="" maxlength="20">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ngaycap" class="form-label mt-1">Ngày cấp</label>
                        <input id="ngaycap" name="ngaycap" type="date" class="form-control" placeholder="Ngày cấp" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <button id="hoantat" class="mt-1 btn btn-primary w-100">Thêm mới</button>
                </div>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD

<script>
    // Biến toàn cục để theo dõi trạng thái lỗi của form
    let formValidationErrors = {};
    // Hàm hiển thị lỗi
    function showFormError($field, message) {
        let fieldId = $field.attr('id');
        if (!fieldId) return;
        hideFormError($field); // Xóa lỗi cũ trước khi hiển thị lỗi mới
        // Tìm thẻ div lỗi tương ứng và hiển thị message
        let $errorDiv = $field.siblings('.error');
        $errorDiv.text(message);
        $field.css('border-color', 'red');
        // Đánh dấu trường này đang có lỗi
        formValidationErrors[fieldId] = true;
        updateSubmitButtonState();
    }
    // Hàm ẩn lỗi
    function hideFormError($field) {
        let fieldId = $field.attr('id');
        if (!fieldId) return;
        // Xóa message lỗi và reset style
        $field.siblings('.error').text('');
        $field.css('border-color', '');
        // Xóa đánh dấu lỗi của trường này
        delete formValidationErrors[fieldId];
        updateSubmitButtonState();
    }
    // Hàm cập nhật trạng thái nút "Hoàn tất"
    function updateSubmitButtonState() {
        let hasErrors = Object.keys(formValidationErrors).length > 0;
        $("#hoantat").prop('disabled', hasErrors);
    }
    // Hàm validate cho Họ Tên
    function validateFullNameForm() {
        const $input = $('#full_nameForm');
        const name = $input.val().trim();
        const nameRegex = /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;

        if (name.length === 0) {
            showFormError($input, "Trường này là bắt buộc.");
        } else if (name.length > 50) {
            showFormError($input, "Họ tên không được vượt quá 50 ký tự.");
        } else if (!nameRegex.test(name)) {
            showFormError($input, "Họ tên chỉ được chứa chữ.");
        } else {
            hideFormError($input);
        }
    }
    // Hàm validate cho Số Điện Thoại (Sửa đổi)
    function validatePhoneForm() {
        const $input = $('#phoneForm');
        const phoneRaw = $input.val();
        const phoneTrimmed = phoneRaw.trim();

        if (phoneTrimmed.length === 0) {
            showFormError($input, "Trường này là bắt buộc.");
        } else if (/\s/.test(phoneRaw)) {
            showFormError($input, "Số điện thoại không được chứa dấu cách.");
        } else if (!/^\d+$/.test(phoneTrimmed)) {
            showFormError($input, "Số điện thoại chỉ được chứa số.");
        } else if (phoneTrimmed.length < 9 || phoneTrimmed.length > 10) {
            showFormError($input, "Số điện thoại phải có từ 9 đến 10 chữ số.");
        } else {
            hideFormError($input);
        }
    }
    // Hàm validate cho Địa chỉ
    function validateAddress() {
        const i = $('#address'),
            v = i.val().trim(),
            r = /^[a-zA-Z0-9À-ỹ\s,]+$/;

        if (!v) return showFormError(i, 'Trường này là bắt buộc.');
        if (v.length > 80) return showFormError(i, 'Địa chỉ không được vượt quá 80 ký tự.');
        if (!r.test(v)) return showFormError(i, 'Chỉ cho phép chữ, số, khoảng trắng và dấu phẩy (,).');

        hideFormError(i);
    }


    // Hàm validate cho các trường select bắt buộc
    function validateSelectFields() {
        $('#formCreateCollaborator select[required]').each(function() {
            const $field = $(this);

            // Nếu giá trị là rỗng (chưa chọn)
            if (!$field.val()) {
                showFormError($field, "Trường này là bắt buộc.");
            } else {
                hideFormError($field);
            }
        });
    }

    $(document).ready(function() {

        // Hàm validate các trường select bắt buộc (đã bỏ vì validate riêng từng trường đã xử lý)

        $('#addCollaboratorModal').on('hidden.bs.modal', function() {
            const $form = $('#formCreateCollaborator');
            $form.find('input[type="text"], input[type="date"], input[type="number"], textarea').val('');
            $form.find('select').prop('selectedIndex', 0);
            $form.find('.error').text('');
            // Reset lại border và trạng thái lỗi
            $form.find('.form-control').css('border-color', '');
            formValidationErrors = {};
            updateSubmitButtonState();
        });

        // Load combobox quận huyện
        $('#provinceForm').on('change', function() {
            let provinceId = $(this).val();
            let url = '{{ route("ctv.getdistrict", ":province_id") }}'.replace(':province_id', provinceId);
            if (provinceId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        let $district = $('#districtForm');
                        $district.empty();
                        $district.append('<option value="" disabled selected>Quận/Huyện</option>');
                        data.forEach(function(item) {
                            $district.append('<option value="' + item.district_id + '">' + item.name + '</option>');
                        });
                    },
                    complete: function() {
                        // Sau khi load xong quận/huyện, validate lại form
                        validateSelectFields();
                    }
                });
            }
        });
        //load combobox xã phường
        $('#districtForm').on('change', function() {
            let districtId = $(this).val();
            let url = '{{ route("ctv.getward", ":district_id") }}'.replace(':district_id', districtId);
            if (districtId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        let $ward = $('#wardForm');
                        $ward.empty();
                        $ward.append('<option value="" disabled selected>Xã/Phường</option>');
                        data.forEach(function(item) {
                            $ward.append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                        });
                    },
                    complete: function() {
                        // Sau khi load xong xã/phường, validate lại form
                        validateSelectFields();
                    }
                });
            }
        });

        $('#hoantat').on('click', function(e) {
            e.preventDefault();

            // 1. Chạy tất cả các hàm validation một lần cuối
            validateFullNameForm();
            validatePhoneForm();
            validateAddress();
            validateSelectFields(); // Gọi hàm mới

            // 2. Chỉ cần kiểm tra đối tượng lỗi
            if (Object.keys(formValidationErrors).length === 0) {
                // KHÔNG CÒN LỖI -> Gửi AJAX
                const data = {
                    id: $('#id').val(),
                    full_name: $('#full_nameForm').val().trim(),
                    phone: $('#phoneForm').val().trim(),
                    province_id: $('#provinceForm').val(),
                    province: $('#provinceForm option:selected').text(),
                    district_id: $('#districtForm').val(),
                    district: $('#districtForm option:selected').text(),
                    ward_id: $('#wardForm').val(),
                    ward: $('#wardForm option:selected').text(),
                    address: $('#address').val().trim()
                };
                $.ajax({
                    url: '{{ route("ctv.create") }}',
                    type: 'POST',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Notification('success', response.message, 1500, false)
                        $('#addCollaboratorModal').modal('hide');
                        $.get("{{ route('ctv.getlist') }}", function(html) {
                            $('#tabContent').html(html);
                        });
                    },
                    error: function(xhr) {
                        alert('Có lỗi xảy ra khi tạo cộng tác viên.');
                        console.log(xhr.responseText);
                    }
                });
            } else {
                // Vẫn còn lỗi, cuộn đến lỗi đầu tiên
                $('html, body').animate({
                    scrollTop: $('#formCreateCollaborator .error:visible:first').offset().top - 100
                }, 300);
            }
        });

        // Gắn sự kiện validate khi người dùng nhập liệu
        $('#full_nameForm').on('input', validateFullNameForm);
        $('#phoneForm').on('input', validatePhoneForm);
        $('#address').on('input', validateAddress);

        // Gắn sự kiện validate cho các trường select khi giá trị thay đổi
        $('#provinceForm, #districtForm, #wardForm').on('change', function() {
            validateSelectFields();
        });
    });
</script>
=======
>>>>>>> 53d2d648d53ecb44cbe48ca3ba5638ef91e09fe6
