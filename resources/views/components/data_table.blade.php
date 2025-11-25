@props(['data'])

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>STT</th>
                <th>Số phiếu</th>
                <th>Seri/Model</th>
                <th>Khách hàng</th>
                <th>Số điện thoại</th>
                <th>Nhân viên</th>
                <th>Ngày tiếp nhận</th>
                <th>Ngày hẹn trả</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
            <tr>
                <td class="text-center">{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}</td>
                <td class="text-center" style="min-width: 80px;">{{ $item->id }}</td>
                <td class="shorten-text" style="min-width: 150px;" data-bs-toggle="tooltip" title="{{ $item->serial_number . ' / ' . $item->product }}">
                    {{ Str::limit($item->serial_number . ' / ' . $item->product, 50, '...') }}
                </td>
                <td style="min-width: 150px;">{{ $item->full_name }}</td>
                <td class="text-center" style="min-width: 150px;">{{ $item->phone_number }}</td>
                <td style="min-width: 150px;">{{ $item->staff_received }}</td>
                <td class="text-center" style="min-width: 150px;">{{ \Carbon\Carbon::parse($item->received_date)->format('d/m/Y') }}</td>
                <td class="text-center" style="min-width: 150px;">{{ \Carbon\Carbon::parse($item->return_date)->format('d/m/Y') }}</td>
                <td class="text-center" style="min-width: 150px;">
                    <span style="font-size: 16px; cursor: pointer;" class="badge @if($item->status == 'Đã hoàn tất') bg-success @elseif($item->status == 'Chờ KH phản hồi') bg-primary @else bg-warning text-dark @endif"
                        @if(($item->status != 'Đã hoàn tất'&& session('user') == $item->staff_received) || session('position') == 'admin' || session('position') == 'quản trị viên')
                        onclick="showStatusModal({{ $item->id }}, '{{ $item->status }}', '{{ $item->type }}')"
                        @else
                        onclick="showError()"
                        @endif>
                        {{ $item->status }}
                    </span>
                </td>
                <td>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <a href="{{ route('warranty.detail', ['id' => $item->id]) }}" class="btn d-flex align-items-center justify-content-center" style="padding: 2px 4px; height: 25px;" title="Cập nhật ca sửa chữa">
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
        @if ($data->total() > 30)
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
<style>
    .table td:last-child,
    .table th:last-child {
        width: 1%;
        white-space: nowrap;
        padding-left: 0.25rem;
        padding-right: 0.25rem;
    }
</style>
