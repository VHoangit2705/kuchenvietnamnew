/**
 * Xử lý các thao tác trên bảng cộng tác viên (sửa, xóa)
 */
(function (window, $) {
    if (!$) return;

    let routes = {};

    function refreshTable() {
        if (!routes.getList) return;
        $.get(routes.getList, function (html) {
            $('#tabContent').html(html);
        });
    }

    function deleteCollaborator(id) {
        if (!routes.delete) {
            console.error('Collaborator route delete chưa được cấu hình');
            return;
        }

        Swal.fire({
            title: 'Xác nhận xoá',
            text: 'Bạn có chắc chắn muốn xoá bản ghi này không?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xoá',
            cancelButtonText: 'Huỷ'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: routes.delete.replace(':id', id),
                type: 'GET',
                success: function (response) {
                    Notification('success', response.message, 1000, false);
                    refreshTable();
                },
                error: function (xhr) {
                    Swal.fire('Lỗi', 'Có lỗi xảy ra khi xoá.', 'error');
                    console.error(xhr.responseText);
                }
            });
        });
    }

    function populateEditForm(item, districts, wards) {
        $('#tieude').text('Cập nhật cộng tác viên');
        $('#hoantat').text('Cập nhật');
        $('#full_nameForm').val(item.full_name);
        $('#date_of_birth').val(
            window.CollaboratorShared
                ? window.CollaboratorShared.formatDateToInput(item.date_of_birth)
                : ''
        );
        $('#phoneForm').val(item.phone);

        const $district = $('#districtForm');
        $district.empty();
        $district.append('<option value="" disabled selected>Quận/Huyện</option>');
        districts.forEach(function (district) {
            const selected = district.district_id === item.district_id ? 'selected' : '';
            $district.append(
                `<option value="${district.district_id}" ${selected}>${district.name}</option>`
            );
        });

        const $ward = $('#wardForm');
        $ward.empty();
        $ward.append('<option value="" disabled selected>Xã/Phường</option>');
        wards.forEach(function (ward) {
            const selected = ward.wards_id === item.ward_id ? 'selected' : '';
            $ward.append(`<option value="${ward.wards_id}" ${selected}>${ward.name}</option>`);
        });

        $('#provinceForm').val(item.province_id);
        $('#address').val(item.address);
        $('#id').val(item.id);
    }

    function registerEditHandler() {
        $(document).on('click', '.edit-row', function () {
            const id = $(this).data('id');
            if (!routes.getById) {
                console.error('Collaborator route getById chưa được cấu hình');
                return;
            }

            $.ajax({
                url: routes.getById,
                type: 'POST',
                data: { id: id },
                success: function (response) {
                    const item = response.data.collaborator;
                    const districts = response.data.districts || [];
                    const wards = response.data.wards || [];
                    populateEditForm(item, districts, wards);
                },
                error: function (xhr) {
                    alert('Có lỗi xảy ra khi lấy dữ liệu cộng tác viên.');
                    console.error(xhr.responseText);
                }
            });
        });
    }

    $(document).ready(function () {
        routes = window.CollaboratorRoutes || {};
        registerEditHandler();
        window.Delete = deleteCollaborator;
    });
})(window, window.jQuery);

