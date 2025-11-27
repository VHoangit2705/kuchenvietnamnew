<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>STT</th>
                <th style="min-width: 80px;">Số phiếu</th>
                <th style="min-width: 200px;">Seri/Model</th>
                <th style="min-width: 130px;">Khách hàng</th>
                <th style="min-width: 120px;">Số điện thoại</th>
                <th style="min-width: 200px;">Nhân viên</th>
                <th style="min-width: 120px;">Ngày tiếp nhận</th>
                <th style="min-width: 120px;">Ngày hẹn trả</th>
                <th style="min-width: 100px;">Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}</td>
                    <td class="text-center">{{ $item->id }}</td>
                    <td class="shorten-text" data-bs-toggle="tooltip"
                        title="{{ $item->serial_number . ' / ' . $item->product }}">
                        {{ Str::limit($item->serial_number . ' / ' . $item->product, 50, '...') }}
                    </td>
                    <td>{{ $item->full_name }}</td>
                    <td class="text-center">{{ $item->phone_number }}</td>
                    <td>{{ $item->staff_received }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->received_date)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->return_date)->format('d/m/Y') }} <br>
                    @php
                    $returnDate = \Carbon\Carbon::parse($item->return_date);
                    $today = \Carbon\Carbon::today();
                    @endphp
                    @if ($today->gt($returnDate) && $item->status == 'Đang sửa chữa')
                    <span style="color: red; font-size: 1rem;">
                        quá hạn {{ $today->diffInDays($returnDate, true) }} ngày
                    </span>
                    @endif
                    </td>
                    <td class="text-center">
                        <span style="font-size: 14px; cursor: pointer;"
                            class="badge @if($item->status == 'Đã hoàn tất') bg-success @elseif($item->status == 'Chờ KH phản hồi') bg-secondary @elseif($item->status == 'Đã tiếp nhận') bg-primary @elseif($item->status == 'Đã gửi linh kiện') bg-info @else bg-warning text-dark @endif"
                            @if(($item->status != 'Đã hoàn tất' && session('user') == $item->staff_received) || session('position') == 'admin' || session('position') == 'quản trị viên')
                            onclick="showStatusModal({{ $item->id }}, '{{ $item->status }}', '{{ $item->type }}', false)"
                            @else
                            onclick="showError()"
                            @endif>
                            {{ $item->status }}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <a href="{{ route('warranty.detail', ['id' => $item->id]) }}"
                                class="btn d-flex align-items-center justify-content-center"
                                style="padding: 2px 4px; height: 25px;" title="Cập nhật ca sửa chữa">
                                <img src="{{ asset('icons/edit.png') }}" alt="Edit" style="height: 20px;">
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Không có dữ liệu</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="d-flex justify-content-center mt-3">
        @if ($data->total() > 50)
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    {{-- Nút Trang đầu --}}
                    @if ($data->currentPage() > 1)
                        <li class="page-item">
                            <a class="page-link" href="{{ $data->url(1) }}">Trang đầu</a>
                        </li>
                    @endif

                    {{-- Nút Trang trước --}}
                    @if ($data->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">«</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $data->previousPageUrl() }}">«</a></li>
                    @endif

                    {{-- Số trang --}}
                    @for ($i = max(1, $data->currentPage() - 1); $i <= min($data->currentPage() + 1, $data->lastPage()); $i++)
                        <li class="page-item {{ $i == $data->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $data->url($i) }}">{{ $i }}</a>
                        </li>
                    @endfor

                    {{-- Nút Trang tiếp --}}
                    @if ($data->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $data->nextPageUrl() }}">»</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">»</span></li>
                    @endif

                    {{-- Nút Trang cuối --}}
                    @if ($data->currentPage() < $data->lastPage())
                        <li class="page-item">
                            <a class="page-link" href="{{ $data->url($data->lastPage()) }}">Trang cuối</a>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
</div>