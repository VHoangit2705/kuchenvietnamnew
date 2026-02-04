<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr class="text-center">
            <th class="align-middle">STT</th>
            <th class="align-middle" style="min-width: 80px;">Số phiếu</th>
            <th class="align-middle" style="min-width: 200px;">Tên sản phẩm</th>
            <th class="align-middle" style="min-width: 80px;">Số lượng</th>
            <th class="align-middle" style="min-width: 100px;">Đã sử dụng</th>
            <th class="align-middle" style="min-width: 100px;">Còn lại</th>
            <th class="align-middle" style="min-width: 180px;">Người tạo</th>
            <th class="align-middle" style="min-width: 80px;">Ngày tạo</th>
            <th class="align-middle" style="min-width: 80px;"></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($lstWarrantyCard as $item)
        @php
            $usedCount = $item->used_count ?? 0;
            $remainingCount = $item->remaining_count ?? $item->quantity;
            $daysRemaining = $item->days_remaining;
            $shouldWarn = $item->should_warn ?? false;
            $usageRate = $item->usage_rate ?? 0;
        @endphp
        <tr>
            <td class="text-center">{{ $loop->iteration + ($lstWarrantyCard->currentPage() - 1) * $lstWarrantyCard->perPage() }}</td>
            <td class="shorten-text text-center" data-bs-toggle="tooltip">{{ $item->id }}</td>
            <td class="shorten-text">{{ $item->product }}</td>
            <td class="shorten-text text-center">{{ $item->quantity }}</td>
            <td class="shorten-text text-center">
                <span class="badge bg-info" style="font-size: 14px; line-height: 1.2">{{ $usedCount }}</span>
            </td>
            <td class="shorten-text text-center">
                @if($remainingCount == 0)
                    {{-- Đã hết tem --}}
                    <div class="d-flex flex-column align-items-center gap-1">
                        <span class="badge bg-dark text-white hover-badge" 
                              style="font-size: 14px; line-height: 1.2; cursor: pointer; transition: all 0.3s ease;"
                              data-bs-toggle="popover"
                              data-bs-trigger="hover"
                              data-bs-placement="top"
                              data-bs-html="true"
                              data-bs-content="<div class='text-start'>
                                  <strong class='text-dark'>🚨 Đã hết tem!</strong><br>
                                  <hr class='my-2'>
                                  <div class='mb-1'><strong>Tổng số tem:</strong> {{ $item->quantity }} tem</div>
                                  <div class='mb-1'><strong>Đã sử dụng:</strong> {{ $usedCount }}/{{ $item->quantity }} tem</div>
                                  <div class='mb-1'><strong>Tỷ lệ đã dùng:</strong> {{ number_format(($usedCount / $item->quantity) * 100, 1) }}%</div>
                                  <div><strong>Ngày tạo:</strong> {{ \Carbon\Carbon::parse($item->create_at)->format('d/m/Y') }}</div>
                              </div>"
                              data-popover-header-class="bg-dark"
                              title="<i class='bi bi-x-circle-fill'></i> Thông tin phiếu">
                            {{ $remainingCount }}
                        </span>
                        <small class="text-danger" style="font-size: 14px;">
                            🚨 Đã hết tem
                        </small>
                    </div>
                @elseif($usedCount == 0)
                    {{-- Chưa có tem nào được kích hoạt --}}
                    <div class="d-flex flex-column align-items-center gap-1">
                        <span class="badge bg-secondary text-white px-3 py-2 hover-badge" 
                              style="font-size: 14px; line-height: 1.2; cursor: pointer; transition: all 0.3s ease;"
                              data-bs-toggle="popover"
                              data-bs-trigger="hover"
                              data-bs-placement="top"
                              data-bs-html="true"
                              data-bs-content="<div class='text-start'>
                                  <strong class='text-secondary'>📦 Chưa kích hoạt tem</strong><br>
                                  <hr class='my-2'>
                                  <div class='mb-1'><strong>Tổng số tem:</strong> {{ $item->quantity }} tem</div>
                                  <div class='mb-1'><strong>Đã sử dụng:</strong> 0 tem</div>
                                  <div class='mb-1'><strong>Còn lại:</strong> {{ $remainingCount }} tem</div>
                                  <div class='mb-1'><strong>Ngày tạo:</strong> {{ \Carbon\Carbon::parse($item->create_at)->format('d/m/Y') }}</div>
                                  <div><strong>Số ngày đã trôi qua:</strong> {{ $item->days_passed ?? 0 }} ngày</div>
                              </div>"
                              data-popover-header-class="bg-secondary"
                              title="<i class='bi bi-info-circle-fill'></i> Thông tin phiếu">
                            {{ $remainingCount }} tem
                        </span>
                        <small class="text-muted" style="font-size: 14px;">
                            Chưa kích hoạt
                        </small>
                    </div>
                @elseif($shouldWarn && $daysRemaining !== null)
                    {{-- Cảnh báo: còn < 5 ngày --}}
                    <div class="d-flex flex-column align-items-center gap-1">
                        <span class="badge bg-danger text-white px-3 py-2 fw-bold hover-badge warning-badge-hover" 
                              style="font-size: 14px; line-height: 1.2; cursor: pointer; transition: all 0.3s ease;"
                              data-bs-toggle="popover"
                              data-bs-trigger="hover"
                              data-bs-placement="top"
                              data-bs-html="true"
                              data-bs-content="<div class='text-start'>
                                  <strong class='text-danger'>⚠️ Cảnh báo sắp hết tem!</strong><br>
                                  <hr class='my-2'>
                                  <div class='mb-1'><strong>Số tem còn lại:</strong> {{ $remainingCount }} tem</div>
                                  <div class='mb-1'><strong>Tốc độ sử dụng:</strong> {{ number_format($usageRate, 2) }} tem/ngày</div>
                                  <div class='mb-1'><strong>Dự đoán hết sau:</strong> {{ number_format($daysRemaining, 1) }} ngày</div>
                                  <div class='mb-1'><strong>Đã sử dụng:</strong> {{ $usedCount }}/{{ $item->quantity }} tem</div>
                                  <div class='mb-1'><strong>Tỷ lệ đã dùng:</strong> {{ number_format(($usedCount / $item->quantity) * 100, 1) }}%</div>
                                  <div><strong>Ngày tạo:</strong> {{ \Carbon\Carbon::parse($item->create_at)->format('d/m/Y') }}</div>
                              </div>"
                              data-popover-header-class="bg-danger"
                              title="<i class='bi bi-exclamation-triangle-fill'></i> Thông tin chi tiết">
                              ⚠️
                            {{ $remainingCount }}
                        </span>
                        <small class="text-danger fw-semibold d-block" style="font-size: 14px; line-height: 1.2;">
                            Sẽ hết sau: {{ number_format($daysRemaining, 1) }} ngày
                        </small>
                    </div>
                @elseif($daysRemaining !== null && $daysRemaining > 0)
                    {{-- Bình thường: còn >= 5 ngày --}}
                    <div class="d-flex flex-column align-items-center gap-1">
                        <span class="badge bg-success text-white px-3 py-2 hover-badge" 
                              style="font-size: 14px; line-height: 1.2; cursor: pointer; transition: all 0.3s ease;"
                              data-bs-toggle="popover"
                              data-bs-trigger="hover"
                              data-bs-placement="top"
                              data-bs-html="true"
                              data-bs-content="<div class='text-start'>
                                  <strong class='text-success'>✅ Tình trạng bình thường</strong><br>
                                  <hr class='my-2'>
                                  <div class='mb-1'><strong>Số tem còn lại:</strong> {{ $remainingCount }} tem</div>
                                  <div class='mb-1'><strong>Tốc độ sử dụng:</strong> {{ number_format($usageRate, 2) }} tem/ngày</div>
                                  <div class='mb-1'><strong>Dự đoán hết sau:</strong> {{ number_format($daysRemaining, 1) }} ngày</div>
                                  <div class='mb-1'><strong>Đã sử dụng:</strong> {{ $usedCount }}/{{ $item->quantity }} tem</div>
                                  <div class='mb-1'><strong>Tỷ lệ đã dùng:</strong> {{ number_format(($usedCount / $item->quantity) * 100, 1) }}%</div>
                                  <div><strong>Ngày tạo:</strong> {{ \Carbon\Carbon::parse($item->create_at)->format('d/m/Y') }}</div>
                              </div>"
                              data-popover-header-class="bg-success"
                              title="<i class='bi bi-check-circle-fill'></i> Thông tin chi tiết">
                            {{ $remainingCount }} tem
                </span>
                        <small class="text-success" style="font-size: 0.7rem;">
                            ~{{ number_format($daysRemaining, 0) }} ngày còn lại
                        </small>
                    </div>
                @else
                    {{-- Trường hợp khác (fallback) --}}
                    <span class="badge bg-secondary" data-bs-toggle="tooltip" 
                          title="Chưa có dữ liệu để dự đoán">
                        {{ $remainingCount }}
                    </span>
                @endif
            </td>
            <td class="shorten-text text-center">{{ $item->create_by }}</td>
            <td class="shorten-text text-center" data-bs-toggle="tooltip">{{ \Carbon\Carbon::parse($item->create_at)->format('d/m/Y') }}</td>
            <td class="shorten-text text-center">
                <div class="d-flex justify-content-center align-items-center gap-2">
                    @if(session('brand') == 'kuchen')
                        <a href="{{ route('warrantycard.detail', ['id' => $item->id]) }}" class="btn btn-info btn-sm align-items-center fw-bold">Xem phiếu</a>
                    @else
                        <a href="{{ route('warrantycard.serial_detail', ['maphieu' => $item->id]) }}" class="btn btn-info btn-sm align-items-center fw-bold">Xem phiếu</a>
                    @endif
                    @if(session('position') == 'admin')
                        <a href="" data-url="{{ route('warrantycard.delete', ['id' => $item->id]) }}"
                            class="btn btn-danger btn-sm d-flex align-items-center justify-content-center gap-1 btn-delete"
                            style="height: 30px;"
                            title="Xóa">
                            Xóa <i class="bi bi-trash3"></i>
                        </a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center">Không có dữ liệu</td>
        </tr>
        @endforelse
    </tbody>
</table>
<link rel="stylesheet" href="{{ asset('css/printwarranty/tablebody.css') }}">
<script src="{{ asset('js/printwarranty/tablebody.js') }}"></script>
