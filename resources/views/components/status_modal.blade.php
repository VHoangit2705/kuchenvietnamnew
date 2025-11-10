<!-- Modal Chuyển đổi trạng thái -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" id="statusModal" data-flag="">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Cập nhật trạng thái</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="status_id">
                <div class="mb-3">
                    <label for="new_status" class="form-label">Chọn trạng thái mới:</label>
                    <select id="new_status" class="form-select">
                        <option value="Đang sửa chữa">Đang sửa chữa</option>
                        <option value="Chờ KH phản hồi">Chờ KH phản hồi</option>
                        <option value="Đã hoàn tất">Đã hoàn tất</option>
                    </select>
                </div>
                <div id="branchSection" class="d-none">
                    <div class="mb-1">
                        <label for="requantity" class="form-label">Hình thức bảo hành: <strong>Gửi phụ kiện cho cộng tác viên</strong></label>
                    </div>
                    <div class="mb-3">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>Tên linh kiện</th>
                                    <th style="width: 17%;">SL gửi</th>
                                    <th style="width: 17%;">SL trả</th>
                                </tr>
                            </thead>
                            <tbody id="componentTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="updateStatus()">Cập nhật</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Pass data to JavaScript
    window.getComponentRoute = @json(route('warranty.getcomponent', ['sophieu' => ':id']));
    window.updateStatusRoute = @json(route('warranty.updatestatus'));
</script>
<script src="{{ asset('js/validate_input/status_modal.js') }}"></script>
<script src="{{ asset('js/components/status_modal.js') }}"></script>
