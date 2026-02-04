$(document).ready(function() {
    $('#btnViewHistory').on('click', function() {
        loadHistory();
        $('#historyModal').modal('show');
    });

    function loadHistory() {
        $('#historyLoading').show();
        $('#historyContent').hide();
        $('#historyEmpty').hide();
        
        let orderCode = window.editCtvHistoryOrderCode || '';
        if (!orderCode) {
            $('#historyLoading').hide();
            $('#historyEmpty').show();
            return;
        }
        
        let historyUrl = window.editCtvHistoryUrl || '';
        if (!historyUrl) {
            $('#historyLoading').hide();
            $('#historyEmpty').show();
            console.error('History URL not defined');
            return;
        }
        
        $.ajax({
            url: historyUrl.replace(':order_code', orderCode),
            method: "GET",
            success: function(response) {
                $('#historyLoading').hide();
                if (response.success && response.data.history.length > 0) {
                    displayHistory(response.data.history);
                    $('#historyContent').show();
                } else {
                    $('#historyEmpty').show();
                }
            },
            error: function(xhr, status, error) {
                $('#historyLoading').hide();
                $('#historyEmpty').show();
                console.error('Lỗi khi tải lịch sử:', error);
            }
        });
    }

    function displayHistory(history) {
        let html = '';
        
        history.forEach(function(item, index) {
            const actionKey = item.event || item.action_type;
            const actionText = item.action_type_text || actionKey;
            const timestamp = item.formatted_edited_at || '';
            const performer = item.edited_by || 'Hệ thống';
            const source = item.source || 'CollaboratorInstallController@Update';

            const customerChanges = getCustomerChanges(item.changes_detail || []);
            const ctvChanges = getCtvChanges(item.changes_detail || []);
            const agencyChanges = getAgencyChanges(item.changes_detail || []);
            const orderChanges = getOrderChanges(item.changes_detail || []);

            html += `
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white border-bottom position-relative">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h5 class="mb-1 fw-bold">${actionText}</h5>
                                <div class="text-muted small">
                                    <div>${timestamp}</div>
                                    <div>Nguồn: ${source}</div>
                                    <div><strong>Người thực hiện:</strong> ${performer}</div>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" disabled>
                                ${actionText}
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        ${item.changes_detail && item.changes_detail.length > 0 ? `
                            ${customerChanges.length > 0 ? `
                                <div class="mb-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <th colspan="3" class="bg-secondary text-white fw-bold">
                                                        <i class="bi bi-person-badge me-2"></i>Thông tin Khách hàng
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th style="width: 25%;">Trường</th>
                                                    <th style="width: 37.5%;">Giá trị cũ</th>
                                                    <th style="width: 37.5%;">Giá trị mới</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${customerChanges.map(change => `
                                                    <tr>
                                                        <td><strong>${change.field_name}</strong></td>
                                                        <td>${change.old_value || 'Trống'}</td>
                                                        <td>${change.new_value || 'Trống'}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${ctvChanges.length > 0 ? `
                                <div class="mb-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th colspan="3" class="bg-primary text-white fw-bold">
                                                        <i class="bi bi-person me-2"></i>Thông tin CTV
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th style="width: 25%;">Trường</th>
                                                    <th style="width: 37.5%;">Giá trị cũ</th>
                                                    <th style="width: 37.5%;">Giá trị mới</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${ctvChanges.map(change => `
                                                    <tr>
                                                        <td><strong>${change.field_name}</strong></td>
                                                        <td>${change.old_value || 'Trống'}</td>
                                                        <td>${change.new_value || 'Trống'}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${agencyChanges.length > 0 ? `
                                <div class="mb-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-info">
                                                <tr>
                                                    <th colspan="3" class="bg-info text-white fw-bold">
                                                        <i class="bi bi-building me-2"></i>Thông tin Đại lý
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th style="width: 25%;">Trường</th>
                                                    <th style="width: 37.5%;">Giá trị cũ</th>
                                                    <th style="width: 37.5%;">Giá trị mới</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${agencyChanges.map(change => `
                                                    <tr>
                                                        <td><strong>${change.field_name}</strong></td>
                                                        <td>${change.old_value || 'Trống'}</td>
                                                        <td>${change.new_value || 'Trống'}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            ` : ''}

                            ${orderChanges.length > 0 ? `
                                <div class="mb-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-success">
                                                <tr>
                                                    <th colspan="3" class="bg-success text-white fw-bold">
                                                        <i class="bi bi-receipt me-2"></i>Trạng thái / Đơn lắp đặt
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th style="width: 25%;">Trường</th>
                                                    <th style="width: 37.5%;">Giá trị cũ</th>
                                                    <th style="width: 37.5%;">Giá trị mới</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${orderChanges.map(change => `
                                                    <tr>
                                                        <td><strong>${change.field_name}</strong></td>
                                                        <td>${change.old_value || 'Trống'}</td>
                                                        <td>${change.new_value || 'Trống'}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            ` : ''}
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        $('#historyList').html(html);
    }

    function getCustomerChanges(changes) {
        const customerFields = ['full_name','phone_number','address','province_id','district_id','ward_id'];
        return changes.filter(change => customerFields.includes(change.field));
    }

    function getCtvChanges(changes) {
        const ctvFields = ['collaborator_id','sotaikhoan','bank_name','chinhanh','cccd','ngaycap'];
        return changes.filter(change => ctvFields.includes(change.field) || (change.field_name || '').includes('CTV'));
    }

    function getAgencyChanges(changes) {
        const agencyFields = ['agency_id', 'agency_name', 'agency_phone', 'agency_address', 'bank_account', 'agency_bank', 'agency_paynumber', 'agency_branch', 'agency_cccd', 'agency_release_date'];
        return changes.filter(change => 
            agencyFields.includes(change.field) || 
            (change.field || '').startsWith('agency_') || 
            (change.field_name || '').includes('Đại lý') || 
            (change.field_name || '').includes('đại lý') ||
            (change.field_name || '').includes('Chủ tài khoản')
        );
    }

    function getOrderChanges(changes) {
        const orderFields = ['order_code', 'product', 'status_install', 'install_cost', 'dispatched_at'];
        return changes.filter(change => orderFields.includes(change.field));
    }
});
