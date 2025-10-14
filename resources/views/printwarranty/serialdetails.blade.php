@extends('layout.layout')

@section('content')
<div class="container-fluid mt-2">
    <div class="row g-4 mb-1">
        <div class="col-12 col-md-12">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white position-relative">
                    <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" onclick="window.history.back()" title="Quay lại"
                        style="height: 15px; filter: brightness(0) invert(1); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                    <h5 class="mb-0 ms-5">Thông tin phiếu nhập</h5>
                </div>
                <div class="card-body ">
                    <p><strong>Tên sản phẩm:</strong> {{ $item->product }}</p>
                    <div class="d-flex justify-content-between">
                        <p><strong>Số lượng:</strong> {{ $item->quantity }}</p>
                        <a href="#" class="btn btn-primary" id="downloadBtn" hidden>Dowload file</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead style="background-color:rgba(199, 199, 199, 0.89);">
                <tr class="text-center">
                    <th class="align-middle">STT</th>
                    <th class="align-middle" style="min-width: 80px;">Số phiếu</th>
                    <th class="align-middle" style="min-width: 100px;">Số Serial</th>
                    <th class="align-middle" style="min-width: 300px;">Tên Sản Phẩm</th>
                    <th class="align-middle" style="min-width: 150px;">Người tạo</th>
                    <th class="align-middle" style="min-width: 80px;">Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="shorten-text text-center">{{ $row->manhaphang }}</td>
                    <td class="shorten-text text-center">{{ $row->sn }}</td>
                    <td class="shorten-text text-center">{{ $row->product_name }}</td>
                    <td class="shorten-text text-center">{{ $item->create_by }}</td>
                    <td class="shorten-text text-center">{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="14" class="text-center">Không có dữ liệu</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection