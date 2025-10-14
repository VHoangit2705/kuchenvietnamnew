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
    $('#addCollaboratorModal').on('hidden.bs.modal', function() {
        const $form = $('#formCreateCollaborator');
        $form.find('input[type="text"], input[type="date"], input[type="number"], textarea').val('');
        $form.find('select').prop('selectedIndex', 0);
        $form.find('.error').text('');
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
            if (validateRequired('#formCreateCollaborator')) {
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
    });
</script>