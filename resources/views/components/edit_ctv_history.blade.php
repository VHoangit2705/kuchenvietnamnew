<!-- Modal Lịch sử thay đổi -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="historyModalLabel">
                    <i class="bi bi-clock-history me-2"></i>Lịch sử thay đổi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải lịch sử thay đổi...</p>
                </div>
                <div id="historyContent" style="display: none;">
                    <div id="historyList"></div>
                </div>
                <div id="historyEmpty" class="text-center py-4" style="display: none;">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-2">Chưa có lịch sử thay đổi nào</p>
                </div>
            </div>
            <div class="modal-footer">
                <p>Lưu ý các trường trống có thể là do đồng bộ từ file excel mà ko có đầy đủ thông tin của đại lý hoặc cộng tác viên</p>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.editCtvHistoryOrderCode = "{{ $code ?? '' }}";
    window.editCtvHistoryUrl = "{{ route('ctv.order.history', ':order_code') }}";
</script>
<script src="{{ asset('js/components/edit_ctv_history.js') }}"></script>
