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
                    <button id="hoantat" class="mt-1 btn btn-primary w-100">Thêm mới</button>
                </div>
            </div>
        </div>
    </div>
</div>

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

        if (name.length > 50) {
            showFormError($input, "Họ tên không được vượt quá 50 ký tự.");
        } else if (name.length > 0 && !nameRegex.test(name)) {
            showFormError($input, "Họ tên chỉ được chứa chữ.");
        } else {
            hideFormError($input);
        }
    }

    // Hàm validate cho Số Điện Thoại
    function validatePhoneForm() {
        const $input = $('#phoneForm');
        const phone = $input.val().trim();

        if (phone.length > 0) { // Chỉ validate nếu có nhập
            if (!/^\d+$/.test(phone)) {
                showFormError($input, "Số điện thoại chỉ được chứa số.");
            } else if (phone.length < 9 || phone.length > 10) {
                showFormError($input, "Số điện thoại phải có từ 9 đến 10 chữ số.");
            } else {
                hideFormError($input);
            }
        } else {
            hideFormError($input); // Xóa lỗi nếu người dùng xóa hết
        }
    }

    // Hàm validate cho Địa chỉ
    function validateAddress() {
        const $input = $('#address');
        const address = $input.val().trim();
        // Cho phép chữ, số, và một số ký tự địa chỉ phổ biến
        const addressRegex = /^[a-zA-Z0-9àáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s\.,\/-]*$/;

        if (address.length > 80) {
            showFormError($input, "Địa chỉ không được vượt quá 80 ký tự.");
        } else if (address.length > 0 && !addressRegex.test(address)) {
            showFormError($input, "Địa chỉ chỉ chứa chữ, số và các ký tự .,-/");
        } else {
            hideFormError($input);
        }
    }

    // Hàm kiểm tra các trường bắt buộc (required)
    function validateRequired(formSelector) {
        let isValid = true;
        $(formSelector).find('[required]').each(function() {
            const $field = $(this);
            const value = $field.val();

            // Kiểm tra giá trị rỗng cho cả input, textarea và select
            if (value === null || value.trim() === '') {
                // Chỉ hiển thị lỗi nếu chưa có lỗi nào khác từ các hàm validate khác
                if (!$field.siblings('.error').text()) {
                    showFormError($field, "Trường này là bắt buộc.");
                }
                isValid = false;
            } else {
                // Nếu trường đã có giá trị, xóa lỗi "bắt buộc" nếu có
                if ($field.siblings('.error').text() === "Trường này là bắt buộc.") {
                    hideFormError($field);
                }
            }
        });
        return isValid;
    }

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
            });
        }
    });

    $(document).ready(function() {
        $('#hoantat').on('click', function(e) {
            e.preventDefault();

            // Chạy tất cả các hàm validation một lần cuối
            validateFullNameForm();
            validatePhoneForm();
            validateAddress();

            // Kiểm tra các trường select bắt buộc
            validateRequired('#formCreateCollaborator');

            // Nếu không có lỗi và các trường bắt buộc đã được điền
            if (Object.keys(formValidationErrors).length === 0 && validateRequired('#formCreateCollaborator')) {
                const data = {
                    id: $('#id').val(),
                    full_name: $('#full_nameForm').val().trim(),
                    // date_of_birth: $('#date_of_birth').val().trim(),
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
            // Gọi hàm validateRequired để kiểm tra lại và xóa lỗi nếu đã chọn
            validateRequired('#formCreateCollaborator');
        });

    });
</script>