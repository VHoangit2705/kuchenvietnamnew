/**
 * Quản lý form tìm kiếm và bộ lọc cộng tác viên
 */
(function (window, $) {
    if (!$) return;

    const state = {
        errors: {}
    };
    let routes = {};
    let initialRequest = {};

    function getFieldId($field) {
        return $field && $field.length ? $field.attr('id') : null;
    }

    function updateSearchButtonState() {
        const hasErrors = Object.keys(state.errors).length > 0;
        $('#searchBtn').prop('disabled', hasErrors);
    }

    function showError($field, message) {
        const fieldId = getFieldId($field);
        if (!fieldId) return;

        hideError($field);
        const $error = $(
            `<div class="text-danger mt-1 validation-error" data-error-for="${fieldId}">${message}</div>`
        );
        $field.closest('.col-md-4').append($error);
        $field.css('border-color', 'red');
        state.errors[fieldId] = true;
        updateSearchButtonState();
    }

    function hideError($field) {
        const fieldId = getFieldId($field);
        if (!fieldId) return;

        $field
            .closest('.col-md-4')
            .find(`.validation-error[data-error-for="${fieldId}"]`)
            .remove();
        $field.css('border-color', '');
        delete state.errors[fieldId];
        updateSearchButtonState();
    }

    function validateFullName() {
        const nameRegex =
            /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;
        const $input = $('#full_name');
        const name = ($input.val() || '').trim();

        hideError($input);

        if (name.length > 50) {
            showError($input, 'Họ tên tối đa 50 ký tự.');
        } else if (name.length > 0 && !nameRegex.test(name)) {
            showError($input, 'Họ tên chỉ được chữ.');
        }
    }

    function validatePhone() {
        const $input = $('#phone');
        const phone = $input.val() || '';

        hideError($input);

        if (phone.length > 0) {
            if (!/^\d+$/.test(phone)) {
                showError($input, 'Số điện thoại chỉ được chứa số, không khoảng cách.');
            } else if (phone.length < 9 || phone.length > 10) {
                showError($input, 'Số điện thoại phải từ 9 đến 10 số.');
            }
        }
    }

    function handleSearchSubmit(event) {
        event.preventDefault();
        if (Object.keys(state.errors).length > 0) return;

        const formData = $(event.currentTarget).serialize();
        if (!routes.getList) {
            console.error('Collaborator route getList chưa được cấu hình');
            return;
        }
        const url = `${routes.getList}?${formData}`;

        $('#searchBtn').prop('disabled', true).text('Đang tìm...');

        $.get(url, function (response) {
            $('#tabContent').html(response);
        })
            .fail(function () {
                alert('Không thể tải dữ liệu');
            })
            .always(function () {
                $('#searchBtn').text('Tìm kiếm');
                updateSearchButtonState();
            });
    }

    function loadDistrictOptions(provinceId, selectedDistrict, selectedWard) {
        if (!routes.getDistrict) return;
        const url = routes.getDistrict.replace(':province_id', provinceId);
        if (!provinceId) return;

        $.ajax({
            url: url,
            type: 'GET',
            success: function (data) {
                const $district = $('#district');
                $district.empty();
                $district.append('<option value="" disabled>Quận/Huyện</option>');
                data.forEach(function (item) {
                    const selected = item.district_id == selectedDistrict ? 'selected' : '';
                    $district.append(
                        `<option value="${item.district_id}" ${selected}>${item.name}</option>`
                    );
                });

                if (selectedDistrict) {
                    loadWardOptions(selectedDistrict, selectedWard);
                }
            }
        });
    }

    function loadWardOptions(districtId, selectedWard) {
        if (!routes.getWard) return;
        const url = routes.getWard.replace(':district_id', districtId);
        if (!districtId) return;

        $.ajax({
            url: url,
            type: 'GET',
            success: function (data) {
                const $ward = $('#ward');
                $ward.empty();
                $ward.append('<option value="" disabled>Phường/Xã</option>');
                data.forEach(function (item) {
                    const selected = item.wards_id == selectedWard ? 'selected' : '';
                    $ward.append(
                        `<option value="${item.wards_id}" ${selected}>${item.name}</option>`
                    );
                });
            }
        });
    }

    function handleProvinceChange() {
        const provinceId = $(this).val();
        $('#district')
            .empty()
            .append('<option value="" selected>Quận/Huyện</option>');
        $('#ward')
            .empty()
            .append('<option value="" selected>Phường/Xã</option>');
        if (provinceId) {
            loadDistrictOptions(provinceId);
        }
    }

    function handleDistrictChange() {
        const districtId = $(this).val();
        $('#ward')
            .empty()
            .append('<option value="" selected>Phường/Xã</option>');
        if (districtId) {
            loadWardOptions(districtId);
        }
    }

    function initialiseFromRequest() {
        const selectedProvince = initialRequest.province || '';
        const selectedDistrict = initialRequest.district || '';
        const selectedWard = initialRequest.ward || '';

        if (selectedProvince) {
            loadDistrictOptions(selectedProvince, selectedDistrict, selectedWard);
        }
    }

    function bindEvents() {
        $('#full_name').on('keyup input', validateFullName);
        $('#phone').on('keyup input', validatePhone);
        $('#searchCollaborator').on('submit', handleSearchSubmit);
        $('#province').on('change', handleProvinceChange);
        $('#district').on('change', handleDistrictChange);
        $('#resetFilters').on('click', resetFilters);

        $('#openModalBtn').on('click', function (event) {
            event.preventDefault();
            $('#tieude').text('Thêm mới cộng tác viên');
            $('#hoantat').text('Thêm mới');
            $('#addCollaboratorModal').modal('show');
        });
    }

    function resetFilters() {
        const $form = $('#searchCollaborator');
        if ($form.length === 0) return;

        $form[0].reset();

        state.errors = {};
        $('.validation-error').remove();
        $form.find('.form-control').css('border-color', '');
        updateSearchButtonState();

        $('#district')
            .empty()
            .append('<option value="" selected>Quận/Huyện</option>');
        $('#ward')
            .empty()
            .append('<option value="" selected>Phường/Xã</option>');

        initialRequest = {};

        if (routes.getList) {
            $.get(routes.getList, function (html) {
                $('#tabContent').html(html);
            });
        }
    }

    $(document).ready(function () {
        if (window.CollaboratorShared) {
            window.CollaboratorShared.setupAjaxCsrf();
        }

        routes = window.CollaboratorRoutes || {};
        initialRequest = window.CollaboratorRequest || {};

        bindEvents();
        initialiseFromRequest();
    });
})(window, window.jQuery);

