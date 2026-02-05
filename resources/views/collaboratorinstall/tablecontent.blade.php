<style>
    .table-container {
        max-height: 75vh;
        overflow-x: auto;
    }

    .table-container thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table-striped > tbody > tr:hover {
        background-color: #afafaf;
        cursor: pointer;
    }

    .table-striped > tbody > tr.highlight-row {
        background-color: #afafaf;
    }
    .table-container.can-grab {
    cursor: grab;
    }

    .table-container.is-grabbing {
    cursor: grabbing;
    }
    .table-dark tr{
        border: 0;
    }
</style>

<div class="table-container">
    <table class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>

                <th>STT</th>
                <th style="min-width: 160px;">Mã đơn/Serial</th>
                <th style="min-width: 100px;">
                    @php
                        $currentTab = $tab ?? 'donhang';
                        if ($currentTab === 'dadieuphoi') {
                            echo 'Ngày điều phối';
                        } elseif ($currentTab === 'dailylapdat') {
                            echo 'Ngày điều phối';
                        } elseif ($currentTab === 'dahoanthanh') {
                            echo 'Ngày hoàn thành';
                        } elseif ($currentTab === 'dathanhtoan') {
                            echo 'Ngày thanh toán';
                        } else {
                            echo 'Ngày tạo';
                        }
                    @endphp
                </th>
                <th style="min-width: 150px;">Kho</th>
                <th style="min-width: 150px;">Đại lý</th>
                <th style="min-width: 100px;">SĐT đại lý</th>
                <th style="min-width: 150px;">Khách hàng</th>
                <th style="min-width: 90px;">SĐT KH</th>
                <th style="min-width: 200px;">Sản phẩm mua</th>
                <th style="min-width: 150px;">Đại lý / CTV lắp đặt</th>
                <th style="min-width: 150px;">Chi phí lắp đặt</th>
                <th style="min-width: 150px;">Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
            @php
            // Xác định type trước
            $type = $item->VAT ? 'donhang' : ($item->warranty_end ? 'baohanh' : 'danhsach');
            
            $isInstallationOrderBaohanh = (isset($item->type) && $item->type === 'baohanh') || 
                                          (isset($item->warranty_requests_id) && $item->warranty_requests_id);
            
            if ($isInstallationOrderBaohanh && isset($item->warranty_requests_id) && $item->warranty_requests_id) {
                $orderCode = $item->order_code ?? '';
                $orderCodeTrimmed = strtoupper(trim($orderCode));
                
                $isNoSerialOrderCode = empty($orderCode) || 
                                      $orderCodeTrimmed === 'HÀNG KHÔNG CÓ MÃ SERI' ||
                                      $orderCodeTrimmed === 'HANG KHONG CO MA SERI' ||
                                      $orderCodeTrimmed === 'BH-' . $item->warranty_requests_id ||
                                      preg_match('/^BH-\d+$/', $orderCodeTrimmed);
                
                if ($isNoSerialOrderCode) {
                    $code = 'HÀNG KHÔNG CÓ MÃ SERI - ' . $item->warranty_requests_id;
                } else {
                    $code = $orderCode;
                }
            } elseif ($type === 'baohanh') {
                $serialNumber = $item->serial_number ?? '';
                $isNoSerial = empty($serialNumber) || 
                              strtoupper(trim($serialNumber)) === 'HÀNG KHÔNG CÓ MÃ SERI' ||
                              strtoupper(trim($serialNumber)) === 'HANG KHONG CO MA SERI';
                
                if ($isNoSerial) {
                    $code = 'HÀNG KHÔNG CÓ MÃ SERI - ' . $item->id;
                } else {
                    $code = $serialNumber;
                }
            } else {
                $code = $item->order->order_code2 ?? $item->order_code ?? 'N/A';
            }
            
            $zone = $item->order->zone ?? $item->zone ?? '';
            $statusInstall = $item->order->status_install ?? $item->status_install;
            $rawInstallCost = $item->order->install_cost ?? $item->install_cost ?? 0;
            // Chỉ xem là đã có đơn lắp đặt (Đại lý/CTV) khi đã điều phối và có chi phí > 0
            $hasInstaller = !is_null($statusInstall) && $statusInstall != 0 && $rawInstallCost > 0;
            
            // Xác định ngày thay đổi trạng thái tùy theo tab
            $statusDate = null;
            $currentTab = $tab ?? 'donhang';
            
            if (in_array($currentTab, ['donhang', 'dieuphoidonhangle', 'dieuphoibaohanh'])) {
                // Các tab đơn hàng chưa điều phối: dùng created_at gốc
                $statusDate = $item->order->created_at ?? $item->received_date ?? $item->created_at;
            } elseif ($currentTab === 'dadieuphoi') {
                // Tab "Đã điều phối": dùng dispatched_at (thời gian chuyển sang status_install = 1)
                $statusDate = $item->dispatched_at ?? null;
            } elseif ($currentTab === 'dailylapdat') {
                // Tab "Đại lý lắp đặt": dùng agency_at (thời gian chuyển sang đại lý lắp đặt)
                $statusDate = $item->agency_at ?? null;
            } elseif ($currentTab === 'dahoanthanh') {
                // Tab "Đã hoàn thành": dùng successed_at (thời gian chuyển sang status_install = 2)
                $statusDate = $item->successed_at ?? null;
            } elseif ($currentTab === 'dathanhtoan') {
                // Tab "Đã thanh toán": dùng paid_at (thời gian chuyển sang status_install = 3)
                $statusDate = $item->paid_at ?? null;
            } else {
                // Fallback: dùng created_at gốc
                $statusDate = $item->order->created_at ?? $item->received_date ?? $item->created_at;
            }
            @endphp
            <tr>

                <td class="text-center">{{ $loop->iteration}}</td>
                {{-- <td>{{$code}}</td> --}}
                <td>{{ $code ?: ' N/A ' }}</td>
                <td class="text-center">
                    {{ ($statusDate ?? null) ? \Carbon\Carbon::parse($statusDate)->format('d-m-Y') : 'N/A' }}
                  </td>
                <td class="text-center">{{ $zone }}</td>
                <td>{{ $item->order->agency_name ?? $item->agency_name ?? '' }}</td>
                <td class="text-center">{{ $item->order->agency_phone ?? $item->agency_phone ?? '' }}</td>
                <td>{{ $item->order->customer_name ?? $item->full_name }}</td>
                <td>{{ $item->order->customer_phone ?? $item->phone_number }}</td>
                <td>{{ $item->product_name ?? $item->product ?? 'Không xác định' }}</td>
                {{-- Đại lý / CTV lắp đặt: chỉ hiển thị khi đã điều phối và có chi phí lắp đặt > 0 --}}
                <td>
                    @if ($hasInstaller)
                        @php
                            $agencyName = $item->order->agency_name ?? $item->agency_name ?? null;
                            $collaboratorName = $item->order->collaborator->full_name ?? $item->collaborator->full_name ?? null;
                        @endphp
                        @if(!empty($agencyName))
                            {{ $agencyName }}
                        @elseif(!empty($collaboratorName))
                            {{ $collaboratorName }}
                        @endif
                    @endif
                </td>
                <td class="text-center">{{ number_format($rawInstallCost, 0, ',', '.') }}</td>
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
</div>
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
