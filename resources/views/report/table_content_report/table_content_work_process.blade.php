<thead class="table-dark">
    <tr class="text-center">
        <th class="align-middle">STT</th>
        <th class="align-middle" style="min-width: 150px;">Chi nhánh</th>
        <th class="align-middle" style="min-width: 150px;">Tên kỹ thuật viên</th>
        <th class="align-middle" style="min-width: 120px;">Tổng tiếp nhận</th>
        <th class="align-middle" style="min-width: 120px;">% so với CN</th>
        <th class="align-middle" style="min-width: 120px;">Đang sửa chữa</th>
        <th class="align-middle" style="min-width: 100px;">Đang sửa chữa %</th>
        <th class="align-middle" style="min-width: 150px;">Chờ KH phản hồi</th>
        <th class="align-middle" style="min-width: 120px;">Chờ KH phản hồi %</th>
        <th class="align-middle" style="min-width: 100px;">Quá hạn</th>
        <th class="align-middle" style="min-width: 100px;">Quá hạn %</th>
        <th class="align-middle" style="min-width: 120px;">Đã hoàn tất</th>
        <th class="align-middle" style="min-width: 120px;">Đã hoàn tất %</th>
    </tr>
</thead>

<tbody>
    @forelse ($workProcessData as $item)
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->branch ?? 'N/A' }}</td>
            <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->staff_received ?? 'N/A' }}</td>
            <td class="text-center">
                <span class="badge bg-primary">{{ $item->tong_tiep_nhan ?? 0 }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-secondary">{{ number_format($item->phan_tram_chi_nhanh ?? 0, 2) }}%</span>
            </td>
            <td class="text-center">
                <span class="badge bg-warning text-dark">{{ $item->dang_sua_chua ?? 0 }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-warning text-dark">{{ number_format($item->dang_sua_chua_percent ?? 0, 2) }}%</span>
            </td>
            <td class="text-center">
                <span class="badge bg-info text-dark">{{ $item->cho_khach_hang_phan_hoi ?? 0 }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-info text-dark">{{ number_format($item->cho_khach_hang_phan_hoi_percent ?? 0, 2) }}%</span>
            </td>
            <td class="text-center">
                <span class="badge bg-danger">{{ $item->qua_han ?? 0 }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-danger">{{ number_format($item->qua_han_percent ?? 0, 2) }}%</span>
            </td>
            <td class="text-center">
                <span class="badge bg-success">{{ $item->da_hoan_tat ?? 0 }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-success">{{ number_format($item->da_hoan_tat_percent ?? 0, 2) }}%</span>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="13" class="text-center">Không có dữ liệu thống kê</td>
        </tr>
    @endforelse
</tbody>

