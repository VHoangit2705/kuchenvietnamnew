<div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="errorTable">
            {{-- Header: Nền xanh đậm, chữ trắng, viết hoa --}}
            <thead class="bg-primary text-white">
                <tr>
                    <th class="py-3 ps-4" style="width: 60px;">#</th>
                    <th class="py-3" style="min-width: 120px;">Mã lỗi</th>
                    <th class="py-3" style="min-width: 200px;">Hiện tượng / Tên lỗi</th>
                    <th class="py-3 text-center" style="width: 150px;">Mức độ</th>
                    <th class="py-3" style="min-width: 250px;">Mô tả sơ bộ</th>
                    <th class="py-3 text-center" style="width: 100px;">Chi tiết</th>
                </tr>
            </thead>
            <tbody id="errorTableBody" class="bg-white">
                {{-- Placeholder Row: Hiển thị khi chưa tìm kiếm --}}
                <tr id="placeholderRow">
                    <td colspan="6" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                            {{-- Dùng icon Bootstrap thay vì ảnh ngoài để load nhanh và đồng bộ --}}
                            <div class="bg-light rounded-circle p-4 mb-3">
                               <img src="{{ asset('imgs/empty_list.png') }}" alt="Search" width="80" class="opacity-25 mb-3">
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Kết quả tìm kiếm sẽ hiện tại đây</h6>
                            <small>Vui lòng chọn model và nhập thông tin ở thanh tìm kiếm phía trên.</small>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
