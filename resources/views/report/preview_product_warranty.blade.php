@extends('layout.layout')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">BÁO CÁO TỔNG HỢP BẢO HÀNH THEO SẢN PHẨM</h4>
        </div>
        <div class="card-body">
            <div class="mb-3 text-end">
                <strong>Chi nhánh:</strong> {{ $branch }} | 
                <strong>Từ ngày:</strong> {{ $fromDate }} | 
                <strong>Đến ngày:</strong> {{ $toDate }}
            </div>
            
            @if($productStats->isEmpty())
                <div class="alert alert-info">
                    Không có dữ liệu báo cáo trong khoảng thời gian này.
                </div>
            @else
                @foreach($productStats as $categoryData)
                    <div class="mb-4">
                        <h5 class="bg-secondary text-white p-2 mb-3">{{ $categoryData['category_name'] }}</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 5%;">TT</th>
                                        <th style="width: 30%;">Tên sản phẩm</th>
                                        <th style="width: 15%;" class="text-center">Tổng số TRƯỜNG HỢP LỖI</th>
                                        <th style="width: 12%;" class="text-center">Bảo hành</th>
                                        <th style="width: 15%;" class="text-center">Hết bảo hành</th>
                                        <th style="width: 23%;" class="text-end">Tổng số tiền thu khách</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryData['products'] as $product)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>{{ $product['product_name'] }}</td>
                                            <td class="text-center">{{ number_format($product['tong_so_loi']) }}</td>
                                            <td class="text-center">{{ number_format($product['bao_hanh']) }}</td>
                                            <td class="text-center">{{ number_format($product['het_bao_hanh']) }}</td>
                                            <td class="text-end">{{ number_format($product['tong_tien'], 0, ',', '.') }} đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-warning">
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Tổng:</td>
                                        <td class="text-center fw-bold">{{ number_format($categoryData['total_loi']) }}</td>
                                        <td class="text-center fw-bold">{{ number_format($categoryData['total_bao_hanh']) }}</td>
                                        <td class="text-center fw-bold">{{ number_format($categoryData['total_het_bao_hanh']) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($categoryData['total_tien'], 0, ',', '.') }} đ</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
            
            <div class="mt-4 text-center">
                <a href="{{ route('baocao') }}" class="btn btn-secondary me-2">Quay lại</a>
                <button type="button" onclick="window.print()" class="btn btn-primary">In báo cáo</button>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, .card-header {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endsection

