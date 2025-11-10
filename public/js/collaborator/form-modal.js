/**
 * Quản lý modal thêm/sửa cộng tác viên
 */
(function (window, $) {
    if (!$) return;

    const state = {
        errors: {}
    };
    let routes = {};

    function markError(fieldId) {
        state.errors[fieldId] = true;
        updateSubmitButtonState();
    }

    function unmarkError(fieldId) {
        delete state.errors[fieldId];
        updateSubmitButtonState();
    }

    function updateSubmitButtonState() {
        const hasErrors = Object.keys(state.errors).length > 0;
        $('#hoantat').prop('disabled', hasErrors);
    }

    function showFormError($field, message) {
        const fieldId = $field.attr('id');
        if (!fieldId) return;
        hideFormError($field);

        const $errorDiv = $field.siblings('.error');
        $errorDiv.text(message);
        $field.css('border-color', 'red');
        markError(fieldId);
    }

    function hideFormError($field) {
        const fieldId = $field.attr('id');
        if (!fieldId) return;
        $field.siblings('.error').text('');
        $field.css('border-color', '');
        unmarkError(fieldId);
    }

    function validateFullNameForm() {
        const $input = $('#full_nameForm');
        const name = ($input.val() || '').trim();
        const nameRegex =
            /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;

        if (name.length === 0) {
            showFormError($input, 'Trường này là bắt buộc.');
        } else if (name.length > 50) {
            showFormError($input, 'Họ tên không được vượt quá 50 ký tự.');
        } else if (!nameRegex.test(name)) {
            showFormError($input, 'Họ tên chỉ được chứa chữ.');
        } else {
            hideFormError($input);
        }
    }

    function validatePhoneForm() {
        const $input = $('#phoneForm');
        const phoneRaw = $input.val() || '';
        const phoneTrimmed = phoneRaw.trim();

        if (phoneTrimmed.length === 0) {
            showFormError($input, 'Trường này là bắt buộc.');
        } else if (/\s/.test(phoneRaw)) {
            showFormError($input, 'Số điện thoại không được chứa dấu cách.');
        } else if (!/^\d+$/.test(phoneTrimmed)) {
            showFormError($input, 'Số điện thoại chỉ được chứa số.');
        } else if (phoneTrimmed.length < 9 || phoneTrimmed.length > 10) {
            showFormError($input, 'Số điện thoại phải có từ 9 đến 10 chữ số.');
        } else {
            hideFormError($input);
        }
    }

    function validateAddress() {
        const $input = $('#address');
        const address = ($input.val() || '').trim();
        const addressRegex =
            /^[a-zA-Z0-9àáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\\s\\.,\\/-]*$/;

        if (address.length === 0) {
            showFormError($input, 'Trường này là bắt buộc.');
        } else if (address.length > 80) {
            showFormError($input, 'Địa chỉ không được vượt quá 80 ký tự.');
        } else if (!addressRegex.test(address)) {
            showFormError($input, 'Địa chỉ chỉ chứa chữ, số và các ký tự .,-/');
        } else {
            hideFormError($input);
        }
    }

    function validateSelectFields() {
        $('#formCreateCollaborator select[required]').each(function () {
            const $field = $(this);
            if (!$field.val()) {
                showFormError($field, 'Trường này là bắt buộc.');
            } else {
                hideFormError($field);
            }
        });
    }

    function resetForm() {
        const $form = $('#formCreateCollaborator');
        $form.find('input[type="text"], input[type="date"], input[type="number"], textarea').val('');
        $form.find('select').prop('selectedIndex', 0);
        $form.find('.error').text('');
        $form.find('.form-control').css('border-color', '');
        state.errors = {};
        updateSubmitButtonState();
    }

    function populateDistrictOptions(provinceId) {
        if (!routes.getDistrict) return;
        const url = routes.getDistrict.replace(':province_id', provinceId);

        $.ajax({
            url: url,
            type: 'GET',
            success: function (data) {
                const $district = $('#districtForm');
                $district.empty();
                $district.append('<option value="" disabled selected>Quận/Huyện</option>');
                data.forEach(function (item) {
                    $district.append(`<option value="${item.district_id}">${item.name}</option>`);
                });
            },
            complete: function () {
                validateSelectFields();
            }
        });
    }

    function populateWardOptions(districtId) {
        if (!routes.getWard) return;
        const url = routes.getWard.replace(':district_id', districtId);

        $.ajax({
            url: url,
            type: 'GET',
            success: function (data) {
                const $ward = $('#wardForm');
                $ward.empty();
                $ward.append('<option value="" disabled selected>Xã/Phường</option>');
                data.forEach(function (item) {
                    $ward.append(`<option value="${item.wards_id}">${item.name}</option>`);
                });
            },
            complete: function () {
                validateSelectFields();
            }
        });
    }

    function handleSubmit(event) {
        event.preventDefault();

        validateFullNameForm();
        validatePhoneForm();
        validateAddress();
        validateSelectFields();

        if (Object.keys(state.errors).length > 0) {
            const $firstError = $('#formCreateCollaborator .error')
                .filter(function () {
                    return $(this).text().trim() !== '';
                })
                .first();
            if ($firstError.length) {
                $('html, body').animate(
                    {
                        scrollTop: $firstError.offset().top - 100
                    },
                    300
                );
            }
            return;
        }

        if (!routes.create) {
            console.error('Collaborator route create chưa được cấu hình');
            return;
        }

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
            url: routes.create,
            type: 'POST',
            data: data,
            success: function (response) {
                Notification('success', response.message, 1500, false);
                $('#addCollaboratorModal').modal('hide');
                if (routes.getList) {
                    $.get(routes.getList, function (html) {
                        $('#tabContent').html(html);
                    });
                }
            },
            error: function (xhr) {
                alert('Có lỗi xảy ra khi tạo/cập nhật cộng tác viên.');
                console.error(xhr.responseText);
            }
        });
    }

    function bindEvents() {
        $('#addCollaboratorModal').on('hidden.bs.modal', resetForm);
        $('#provinceForm').on('change', function () {
            const provinceId = $(this).val();
            $('#districtForm').empty().append('<option value="" disabled selected>Quận/Huyện</option>');
            $('#wardForm').empty().append('<option value="" disabled selected>Xã/Phường</option>');
            if (provinceId) {
                populateDistrictOptions(provinceId);
            }
            validateSelectFields();
        });
        $('#districtForm').on('change', function () {
            const districtId = $(this).val();
            $('#wardForm').empty().append('<option value="" disabled selected>Xã/Phường</option>');
            if (districtId) {
                populateWardOptions(districtId);
            }
            validateSelectFields();
        });
        $('#hoantat').on('click', handleSubmit);

        $('#full_nameForm').on('input', validateFullNameForm);
        $('#phoneForm').on('input', validatePhoneForm);
        $('#address').on('input', validateAddress);
        $('#provinceForm, #districtForm, #wardForm').on('change', validateSelectFields);
    }

    $(document).ready(function () {
        routes = window.CollaboratorRoutes || {};
        bindEvents();
    });
})(window, window.jQuery);

