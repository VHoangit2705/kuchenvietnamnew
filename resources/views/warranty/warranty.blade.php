@extends('layout.layout')

@section('content')
    <div class="container mt-4">
        <form id="searchForm" method="GET" action="{{ route('warranty.search') }}">
            <!-- @csrf -->
            <div class="row">
                <div class="col-md-4 mb-1">
                    @if (session('position') != 'Kỹ thuật viên')
                        <select class="form-select" name="chinhanh">
                            <option value="">Tất cả chi nhánh</option>
                            @if (session('brand') == 'kuchen')
                                <option value="KUCHEN VINH" {{ request('chinhanh') == 'KUCHEN VINH' ? 'selected' : '' }}>KUCHEN VINH
                                </option>
                                <option value="KUCHEN HÀ NỘI" {{ request('chinhanh') == 'KUCHEN HÀ NỘI' ? 'selected' : '' }}>KUCHEN HÀ
                                    NỘI</option>
                                <option value="KUCHEN HCM" {{ request('chinhanh') == 'Kuchen HCM' ? 'selected' : '' }}>KUCHEN HCM
                                </option>
                            @else
                                <option value="HUROM VINH" {{ request('chinhanh') == 'HUROM VINH' ? 'selected' : '' }}>HUROM VINH
                                </option>
                                <option value="HUROM HÀ NỘI" {{ request('chinhanh') == 'HUROM HÀ NỘI' ? 'selected' : '' }}>HUROM HÀ
                                    NỘI</option>
                                <option value="HUROM HCM" {{ request('chinhanh') == 'HUROM HCM' ? 'selected' : '' }}>HUROM HCM
                                </option>
                            @endif
                        </select>
                    @else
                        <select class="form-select" name="branch_display" disabled>
                            <option selected>{{ $userBranch }}</option>
                        </select>
                        <input type="hidden" name="chinhanh" value="{{ $userBranch }}">
                    @endif
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="sophieu" name="sophieu" class="form-control" placeholder="Nhập số phiếu"
                        value="{{ request('sophieu') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="seri" name="seri" class="form-control" placeholder="Nhập số seri"
                        value="{{ request('seri') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="sdt" name="sdt" class="form-control" placeholder="Nhập số điện thoại"
                        value="{{ request('sdt') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="khachhang" name="khachhang" class="form-control"
                        placeholder="Nhập tên khách hàng" value="{{ request('khachhang') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="kythuatvien" name="kythuatvien" class="form-control"
                        placeholder="Nhập tên kỹ thuật viên" value="{{ request('kythuatvien') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>
    <div class="container-fluid mt-3">
        <div class="d-flex" style="overflow-x: auto; white-space: nowrap;">
            <ul class="nav nav-tabs flex-nowrap" id="myTab" role="tablist">
                @php
                    $tabs = [
                        'Danh sách phiếu' => $data,
                        'Đang sửa chữa' => $dangsua,
                        'Chờ khách hàng phản hồi' => $chophanhoi,
                        'Quá hạn sửa chữa' => $quahan,
                        'Đã hoàn tất' => $hoantat
                    ];
                    $index = 1;
                @endphp

                @foreach($tabs as $label => $dataset)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                            data-bs-target="#tab-{{ $index }}" data-tab="{{ $label }}" type="button" role="tab">
                            {{ $label }} <span class="text-danger">({{ $dataset->total() }})</span>
                        </button>
                    </li>
                    @php $index++; @endphp
                @endforeach
            </ul>
        </div>

        <div class="tab-content mt-1">
            @php $index = 1; @endphp
            @foreach($tabs as $dataset)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $index }}" role="tabpanel">
                    @include('components.data_table', ['data' => $dataset])
                </div>
                @php $index++; @endphp
            @endforeach
        </div>
        @include('components.status_modal')
    </div>

    <script src="{{ asset('public/js/warranty/warranty.js') }}"></script>
@endsection