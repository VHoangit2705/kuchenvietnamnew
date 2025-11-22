<thead class="table-dark">
    <tr class="text-center">
        <th class="align-middle">STT</th>
        <th class="align-middle" style="max-width: 70px;">Mã serial</th>
        <th class="align-middle" style="min-width: 200px;">Tên sản phẩm</th>
        <th class="align-middle" style="min-width: 150px;">Chi nhánh</th>
        <th class="align-middle" style="min-width: 100px;">Khách hàng</th>
        <th class="align-middle" style="min-width: 80px;">Điện thoại</th>
        <th class="align-middle" style="min-width: 100px;">Kỹ thuật viên</th>
        <th class="align-middle" style="min-width: 120px;">Ngày tiếp nhận</th>
        <th class="align-middle" style="min-width: 120px;">Lỗi ban đầu</th>
        <th class="align-middle" style="min-width: 120px;">Ngày xuất kho</th>
        <th class="align-middle" style="min-width: 100px;">Bảo hành</th>
        <th class="align-middle" style="min-width: 100px;">Linh kiện</th>
        <th class="align-middle" style="min-width: 80px;">Đơn giá</th>
        <th class="align-middle" style="min-width: 60px;">SL</th>
        <th class="align-middle" style="min-width: 100px;">Thành tiền</th>
        <!--<th class="align-middle" style="min-width: 120px;">KH thanh toán</th>-->
    </tr>
</thead>

<tbody>
    @forelse ($data as $item)
        @php
            $warrantyEnd = \Carbon\Carbon::parse($item->warranty_end);
            $receivedDate = \Carbon\Carbon::parse($item->received_date);
            $isInWarranty = $warrantyEnd->gte($receivedDate);
        @endphp
        <tr>
            <td class="shorten-text text-center">{{ $loop->iteration }}</td>
            <td class="shorten-text" data-bs-toggle="tooltip">
                @if (Str::contains($item->product, ['Máy giặt', 'Máy sấy', 'Máy rửa bát', 'Tủ lạnh']) && !empty($item->serial_thanmay))
                    {{ $item->serial_thanmay }}
                @else
                    {{ $item->serial_number }}
                @endif
            </td>
            <td class="shorten-text" data-bs-toggle="tooltip" title="{{ $item->product }}">
                <a href="{{ route('warranty.detail', ['id' => $item->id]) }}" target="_blank"
                    class="text-decoration-none text-primary">
                    {{ \Illuminate\Support\Str::limit($item->product, 40, '...') }}
                </a>
            </td>
            <td class="shorten-text">{{ $item->branch }}</td>
            <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->full_name }}</td>
            <td class="shorten-text text-center">{{ $item->phone_number }}</td>
            <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->staff_received }}</td>
            <td class="shorten-text text-center" data-bs-toggle="tooltip">
                {{ \Carbon\Carbon::parse($item->received_date)->format('d/m/Y') }}
            </td>
            <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->initial_fault_condition }}</td>
            <td class="shorten-text text-center" data-bs-toggle="tooltip">
                {{ \Carbon\Carbon::parse($item->shipment_date)->format('d/m/Y') }}
            </td>
            <td class="shorten-text text-center {{ $isInWarranty ? 'text-success' : 'text-danger' }}">
                {{ $isInWarranty ? 'Còn hạn BH' : 'Hết hạn BH' }}
            </td>
            <td class="shorten-text" data-bs-toggle="tooltip" title="{{ $item->replacement }}">
                {{ \Illuminate\Support\Str::limit($item->replacement, 40, '...') }}
            </td>
            <td class="shorten-text text-center">{{ number_format($item->replacement_price, 0, ',', '.') }}</td>
            <td class="shorten-text text-center">{{ $item->quantity }}</td>
            <td class="shorten-text text-center">
                {{ number_format($item->replacement_price * $item->quantity, 0, ',', '.') }}
            </td>
            <!--<td class="shorten-text text-center">{{ number_format($item->total, 0, ',', '.') }}</td>-->
        </tr>
    @empty
        <tr>
            <td colspan="14" class="text-center">Không có dữ liệu</td>
        </tr>
    @endforelse
</tbody>
