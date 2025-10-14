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
                <th style="min-width: 95px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
            <tr>
                <td class="text-center">{{ $loop->iteration}}</td>
                <td>{{ $item->full_name }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($item->date_of_birth)->format('d/m/Y') }}</td>
                <td class="text-center">{{ $item->phone }}</td>
                <td>{{ $item->ward }}</td>
                <td>{{ $item->district }}</td>
                <td>{{ $item->province }}</td>
                <td>{{ $item->address }}</td>
                <td>
                    <button class="btn btn-warning btn-sm edit-row" data-bs-toggle="modal" data-id="{{ $item->id }}" data-bs-target="#addCollaboratorModal">Sửa</button>
                    <button class="btn btn-danger btn-sm delete-row" onclick="Delete({{ $item->id }})">Xóa</button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center">Không có dữ liệu</td>
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

<script>
    function Delete(id) {
        Swal.fire({
            title: 'Xác nhận xoá',
            text: "Bạn có chắc chắn muốn xoá bản ghi này không?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xoá',
            cancelButtonText: 'Huỷ'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('ctv.delete', ':id') }}".replace(':id', id),
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Notification('success', response.message, 1000, false);
                        $.get("{{ route('ctv.getlist') }}", function(html) {
                            $('#tabContent').html(html);
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Lỗi', 'Có lỗi xảy ra khi xoá.', 'error');
                        console.error(xhr.responseText);
                    }
                });
            }
        });
    }

    $(document).on('click', '.edit-row', function() {
        const button = $(this);
        const id = button.data('id');
        $.ajax({
            url: '{{ route("ctv.getbyid") }}',
            type: 'POST',
            data: {
                id: id
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                const item = response.data.collaborator;
                const province = response.data.provinces;
                const district = response.data.districts;
                const ward = response.data.wards;
                district.forEach(function(item) {
                    $('#districtForm').append('<option value="' + item.district_id + '">' + item.name + '</option>');
                });
                ward.forEach(function(item) {
                    $('#wardForm').append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                });
                $('#tieude').text("Cập nhật cộng tác viên");
                $('#hoantat').text('Cập nhật');
                $('#full_nameForm').val(item.full_name);
                $('#date_of_birth').val(formatDateToInput(item.date_of_birth));
                $('#phoneForm').val(item.phone);
                $('#provinceForm').val(item.province_id);
                $('#districtForm').val(item.district_id);
                $('#wardForm').val(item.ward_id);
                $('#address').val(item.address);
                $('#id').val(item.id);
                
            },
            error: function(xhr) {
                alert('Có lỗi xảy ra khi lấy dữ liệu cộng tác viên.');
                console.log(xhr.responseText);
            }
        });
    });
</script>