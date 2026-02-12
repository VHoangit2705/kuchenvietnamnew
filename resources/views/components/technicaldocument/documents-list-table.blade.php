{{-- Documents List Table Component --}}
@props(['documents'])

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            {{-- Header: Nền xanh đậm, chữ trắng --}}
            <thead class="bg-primary text-white">
                <tr>
                    <th class="py-3 ps-4" style="width: 60px;">#</th>
                    <th class="py-3 text-nowrap">Phân loại</th>
                    <th class="py-3" style="min-width: 250px;">Tiêu đề tài liệu</th>
                    <th class="py-3 text-center" style="width: 100px;">Version</th>
                    <th class="py-3 text-center" style="width: 160px;">Trạng thái</th>
                    <th class="py-3 text-end pe-4" style="width: 180px;">Thao tác</th>
                </tr>
            </thead>

            <tbody class="bg-white">
                @forelse($documents as $doc)
                    <tr>
                        {{-- STT: Đậm, màu xám --}}
                        <td class="ps-4 fw-bold text-secondary">{{ $loop->iteration }}</td>

                        {{-- Phân loại: Badge có viền --}}
                        <td>
                            <span
                                class="badge rounded-pill bg-light text-dark border border-secondary border-opacity-25 px-3 py-2">
                                {{ $doc->doc_type }}
                            </span>
                        </td>

                        {{-- Tiêu đề: Link đậm, mô tả nhỏ phía dưới --}}
                        <td>
                            <div class="d-flex flex-column">
                                <a href="{{ route('warranty.document.documents.show', $doc->id) }}"
                                    class="text-dark text-decoration-none fw-bold hover-primary">
                                    {{ $doc->title }}
                                </a>
                                @if($doc->description)
                                    <small class="text-muted mt-1 text-truncate" style="max-width: 300px;">
                                        {{ Str::limit($doc->description, 60) }}
                                    </small>
                                @endif
                            </div>
                        </td>

                        {{-- Version: Font Monospace --}}
                        <td class="text-center">
                            @php
                                $latestVer = $doc->documentVersions?->sortByDesc('id')->first();
                            @endphp
                            @if($latestVer)
                                <span
                                    class="badge text-dark bg-info bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill font-monospace">
                                    v{{ $latestVer->version }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        {{-- Trạng thái: Badge màu sắc rõ ràng + Icon --}}
                        <td class="text-center">
                            @if($doc->status === 'active')
                                <span
                                    class="badge text-white bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">
                                    <i class="bi bi-check-circle-fill me-1"></i>Hoạt động
                                </span>
                            @elseif($doc->status === 'deprecated')
                                <span
                                    class="badge text-white bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Lỗi thời
                                </span>
                            @else
                                <span
                                    class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3">
                                    {{ $doc->status }}
                                </span>
                            @endif
                        </td>

                        {{-- Thao tác: Nút tròn icon căn giữa --}}
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                {{-- Nút Xem --}}
                                <a href="{{ route('warranty.document.documents.show', $doc->id) }}"
                                    class="btn btn-outline-secondary rounded-circle d-flex justify-content-center align-items-center"
                                    style="width: 32px; height: 32px;" title="Xem chi tiết" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye-fill"></i>
                                </a>

                                @can('technical_document.manage')
                                    {{-- Nút Sửa --}}
                                    <a href="{{ route('warranty.document.documents.edit', $doc->id) }}"
                                        class="btn btn-outline-primary rounded-circle d-flex justify-content-center align-items-center"
                                        style="width: 32px; height: 32px;" title="Chỉnh sửa" data-bs-toggle="tooltip">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                @endcan

                                {{-- Nút Chia sẻ --}}
                                @auth
                                    @if($latestVer)
                                        <button type="button"
                                            class="btn btn-outline-info rounded-circle d-flex justify-content-center align-items-center btn-share-doc"
                                            style="width: 32px; height: 32px;" data-version-id="{{ $latestVer->id }}"
                                            data-doc-title="{{ $doc->title }}" title="Chia sẻ" data-bs-toggle="tooltip">
                                            <i class="bi bi-share-fill"></i>
                                        </button>
                                    @endif
                                @endauth

                                @can('technical_document.manage')
                                    {{-- Nút Xóa (Form Wrapper) --}}
                                    <form action="{{ route('warranty.document.documents.destroy', $doc->id) }}" method="post"
                                        class="d-inline form-delete-document" data-doc-title="{{ $doc->title }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-outline-danger rounded-circle d-flex justify-content-center align-items-center"
                                            style="width: 32px; height: 32px;" title="Xóa vĩnh viễn" data-bs-toggle="tooltip">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    {{-- Empty State --}}
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center text-muted">
                                <div class="bg-light rounded-circle p-4 mb-3">
                                    <i class="bi bi-folder-x fs-1 opacity-50 text-secondary"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Chưa có tài liệu nào</h6>
                                <small>Danh sách tài liệu đang trống.</small>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>