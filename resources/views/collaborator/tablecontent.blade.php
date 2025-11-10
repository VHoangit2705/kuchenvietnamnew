<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>STT</th>
                <th style="min-width: 130px;">Họ tên</th>
                <th style="min-width: 90px;">Ngày sinh</th>
                <th style="min-width: 90px;">Số ĐT</th>
                <th style="min-width: 150px;">Phường/Xã</th>
                <th style="min-width: 150px;">Quận/Huyện</th>
                <th style="min-width: 150px;">Tỉnh/TP</th>
                <th style="min-width: 200px;">Địa chỉ</th>
                <th style="min-width: 150px;">Ngân hàng</th>
                <th style="min-width: 120px;">Chi nhánh</th>
                <th style="min-width: 120px;">Số tài khoản</th>
                <th style="min-width: 95px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
            <tr>
                <td class="text-center">{{ $loop->iteration}}</td>
                <td>{{ $item->full_name }}</td>
                <td class="text-center">{{ $item->date_of_birth ? \Carbon\Carbon::parse($item->date_of_birth)->format('d/m/Y') : '' }}</td>
                <td class="text-center">{{ $item->phone }}</td>
                <td>{{ $item->ward }}</td>
                <td>{{ $item->district }}</td>
                <td>{{ $item->province }}</td>
                <td>{{ $item->address }}</td>
                <td data-bank-name="{{ $item->bank_name ?? '' }}">
                    <span class="bank-name-text">{{ $item->bank_name ?? '' }}</span>
                    <img class="bank-logo ms-2" alt="logo ngân hàng" style="height: 30px; vertical-align: middle; display: none;">
                </td>
                <td>{{ $item->chinhanh ?? '' }}</td>
                <td>{{ $item->sotaikhoan ?? '' }}</td>
                <td>
                    @if ($item->id != 1)
                    <button class="btn btn-warning btn-sm edit-row" data-bs-toggle="modal" data-id="{{ $item->id }}" data-bs-target="#addCollaboratorModal">Sửa</button>
                    <button class="btn btn-danger btn-sm delete-row" onclick="Delete({{ $item->id }})">Xóa</button>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="13" class="text-center">Không có dữ liệu</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <!--  -->
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

<script src="{{ asset('js/collaborator/tablecontent.js') }}"></script>