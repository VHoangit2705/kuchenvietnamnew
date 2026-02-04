{{-- Error Management Table Component --}}
@props(['errors'])

<table class="table table-hover align-middle mb-0">
    {{-- Header: Đồng bộ màu xanh chủ đạo --}}
    <thead class="bg-primary text-white">
        <tr>
            <th class="py-3 ps-4" style="width: 150px;">Mã Lỗi</th>
            <th class="py-3" style="min-width: 200px;">Tên Lỗi</th>
            <th class="py-3 text-center" style="width: 150px;">Mức độ</th>
            <th class="py-3" style="min-width: 250px;">Mô tả kỹ thuật</th>
            <th class="py-3 text-end pe-4" style="width: 120px;">Thao tác</th>
        </tr>
    </thead>
    <tbody class="bg-white">
        @forelse($errors as $error)
            <tr>
                {{-- Mã lỗi: Font Monospace để giống giao diện code --}}
                <td class="ps-4">
                    <span class="font-monospace text-white fw-bold text-primary bg-primary bg-opacity-10 px-2 py-1 rounded border border-primary border-opacity-25">
                        {{ $error->error_code }}
                    </span>
                </td>
                
                {{-- Tên lỗi: Đậm --}}
                <td class="fw-bold text-dark">
                    {{ $error->error_name }}
                </td>
                
                {{-- Mức độ: Badge đẹp hơn, có icon --}}
                <td class="text-center">
                    @if($error->severity == 'critical')
                        <span class="badge text-white rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2">
                            <i class="bi bi-x-octagon-fill me-1"></i>Nghiêm trọng
                        </span>
                    @elseif($error->severity == 'common')
                        <span class="badge text-white rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py- 2">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Phổ biến
                        </span>
                    @else
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">
                            <i class="bi bi-info-circle-fill me-1"></i>Bình thường
                        </span>
                    @endif
                </td>
                
                {{-- Mô tả --}}
                <td class="text-muted small">
                    {{ Str::limit($error->description, 60) }}
                </td>

                {{-- Thao tác: Nút tròn chuẩn, icon căn giữa --}}
                <td class="text-end pe-4">
                    <div class="d-flex justify-content-end gap-2">
                        {{-- Nút Sửa --}}
                        <a href="{{ route('warranty.document.errors.edit', $error->id) }}" 
                           class="btn btn-outline-primary rounded-circle d-flex justify-content-center align-items-center" 
                           style="width: 32px; height: 32px;"
                           title="Chỉnh sửa" data-bs-toggle="tooltip">
                            <i class="bi bi-pencil-fill"></i>
                        </a>

                        {{-- Nút Xóa --}}
                        <button type="button" 
                                class="btn btn-outline-danger rounded-circle d-flex justify-content-center align-items-center btn-delete-error" 
                                style="width: 32px; height: 32px;"
                                data-id="{{ $error->id }}" 
                                data-code="{{ $error->error_code }}"
                                title="Xóa mã lỗi" data-bs-toggle="tooltip">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center text-muted">
                        <i class="bi bi-bug fs-1 opacity-50 mb-2"></i>
                        <p class="mb-0 fw-bold">Chưa có mã lỗi nào</p>
                        <small>Danh sách mã lỗi cho model này đang trống.</small>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
