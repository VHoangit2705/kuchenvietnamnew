@php
$primaryWarranty = $warrantyRequests->first();
$primaryOrder = $purchasedProducts->first();
$customerName = $primaryWarranty->full_name
?? $primaryOrder->customer_name
?? 'N/A';
$customerPhone = $primaryWarranty->phone_number
?? $primaryOrder->customer_phone
?? $phoneDisplay;
$customerAddress = $primaryOrder->customer_address ?? 'N/A';
@endphp

<div class="col-12">
    <div class="alert alert-info">
        Kết quả tra cứu theo SĐT: <strong>{{ $phoneDisplay }}</strong>
        @if(!empty($productFilter))
        - Sản phẩm: <strong>{{ $productFilter }}</strong>
        @endif
    </div>
</div>

<div class="col-12 col-lg-6">
    <div class="card h-100">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Thông tin khách hàng bảo hành</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive col-12">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th>1. Tên khách hàng</th>
                            <td>{{ $customerName }}</td>
                        </tr>
                        <tr>
                            <th>2. Số điện thoại:</th>
                            <td>{{ $customerPhone }}</td>
                        </tr>
                        <tr>
                            <th>3. Địa chỉ:</th>
                            <td>{{ $customerAddress }}</td>
                        </tr>
                        <tr>
                            <th>4. Số đơn hàng đã mua:</th>
                            <td>
                               {{ $purchasedProducts->groupBy(fn($i) => $i->order_code2 ?? $i->order_code1)->count() }}
                            </td>
                        </tr>
                        <tr>
                            <th>5. Sản phẩm đã mua:</th>
                            <td>
                                @if($purchasedProducts->isEmpty())
                                Không tìm thấy đơn hàng nào gắn với số điện thoại này.
                                @else
                                @foreach($purchasedProducts as $item)
                                @php
                                $months = (int) ($item->month ?? 0);
                                $purchaseDate = $item->order_created_at ? \Carbon\Carbon::parse($item->order_created_at)
                                : null;
                                $warrantyEnd = $purchaseDate && $months > 0 ? $purchaseDate->copy()->addMonths($months)
                                : null;
                                $orderCode = $item->order_code2 ?? $item->order_code1 ?? 'N/A';
                                @endphp
                                + [{{ $orderCode }}] {{ $item->product_name ?? 'N/A' }} - Mã bảo hành:
                                <span class="text-danger bold">
                                    {{ $item->warranty_code ?? 'Chưa kích hoạt' }}
                                </span>
                                @if($warrantyEnd)
                                - Hạn bảo hành: <span class="text-primary">{{ $warrantyEnd->format('d/m/Y') }}</span>
                                @endif
                                <br>
                                @endforeach
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="col-12 col-lg-6">
    <div class="card h-100">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Lịch sử bảo hành / sửa chữa</h5>
        </div>
        <div class="card-body">
            <div class="timeline">
                @if(isset($repairHistory) && count($repairHistory) > 0)
                @foreach($repairHistory as $item)
                <div class="timeline-item">
                    <div class="timeline-header d-flex justify-content-between align-items-center">
                        <p>
                            <strong>Ngày tiếp nhận:</strong>
                            @if(isset($item->warrantyRequest->received_date))
                            {{ \Carbon\Carbon::parse($item->warrantyRequest->received_date)->format('d/m/Y') }}
                            @else
                            N/A
                            @endif
                            - <strong>Tên sản phẩm:</strong> {{ $item->product_name ?? ($item->warrantyRequest->product
                            ?? 'N/A') }}
                            @if(isset($item->serial_number) || isset($item->warrantyRequest->serial_number))
                            - <strong>Mã bảo hành:</strong> {{ $item->serial_number ??
                            $item->warrantyRequest->serial_number }}
                            @endif
                        </p>
                        <button class="btn btn-link toggle-details">▼</button>
                    </div>
                    <div class="timeline-details" style="overflow: hidden; height: 0;">
                        <p><strong>Loại lỗi gặp phải:</strong> {{ $item->error_type ?? 'N/A' }}</p>
                        <p><strong>Phương án xử lý:</strong> {{ $item->solution ?? 'N/A' }}</p>
                        <p><strong>Linh kiện thay thế:</strong> {{ $item->replacement ?? 'N/A' }}</p>
                        <p><strong>Người tiếp nhận:</strong> {{ $item->staff_received ??
                            ($item->warrantyRequest->staff_received ?? 'N/A') }}</p>
                    </div>
                </div>
                @endforeach
                @else
                <p>Chưa có lịch sử bảo hành / sửa chữa liên quan đến số điện thoại này.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="col-12 mt-4">
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
                                {{ $request->received_date ?
                                \Carbon\Carbon::parse($request->received_date)->format('d/m/Y') : 'N/A' }}
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

<script>
    $(document).ready(function() {
        $('.timeline-header, .timeline-details').click(function() {
            toggleTimelineDetails($(this).closest('.timeline-item'));
        });
    });

    function toggleTimelineDetails(timelineItem) {
        const details = timelineItem.find('.timeline-details');

        $('.timeline-details').not(details).animate({
            height: 0
        }, 10);
        $('.toggle-details').not(timelineItem.find('.toggle-details')).text('▼');

        if (details.height() > 0) {
            details.animate({
                height: 0
            }, 10);
            timelineItem.find('.toggle-details').text('▼');
        } else {
            const fullHeight = details.prop('scrollHeight');
            details.animate({
                height: fullHeight
            }, 10);
            timelineItem.find('.toggle-details').text('▲');
        }
    }
</script>