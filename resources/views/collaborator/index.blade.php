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
            <div class="col-md-4 mb-1 d-flex gap-2">
                <button id="searchBtn" class="btn btn-primary flex-fill">Tìm kiếm</button>
                <button type="button" id="resetFilters" class="btn btn-outline-secondary flex-fill">Xóa bộ lọc</button>
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
    window.CollaboratorRoutes = {
        getList: "{{ route('ctv.getlist') }}",
        getDistrict: "{{ route('ctv.getdistrict', ':province_id') }}",
        getWard: "{{ route('ctv.getward', ':district_id') }}",
        create: "{{ route('ctv.create') }}",
        getById: "{{ route('ctv.getbyid') }}",
        delete: "{{ route('ctv.delete', ':id') }}"
    };

    window.CollaboratorRequest = {
        province: "{{ request('province') }}",
        district: "{{ request('district') }}",
        ward: "{{ request('ward') }}"
    };
</script>
<script src="{{ asset('public/js/collaborator/shared.js') }}"></script>
<script src="{{ asset('public/js/collaborator/search.js') }}"></script>
<script src="{{ asset('public/js/collaborator/form-modal.js') }}"></script>
<script src="{{ asset('public/js/collaborator/table-actions.js') }}"></script>
@endsection