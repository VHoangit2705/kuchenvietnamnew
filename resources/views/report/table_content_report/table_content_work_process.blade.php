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
        @php
            // Tính toán các giá trị một lần cho mỗi dòng
            $tongTiepNhan = $item->tong_tiep_nhan ?? 0;
            $dangSuaChua = $item->dang_sua_chua ?? 0;
            $choKhachHangPhanHoi = $item->cho_khach_hang_phan_hoi ?? 0;
            $quaHan = $item->qua_han ?? 0;
            $daHoanTat = $item->da_hoan_tat ?? 0;
            
            // Tính tỉ lệ % cho từng trạng thái
            $dangSuaChuaPercent = $tongTiepNhan > 0 ? round(($dangSuaChua / $tongTiepNhan) * 100, 2) : 0;
            $choKhachHangPhanHoiPercent = $tongTiepNhan > 0 ? round(($choKhachHangPhanHoi / $tongTiepNhan) * 100, 2) : 0;
            $quaHanPercent = $tongTiepNhan > 0 ? round(($quaHan / $tongTiepNhan) * 100, 2) : 0;
            $daHoanTatPercent = $tongTiepNhan > 0 ? round(($daHoanTat / $tongTiepNhan) * 100, 2) : 0;
        @endphp
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->branch ?? 'N/A' }}</td>
            <td class="shorten-text" data-bs-toggle="tooltip">{{ $item->staff_received ?? 'N/A' }}</td>
            <td class="text-center">
                <span class="badge bg-primary">{{ $tongTiepNhan }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-secondary">{{ number_format($item->phan_tram_chi_nhanh ?? 0, 2) }}%</span>
            </td>
            <td class="text-center">
                <span class="badge bg-warning text-dark">{{ $dangSuaChua }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-warning text-dark">{{ number_format($dangSuaChuaPercent, 2) }}%</span>
            </td>
            <td class="text-center">
                <span class="badge bg-info text-dark">{{ $choKhachHangPhanHoi }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-info text-dark">{{ number_format($choKhachHangPhanHoiPercent, 2) }}%</span>
            </td>
            <td class="text-center">
                <span class="badge bg-danger">{{ $quaHan }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-danger">{{ number_format($quaHanPercent, 2) }}%</span>
            </td>
            <td class="text-center">
                <span class="badge bg-success">{{ $daHoanTat }}</span>
            </td>
            <td class="text-center">
                <span class="badge bg-success">{{ number_format($daHoanTatPercent, 2) }}%</span>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="13" class="text-center">Không có dữ liệu thống kê</td>
        </tr>
    @endforelse
</tbody>

