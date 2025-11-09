/**
 * JavaScript for status_modal component
 */

const csrfToken = $('meta[name="csrf-token"]').attr('content');

/**
 * Hiển thị lỗi không có quyền
 */
function showError() {
    if (typeof showSwalMessage === 'function') {
        showSwalMessage('error', 'Bạn không được quyền!', '');
    } else {
        Swal.fire({ icon: 'error', title: 'Bạn không được quyền!' });
    }
}

/**
 * Hiển thị modal cập nhật trạng thái
 * @param {number} id - ID của warranty
 * @param {string} currentStatus - Trạng thái hiện tại
 * @param {string} type - Loại bảo hành
 * @param {boolean} flag - Flag để reload page
 */
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
    } else {
        $status.find('option[value="Đã tiếp nhận"]').remove();
        $status.find('option[value="Đã gửi linh kiện"]').remove();
    }
    $('#status_id').val(id);
    $('#new_status').val(currentStatus).data('type', type);
    $('#statusModal').attr('data-flag', flag);
    handleBranchSectionDisplay(type, currentStatus, id);
    new bootstrap.Modal($('#statusModal')[0]).show();
}

/**
 * Xử lý hiển thị section branch (linh kiện)
 * @param {string} type - Loại bảo hành
 * @param {string} selectedStatus - Trạng thái được chọn
 * @param {number} id - ID của warranty
 */
function handleBranchSectionDisplay(type, selectedStatus, id) {
    const isShow = type === 'agent_component' && selectedStatus === 'Đã hoàn tất';
    $('#branchSection').toggleClass('d-none', !isShow);
    if (!isShow) {
        $('#componentTableBody').empty();
        return;
    }
    let getComponentUrl = window.getComponentRoute || '';
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

/**
 * NOTE: Hàm validateReturnQuantities đã được di chuyển sang /js/validate_input/status_modal.js
 * Vui lòng đảm bảo file sau được load trước file này:
 * - /js/validate_input/status_modal.js
 */

/**
 * Cập nhật trạng thái warranty
 */
function updateStatus() {
    const id = $('#status_id').val();
    const newStatus = $('#new_status').val();
    let flag = $('#statusModal').data('flag');
    const showBranch = !$('#branchSection').hasClass('d-none');
    let components = '';

    if (showBranch) {
        const data = validateReturnQuantities();
        if (!data) {
            if (typeof showSwalMessage === 'function') {
                showSwalMessage('error', 'Số lượng trả phải hợp lệ và không lớn hơn số lượng gửi!', '');
            } else {
                Swal.fire({ icon: 'error', title: 'Số lượng trả phải hợp lệ và không lớn hơn số lượng gửi!' });
            }
            return;
        }
        components = data;
    }
    
    if (typeof OpenWaitBox === 'function') {
        OpenWaitBox();
    }
    
    $.post(window.updateStatusRoute || '', { 
        _token: csrfToken, 
        id, 
        status: newStatus, 
        components 
    })
        .done(response => {
            if (typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            if (response.success === false) {
                if (typeof showSwalMessage === 'function') {
                    showSwalMessage('warning', response.message, '', {
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({ icon: 'warning', title: response.message, timer: 1500, showConfirmButton: false });
                }
                return;
            }
            
            const successConfig = {
                icon: 'success',
                title: 'Cập nhật thành công!',
                timer: 1000,
                showConfirmButton: false
            };
            
            if (typeof showSwalMessage === 'function') {
                showSwalMessage('success', 'Cập nhật thành công!', '', {
                    timer: 1000,
                    showConfirmButton: false
                }).then(() => {
                    handleStatusUpdateSuccess(flag);
                });
            } else {
                Swal.fire(successConfig).then(() => {
                    handleStatusUpdateSuccess(flag);
                });
            }
        })
        .fail(() => {
            if (typeof CloseWaitBox === 'function') {
                CloseWaitBox();
            }
            if (typeof showSwalMessage === 'function') {
                showSwalMessage('error', 'Lỗi khi cập nhật!', '');
            } else {
                Swal.fire({ icon: 'error', title: 'Lỗi khi cập nhật!' });
            }
        });
}

/**
 * Xử lý sau khi cập nhật trạng thái thành công
 * @param {boolean} flag - Flag để reload page
 */
function handleStatusUpdateSuccess(flag) {
    // Gọi lại loadTabData với tab hiện tại và dữ liệu filter
    let activeTab = localStorage.getItem('activeTab') || 'danhsach';
    let formData = $('#searchForm').serialize();
    if (flag) {
        location.reload();
    } else {
        if (typeof loadTabData === 'function') {
            loadTabData(activeTab, formData);
        } else {
            location.reload();
        }
    }
    $('#statusModal').modal('hide');
}

// Event handler cho thay đổi trạng thái
$('#new_status').on('change', function () {
    handleBranchSectionDisplay($(this).data('type'), $(this).val(), $('#status_id').val());
});

