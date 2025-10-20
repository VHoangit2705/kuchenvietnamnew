<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>STT</th>
                <th style="min-width: 160px;">Mã đơn/Serial</th>
                <th style="min-width: 100px;">Ngày tạo</th>
                <th style="min-width: 150px;">Kho</th>
                <th style="min-width: 150px;">Đại lý</th>
                <th style="min-width: 100px;">SĐT đại lý</th>
                <th style="min-width: 150px;">Khách hàng</th>
                <th style="min-width: 90px;">SĐT KH</th>
                <th style="min-width: 200px;">Sản phẩm mua</th>
                <th style="min-width: 150px;">CTV lắp đặt</th>
                <th style="min-width: 150px;">Chi phí lắp đặt</th>
                <th style="min-width: 150px;">Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
            @php
            $code = $item->order->order_code2 ?? $item->serial_number ?? $item->order_code;
            $zone = $item->order->zone ?? $item->zone ?? '';
            $created_at = $item->order->created_at ?? $item->received_date ?? $item->created_at;
            $statusInstall = $item->order->status_install ?? $item->status_install;
            $type = $item->VAT ? 'donhang' : ($item->warranty_end ? 'baohanh' : 'danhsach');
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration}}</td>
                <td>{{$code}}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($created_at)->format('d/m/Y') }}</td>
                <td class="text-center">{{ $zone }}</td>
                <td>{{ $item->order->agency_name ?? $item->agency_name ?? '' }}</td>
                <td class="text-center">{{ $item->order->agency_phone ?? $item->agency_phone ?? '' }}</td>
                <td>{{ $item->order->customer_name ?? $item->full_name }}</td>
                <td>{{ $item->order->customer_phone ?? $item->phone_number }}</td>
                <td>{{ $item->product_name ?? $item->product ?? 'Không xác định' }}</td>
                <td>{{ $item->order->collaborator->full_name ?? $item->collaborator->full_name ?? '' }}</td>
                <td class="text-center">{{ number_format($item->order->install_cost ?? $item->install_cost, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($statusInstall == null || $statusInstall == 0)
                    <span style="font-size: 13px;" class=" badge bg-secondary">Chưa điều phối</span>
                    @elseif($statusInstall == 1)
                    <span style="font-size: 13px;" class="badge bg-warning text-dark">Đã điều phối</span>
                    @elseif($statusInstall == 3)
                    <span style="font-size: 13px;" class="badge bg-info">Đã thanh toán</span>
                    @else
                    <span style="font-size: 13px;" class="badge bg-success">Hoàn thành</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <a href="{{ route('dieuphoi.detail', ['id' => $item->id, 'type' => $type]) }}"
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