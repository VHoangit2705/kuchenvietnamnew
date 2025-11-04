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
                <button class="btn btn-primary w-100">Tìm kiếm</button>
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
    $(document).ready(function() {
        $('#openModalBtn').on('click', function(e) {
            e.preventDefault();
            $('#tieude').text("Thêm mới cộng tác viên");
            $('#hoantat').text('Thêm mới');
            $('#addCollaboratorModal').modal('show');
        });
        setRequset();

    });

    $('#searchCollaborator').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        let url = "{{ route('ctv.getlist') }}?" + formData;

        $.get(url, function(response) {
            $('#tabContent').html(response); // chỉ render HTML
        }).fail(function() {
            alert("Không thể tải dữ liệu");
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