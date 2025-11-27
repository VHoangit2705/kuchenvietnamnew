/**
 * Lọc CTV theo tỉnh/huyện/xã
 */

$(document).ready(function() {
    $('#province').on('change', function() {
        let provinceId = $(this).val();
        $('#district').empty().append('<option value="" selected>Quận/Huyện</option>');
        $('#ward').empty().append('<option value="" selected>Phường/Xã</option>');
        let url = (window.ROUTES.ctv_getdistrict || '/ctv/getdistrict/:province_id').replace(':province_id', provinceId);
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
        let url = (window.ROUTES.ctv_getward || '/ctv/getward/:district_id').replace(':district_id', districtId);
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
            url: window.ROUTES.collaborators_filter || '/collaborators/filter',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
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

