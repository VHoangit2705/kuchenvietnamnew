<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr class="text-center">
            <th class="align-middle">STT</th>
            <th class="align-middle" style="min-width: 80px;">Số phiếu</th>
            <th class="align-middle" style="min-width: 200px;">Tên sản phẩm</th>
            <th class="align-middle" style="min-width: 80px;">Số lượng</th>
            <th class="align-middle" style="min-width: 180px;">Người tạo</th>
            <th class="align-middle" style="min-width: 80px;">Ngày tạo</th>
            <th class="align-middle" style="min-width: 80px;"></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($lstWarrantyCard as $item)
        <tr>
            <td class="text-center">{{ $loop->iteration + ($lstWarrantyCard->currentPage() - 1) * $lstWarrantyCard->perPage() }}</td>
            <td class="shorten-text text-center" data-bs-toggle="tooltip">{{ $item->id }}</td>
            <td class="shorten-text">{{ $item->product }}</td>
            <td class="shorten-text text-center">{{ $item->quantity }}</td>
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
            <td colspan="14" class="text-center">Không có dữ liệu</td>
        </tr>
        @endforelse
    </tbody>
</table>
<script>
    $(document).ready(function() {
        $(".btn-delete").click(function(e) {
            e.preventDefault();
            let url = $(this).data("url");
            let row = $(this).closest("tr");
            Swal.fire({
                title: "Bạn có chắc chắn muốn xóa?",
                text: "Dữ liệu sẽ không thể khôi phục!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Xóa",
                cancelButtonText: "Hủy"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: "DELETE",
                        data: {
                            _token: $('meta[name="csrf-token"]').attr("content")
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire("Đã xóa!", response.message, "success");
                                row.remove();
                            } else {
                                Swal.fire("Lỗi!", response.message, "error");
                            }
                        },
                        error: function() {
                            Swal.fire("Lỗi!", "Không thể kết nối server.", "error");
                        }
                    });
                }
            });
        });
    });
</script>