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

<!-- JavaScript để xử lý modal và AJAX -->
<script>
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    // Đã đổi tên hàm để tránh trùng tên
    function showPermissionError() {
        Swal.fire({ icon: 'error', title: 'Bạn không được quyền!' });
    }

    function showStatusModal(id, currentStatus, type, flag) {
        const $status = $('#new_status');
        if (type === 'agent_component') {
            if ($status.find('option[value="Đã tiếp nhận"]').length === 0) {
                const option = $('<option value="Đã tiếp nhận">Đã tiếp nhận</option>');
                $status.find('option:nth-child(1)').after(option);
            }
            if ($status.find('option[value="Đã gửi linh kiện"]').length === 0) {
                const option = $('<option value="Đã gửi linh kiện">Đã gửi linh kiện</option>');
                $status.find('option:nth-child(2)').after(option);
            }
        }
        else{
            $status.find('option[value="Đã tiếp nhận"]').remove();
            $status.find('option[value="Đã gửi linh kiện"]').remove();
        }
        $('#status_id').val(id);
        $('#new_status').val(currentStatus).data('type', type);
        $('#statusModal').attr('data-flag', flag);
        handleBranchSectionDisplay(type, currentStatus, id);
        new bootstrap.Modal($('#statusModal')[0]).show();
    }

    function handleBranchSectionDisplay(type, selectedStatus, id) {
        const isShow = type === 'agent_component' && selectedStatus === 'Đã hoàn tất';
        $('#branchSection').toggleClass('d-none', !isShow);
        if (!isShow) {
            $('#componentTableBody').empty();
            return;
        }
        let getComponentUrl = "{{ route('warranty.getcomponent', ['sophieu' => ':id']) }}";
        let url = getComponentUrl.replace(':id', id);
        $('#componentTableBody').html(`<tr><td colspan="3" class="text-center text-muted">Đang tải dữ liệu...</td></tr>`).fadeIn(200);
        // Load dữ liệu linh kiện qua AJAX
        $.get(url)
            .done(response => {
                const html = response.map(item => `
                    <tr>
                        <td>${item.replacement}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-center">
                            <input type="number" class="form-control w-100" min="0" name="return_qty[${item.id}]">
                        </td>
                    </tr>
                `).join('');
                $('#componentTableBody').html(html);
            })
            .fail(() => {
                $('#componentTableBody').html('<tr><td colspan="3" class="text-center text-danger">Lỗi tải dữ liệu linh kiện</td></tr>');
            });
    }

    $('#new_status').on('change', function () {
        handleBranchSectionDisplay($(this).data('type'), $(this).val(), $('#status_id').val());
    });

    function validateReturnQuantities() {
        let valid = true;
        const data = [];

        $('#componentTableBody tr').each(function () {
            const $input = $(this).find('input[type="number"]');
            const returnQty = parseInt($input.val());
            const detailId = $input.attr('name').match(/\d+/)[0];
            const sendQty = parseInt($(this).find('td').eq(1).text());

            if (isNaN(returnQty) || returnQty < 0 || returnQty > sendQty) {
                $input.addClass('is-invalid');
                valid = false;
            } else {
                $input.removeClass('is-invalid');
                data.push({ id: detailId, return_quantity: returnQty });
            }
        });

        return valid ? data : null;
    }

    function updateStatus() {
        const id = $('#status_id').val();
        const newStatus = $('#new_status').val();
        let flag = $('#statusModal').data('flag');
        const showBranch = !$('#branchSection').hasClass('d-none');
        let components = '';

        if (showBranch) {
            const data = validateReturnQuantities();
            if (!data) {
                return Swal.fire({ icon: 'error', title: 'Số lượng trả phải hợp lệ và không lớn hơn số lượng gửi!' });
            }
            components = data;
        }
        OpenWaitBox();
        $.post("{{ route('warranty.updatestatus') }}", { _token: csrfToken, id, status: newStatus, components })
            .done(response => {
                CloseWaitBox();
                if (response.success === false) {
                    return Swal.fire({ icon: 'warning', title: response.message, timer: 1500, showConfirmButton: false });
                }
                Swal.fire({ icon: 'success', title: 'Cập nhật thành công!', timer: 1000, showConfirmButton: false })
                    .then(() => {
                        // Gọi lại loadTabData với tab hiện tại và dữ liệu filter
                        let activeTab = localStorage.getItem('activeTab') || 'danhsach';
                        let formData = $('#searchForm').serialize();
                        if (flag) {
                            location.reload();
                        } else {
                            loadTabData(activeTab, formData);
                        }
                        $('#statusModal').modal('hide');
                    });
            })
            .fail(() => {
                CloseWaitBox();
                Swal.fire({ icon: 'error', title: 'Lỗi khi cập nhật!' });
            });
    }
</script>
