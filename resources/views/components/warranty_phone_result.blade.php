<div class="col-12">
    <div class="alert alert-info">
        Kết quả tra cứu theo SĐT: <strong>{{ $phoneDisplay }}</strong>
    </div>
</div>

<div class="col-12">
    <div class="card h-100">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Các phiếu bảo hành trước đây</h5>
        </div>
        <div class="card-body">
            @if($warrantyRequests->isEmpty())
                <p class="mb-0">Chưa ghi nhận phiếu bảo hành nào với số điện thoại này.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>Phiếu</th>
                                <th>Sản phẩm</th>
                                <th>Mã bảo hành</th>
                                <th>Ngày tiếp nhận</th>
                                <th>Chi nhánh</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warrantyRequests as $request)
                                <tr>
                                    <td class="text-center">#{{ $request->id }}</td>
                                    <td>{{ $request->product }}</td>
                                    <td>{{ $request->serial_number ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        {{ $request->received_date ? \Carbon\Carbon::parse($request->received_date)->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td class="text-center">{{ $request->branch ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $request->status ?? 'Chưa cập nhật' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="col-12">
    <div class="card h-100 mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Sản phẩm đã mua</h5>
        </div>
        <div class="card-body">
            @if($purchasedProducts->isEmpty())
                <p class="mb-0">Không tìm thấy đơn hàng nào gắn với số điện thoại này.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Mã bảo hành</th>
                                <th>Sản phẩm</th>
                                <th>Ngày mua</th>
                                <th>Hạn bảo hành</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchasedProducts as $item)
                                @php
                                    $months = (int) ($item->month ?? 0);
                                    $purchaseDate = $item->order_created_at ? \Carbon\Carbon::parse($item->order_created_at) : null;
                                    $warrantyEnd = $purchaseDate && $months > 0 ? $purchaseDate->copy()->addMonths($months) : null;
                                    $orderCode = $item->order_code1 ?? $item->order_code2 ?? 'N/A';
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $orderCode }}</td>
                                    <td class="text-center">{{ $item->warranty_code ?? 'Chưa kích hoạt' }}</td>
                                    <td>{{ $item->product_name ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        {{ $purchaseDate ? $purchaseDate->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $warrantyEnd ? $warrantyEnd->format('d/m/Y') : 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="col-12">
    <div class="card h-100 mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Lịch sử sửa chữa liên quan</h5>
        </div>
        <div class="card-body">
            @if($repairHistory->isEmpty())
                <p class="mb-0">Chưa có lịch sử sửa chữa liên quan đến số điện thoại này.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>Ngày tiếp nhận</th>
                                <th>Sản phẩm</th>
                                <th>Mã bảo hành</th>
                                <th>Lỗi gặp</th>
                                <th>Phương án xử lý</th>
                                <th>Linh kiện/Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($repairHistory as $history)
                                <tr>
                                    <td class="text-center">
                                        {{ $history->warrantyRequest?->received_date ? \Carbon\Carbon::parse($history->warrantyRequest->received_date)->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td>{{ $history->warrantyRequest?->product ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $history->warrantyRequest?->serial_number ?? 'N/A' }}</td>
                                    <td>{{ $history->error_type ?? 'N/A' }}</td>
                                    <td>{{ $history->solution ?? 'N/A' }}</td>
                                    <td>{{ $history->replacement ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

