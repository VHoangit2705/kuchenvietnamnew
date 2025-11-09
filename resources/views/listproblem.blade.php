<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách lỗi</title>
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- jQuery UI -->
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <!-- CSS -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
        <!-- JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <h3 class="mt-2 text-center">Danh sách yêu cầu hỗ trợ</h3>
    </div>
    <div class="container-fluid d-flex flex-column justify-content-start">
        <div class="table-container">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr class="text-center">
                        <th class="align-middle">STT</th>
                        <th class="align-middle">Tên lỗi</th>
                        <th class="align-middle">Mô tả</th>
                        <th class="align-middle">Người gửi</th>
                        <th class="align-middle">Ngày gửi</th>
                        <th class="align-middle">Người xử lý</th>
                        <th class="align-middle">Ngày xủ lý</th>
                        <th class="align-middle">Phản hồi</th>
                        <th class="align-middle">Trạng thái</th>
                        <th class="align-middle">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse  ($data as $item)
                    <tr>
                        <td class="shorten-text text-center">{{ $loop->iteration }}</td>
                        <td class="shorten-text">{{ $item->error_name }}</td>
                        <td class="shorten-text">{{ $item->description }}</td>
                        <td class="shorten-text">{{ $item->create_by }}</td>
                        <td class="shorten-text text-center" data-bs-toggle="tooltip">
                            {{ \Carbon\Carbon::parse($item->create_at)->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="shorten-text">{{ $item->create_by }}</td>
                        <td class="shorten-text text-center" data-bs-toggle="tooltip">
                        @if($item->update_at)
                            {{ \Carbon\Carbon::parse($item->update_at)->format('d/m/Y H:i:s') }}
                        @else
                            
                        @endif
                        </td>
                        <td class="shorten-text">{{ $item->solution }}</td>
                        <td class="shorten-text text-center">
                            <span class="px-2 py-1 rounded  {{ $item->status === 0 ? 'bg-warning' : 'bg-success' }}">
                                {{ $item->status === 0 ? 'Chưa cập nhật' : 'Đã cập nhật' }}
                            </span>
                        </td>
                        <td class="shorten-text text-center">
                            <a class="btn btn-info btn-sm me-1"  data-bs-toggle="tooltip" onclick="redirectToDetail({{ $item->id }})" data-bs-placement="top"  title="Xem/Chỉnh sửa">
                                <img src="{{ asset('icons/edit.png') }}" alt="view icon" style="width: 16px; height: 16px;">
                            </a>
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
    </div>
    <script>
        // Pass data to JavaScript
        window.detailProblemRoute = @json(route('detailproblem'));
    </script>
    <script src="{{ asset('js/listproblem.js') }}"></script>
</body>
</html>