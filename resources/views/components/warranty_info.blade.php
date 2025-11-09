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
                            <td>{{ $warranty->order_product?->order?->customer_name ?? $warranty->full_name }}</td>
                        </tr>
                        <tr>
                            <th>2. Số điện thoại:</th>
                            <td>{{ $warranty->order_product?->order?->customer_phone ?? $warranty->phone_number }}</td>
                        </tr>
                        <tr>
                            <th>3. Địa chỉ:</th>
                            <td>{{ $warranty->order_product?->order?->customer_address ?? $warranty->address }}</td>
                        </tr>
                        <tr>
                            <th>4. Tên đại lý:</th>
                            <td>{{ $warranty->order_product?->order?->agency_name ?? 'N/A' }}</t>
                        </tr>
                        <tr>
                            <th>5. SĐT đại lý:</th>
                            <td>{{ $warranty->order_product?->order?->agency_phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>6. Ngày mua hàng:</th>
                            <td>{{ $warranty->order_product?->order?->created_at ? \Carbon\Carbon::parse($warranty->order_product->order->created_at)->format('d/m/Y') : $warranty->shipment_date->format('d/m/Y') }}
                            </td>
                        </tr>
                        <tr>
                            <th>7. Sản phẩm đã mua:</th>
                            <td>
                                @foreach($lstproduct as $item)
                                @php
                                $months = (int)$item->month;
                                if (!empty($warranty->order_product?->order?->created_at)) {
                                $han = \Carbon\Carbon::parse($warranty->order_product->order->created_at)->addMonths($months);
                                } else {
                                $han = \Carbon\Carbon::parse($warranty->warranty_end);
                                }
                                @endphp
                                + {{ $item->product_name }} - Mã bảo hành:
                                <span class="text-danger bold">
                                    {{ $item->warranty_code ?? '' }}
                                </span>
                                - Hạn bảo hành: <span class="text-primary">{{ $han->format('d/m/Y') }}</span>
                                <br>
                                @endforeach
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
            <h5 class="mb-0">Lịch sử bảo hành</h5>
        </div>
        <div class="card-body">
            <div class="timeline">
                @if(isset($history) && count($history) > 0)
                @foreach($history as $item)
                <div class="timeline-item">
                    <div class="timeline-header d-flex justify-content-between align-items-center">
                        <p><strong>Ngày tiếp nhận:</strong> {{ \Carbon\Carbon::parse($received_date)->format('d/m/Y') ?? 'N/A' }}
                            - <strong>Tên sản phẩm:</strong> {{ $warranty->order_product->product_name ?? 'N/A' }}
                        </p>
                        <button class="btn btn-link toggle-details">▼</button>
                    </div>
                    <div class="timeline-details" style="overflow: hidden; height: 0;">
                        <p><strong>Loại lỗi gặp phải:</strong> {{ $item->error_type ?? 'N/A' }}</p>
                        <p><strong>Phương án xử lý:</strong> {{ $item->solution ?? 'N/A' }}</p>
                        <p><strong>Linh kiên thay thế:</strong> {{ $item->replacement ?? 'N/A' }}</p>
                        <p><strong>Người tiếp nhận:</strong> {{ $received_warranty ?? 'N/A' }}</p>
                    </div>
                </div>
                @endforeach
                @else
                <p>Chưa có lịch sử bảo hành</p>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="col-12 col-lg-6">
    <form action="{{ route('warranty.formcard') }}" method="POST">
        @csrf
        <input type="hidden" name="warranty" value="{{ json_encode($warranty) }}">
        <input type="hidden" name="lstproduct" value="{{ json_encode($lstproduct) }}">
        <button type="submit" class="btn btn-warning text-dark">&#43; Tạo phiếu bảo hành</button>
    </form>
</div>
<script src="{{ asset('js/components/warranty_info.js') }}"></script>