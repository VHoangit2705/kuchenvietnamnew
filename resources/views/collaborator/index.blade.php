@extends('layout.layout')

@section('content')
<div class="container mt-2">
    <form id="searchCollaborator" 
          data-route-getdistrict="{{ route('ctv.getdistrict', ':province_id') }}"
          data-route-getward="{{ route('ctv.getward', ':district_id') }}"
          data-route-getlist="{{ route('ctv.getlist') }}"
          data-request-province="{{ request('province') }}"
          data-request-district="{{ request('district') }}"
          data-request-ward="{{ request('ward') }}">
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
    <div id="tabContent" 
         data-route-delete="{{ route('ctv.delete', ':id') }}"
         data-route-getlist="{{ route('ctv.getlist') }}"
         data-route-getbyid="{{ route('ctv.getbyid') }}">
        @include('collaborator.tablecontent', ['data' => $data])
    </div>
</div>
<!-- Include modal -->
@include('collaborator.formcreate', ['lstProvince' => $lstProvince])
<script>
    // Định nghĩa URL API ngân hàng từ env
    window.VIETQR_BANKS_URL = '{{ env("VIETQR_BANKS_URL", "https://api.vietqr.io/v2/banks") }}';
</script>
<script src="{{ asset('js/collaborator/common.js') }}"></script>
<script src="{{ asset('js/collaborator/index.js') }}"></script>
<script>
    // Load danh sách ngân hàng khi trang được tải để hiển thị logo trong bảng
    $(document).ready(function() {
        if (typeof loadBanks === 'function') {
            loadBanks('', '', '', function() {
                // Logo đã được cập nhật tự động trong hàm loadBanks
            });
        }
    });
</script>
@endsection