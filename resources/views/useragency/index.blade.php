@extends('layout.layout')

@section('content')
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-1 {
            background-color: #198754;
            color: #fff;
        }

        .status-0 {
            background-color: #dc3545;
            color: #fff;
        }

        .status-2 {
            background-color: #dc3545;
            color: #fff;
        }

        .verified-badge {
            background-color: #0d6efd;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
        }

        .nav-tabs .nav-link.active {
            background-color: #666666 !important;
            color: #ffffff !important;
            border-color: #666666 #666666 transparent !important;
            font-weight: bold;
        }

        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }
    </style>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-people me-2"></i>Quản lý tài khoản đại lý
                </h4>
                <a href="{{ route('useragency.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Tạo tài khoản mới
                </a>
            </div>
            <div class="card-body">
                <!-- Form tìm kiếm -->
                <form method="GET" action="{{ route('useragency.index') }}" id="searchForm">
                    <div class="row mb-3">
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Số điện thoại (Username)</label>
                            <input type="text" name="username" class="form-control" placeholder="Nhập số điện thoại"
                                value="{{ request('username') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Họ tên</label>
                            <input type="text" name="fullname" class="form-control" placeholder="Nhập họ tên"
                                value="{{ request('fullname') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Tên đại lý</label>
                            <input type="text" name="agency_name" class="form-control" placeholder="Nhập tên đại lý"
                                value="{{ request('agency_name') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">SĐT đại lý</label>
                            <input type="text" name="agency_phone" class="form-control" placeholder="Nhập SĐT đại lý"
                                value="{{ request('agency_phone') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Kích hoạt</option>
                                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Vô hiệu hóa</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small text-muted">Xác minh</label>
                            <select name="verified" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>Đã xác minh
                                </option>
                                <option value="0" {{ request('verified') == '0' ? 'selected' : '' }}>Chưa xác minh
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search me-1"></i>Tìm kiếm
                            </button>
                            <a href="{{ route('useragency.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabs theo trạng thái -->
    <div class="container-fluid mt-3">
        <ul class="nav nav-tabs" id="statusTabs">
            <li class="nav-item">
                <a class="nav-link {{ !request('status') && !request('verified') ? 'active' : '' }}"
                    href="{{ route('useragency.index') }}">
                    Tất cả <span class="badge bg-secondary">({{ $counts['all'] }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('verified') == '1' ? 'active' : '' }}"
                    href="{{ route('useragency.index', array_merge(request()->except(['verified', 'page']), ['verified' => '1'])) }}">
                    Đã xác minh <span class="badge bg-success">({{ $counts['verified'] }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('verified') == '0' ? 'active' : '' }}"
                    href="{{ route('useragency.index', array_merge(request()->except(['verified', 'page']), ['verified' => '0'])) }}">
                    Chưa xác minh <span class="badge bg-warning">({{ $counts['unverified'] }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') == '1' ? 'active' : '' }}"
                    href="{{ route('useragency.index', array_merge(request()->except(['status', 'page']), ['status' => '1'])) }}">
                    Kích hoạt <span class="badge bg-info">({{ $counts['active'] }})</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') == '2' ? 'active' : '' }}"
                    href="{{ route('useragency.index', array_merge(request()->except(['status', 'page']), ['status' => '2'])) }}">
                    Vô hiệu hóa <span class="badge bg-danger">({{ $counts['inactive'] }})</span>
                </a>
            </li>
        </ul>

        <!-- Bảng dữ liệu -->
        <div class="card mt-3">
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="min-width: 40px;">STT</th>
                                <th style="min-width: 180px;">Tên đại lý</th>
                                <th style="min-width: 130px;">SĐT đại lý</th>
                                <th style="min-width: 220px;">Địa chỉ đại lý</th>
                                <th style="min-width: 150px;">Ngân hàng</th>
                                <th style="min-width: 150px;">Số tài khoản</th>
                                <th style="min-width: 150px;">Chi nhánh</th>
                                <th style="min-width: 130px;">Căn cước công dân</th>
                                <th style="min-width: 110px;">Trạng thái tài khoản</th>
                                <th style="min-width: 110px;">Xác minh</th>
                                <th style="min-width: 140px;">Ngày tạo TK</th>
                                <th style="min-width: 140px;">Ngày xác minh</th>
                                <th style="min-width: 150px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $index => $user)
                                <tr>
                                    <td>{{ $users->firstItem() + $index }}</td>
                                    
                                    {{-- Tên đại lý: ưu tiên lấy từ bảng agency, nếu chưa gán thì fallback fullname --}}
                                    <td>
                                        @if ($user->agency)
                                            <strong>{{ $user->agency->name ?? '-' }}</strong>
                                        @else
                                            {{ $user->fullname ?? '-' }}
                                        @endif
                                    </td>

                                    {{-- SĐT đại lý (phone trong agency hoặc username) --}}
                                    <td>
                                        @if ($user->agency)
                                            {{ $user->agency->phone ?? '-' }}
                                        @else
                                            {{ $user->username ?? '-' }}
                                        @endif
                                    </td>

                                    {{-- Địa chỉ đại lý --}}
                                    <td>
                                        @if ($user->agency && $user->agency->address)
                                            <div style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                title="{{ $user->agency->address }}">
                                                {{ $user->agency->address }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Ngân hàng --}}
                                    <td>
                                        @if ($user->agency && $user->agency->bank_name_agency)
                                            {{ $user->agency->bank_name_agency }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Số tài khoản --}}
                                    <td>
                                        @if ($user->agency && $user->agency->sotaikhoan)
                                            {{ $user->agency->sotaikhoan }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Chi nhánh --}}
                                    <td>
                                        @if ($user->agency && $user->agency->chinhanh)
                                            {{ $user->agency->chinhanh }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- CCCD --}}
                                    <td>
                                        @if ($user->agency && $user->agency->cccd)
                                            {{ $user->agency->cccd }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Trạng thái tài khoản --}}
                                    <td>
                                        <span class="status-badge status-{{ $user->status }}">
                                            @if ($user->status == 1)
                                                Kích hoạt
                                            @elseif($user->status == 2)
                                                Vô hiệu hóa
                                            @else
                                                Chưa xác minh
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        @if ($user->isVerified())
                                            <span class="verified-badge">
                                                <i class="bi bi-check-circle me-1"></i>Đã xác minh
                                            </span>
                                        @else
                                            <span class="text-muted">Chưa xác minh</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($user->created_at)
                                            {{ $user->created_at->format('d/m/Y') }}<br>
                                            <small class="text-muted">{{ $user->created_at->format('H:i') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($user->phone_verified_at)
                                            {{ $user->phone_verified_at->format('d/m/Y') }}<br>
                                            <small
                                                class="text-muted">{{ $user->phone_verified_at->format('H:i') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('useragency.show', $user->id) }}" class="btn btn-info"
                                                title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('useragency.edit', $user->id) }}" class="btn btn-warning"
                                                title="Chỉnh sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-{{ $user->status == 1 ? 'secondary' : 'success' }}"
                                                onclick="toggleStatus({{ $user->id }})"
                                                title="{{ $user->status == 1 ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                <i
                                                    class="bi bi-{{ $user->status == 1 ? 'x-circle' : 'check-circle' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger"
                                                onclick="deleteUser({{ $user->id }})" title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                        <p class="mt-2 text-muted">Không có dữ liệu</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($users->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $users->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal xác nhận xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa tài khoản đại lý này?</p>
                    <p class="text-danger"><small>Hành động này không thể hoàn tác!</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteUser(id) {
            const form = document.getElementById('deleteForm');
            form.action = '{{ route('useragency.index') }}/' + id;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function toggleStatus(id) {
            if (!confirm('Bạn có chắc chắn muốn thay đổi trạng thái tài khoản này?')) {
                return;
            }

            fetch(`{{ route('useragency.index') }}/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                            document.querySelector('input[name="_token"]')?.value,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(data.message || 'Có lỗi xảy ra!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Có lỗi xảy ra khi cập nhật trạng thái!');
                });
        }

        @if (session('success'))
            toastr.success('{{ session('success') }}');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}');
        @endif
    </script>
@endsection
