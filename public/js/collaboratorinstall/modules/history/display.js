/**
 * History Display Module
 * Xử lý hiển thị lịch sử đơn hàng
 */

const CollaboratorInstallHistoryDisplay = {
    /**
     * Display history items
     * @param {Array} history - Array of history items
     */
    display: function(history) {
        let html = '';
        
        history.forEach((item) => {
            html += `
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="bi bi-${this.getActionIcon(item.action_type)} me-2"></i>
                                ${item.action_type_text || item.action_type}
                            </h6>
                            <small class="text-muted">${item.formatted_edited_at}</small>
                        </div>
                        <div>
                            <span class="badge bg-${this.getActionBadgeColor(item.action_type)}">${item.action_type_text || item.action_type}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text">${this.formatStatusComment(item.comments || 'Không có ghi chú')}</p>
                        <p class="card-text"><strong>Người thực hiện:</strong> ${item.edited_by || 'Hệ thống'}</p>
                        
                        ${item.changes_detail && item.changes_detail.length > 0 ? `
                            <div class="mt-3">
                                <h6>Chi tiết thay đổi:</h6>
                                
                                ${this.getCtvChanges(item.changes_detail).length > 0 ? `
                                    <div class="mb-3">
                                        <h6 class="text-primary">
                                            <i class="bi bi-person me-1"></i>Thông tin CTV
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th style="width: 25%;">Trường</th>
                                                        <th style="width: 35%;">Giá trị cũ</th>
                                                        <th style="width: 35%;">Giá trị mới</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${this.getCtvChanges(item.changes_detail).map(change => `
                                                        <tr>
                                                            <td><strong>${change.field_name}</strong></td>
                                                            <td>
                                                                <span class="text-muted">${change.old_value || 'Trống'}</span>
                                                            </td>
                                                            <td>
                                                                <span class="text-success">${change.new_value || 'Trống'}</span>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${this.getAgencyChanges(item.changes_detail).length > 0 ? `
                                    <div class="mb-3">
                                        <h6 class="text-info">
                                            <i class="bi bi-building me-1"></i>Thông tin Đại lý
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-info">
                                                    <tr>
                                                        <th style="width: 25%;">Trường</th>
                                                        <th style="width: 35%;">Giá trị cũ</th>
                                                        <th style="width: 35%;">Giá trị mới</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${this.getAgencyChanges(item.changes_detail).map(change => `
                                                        <tr>
                                                            <td><strong>${change.field_name}</strong></td>
                                                            <td>
                                                                <span class="text-muted">${change.old_value || 'Trống'}</span>
                                                            </td>
                                                            <td>
                                                                <span class="text-success">${change.new_value || 'Trống'}</span>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        $('#historyList').html(html);
    },
    
    /**
     * Get action icon
     * @param {string} actionType - Action type
     * @returns {string} Icon name
     */
    getActionIcon: function(actionType) {
        const icons = {
            'create': 'plus-circle',
            'update': 'pencil-square',
            'delete': 'trash',
            'update_agency': 'building',
            'switch_to_agency': 'arrow-right-circle',
            'switch_to_ctv': 'arrow-left-circle',
            'clear': 'x-circle',
            'status_change': 'arrow-repeat',
            'complete': 'check-circle',
            'payment': 'credit-card'
        };
        return icons[actionType] || 'info-circle';
    },
    
    /**
     * Get action badge color
     * @param {string} actionType - Action type
     * @returns {string} Badge color class
     */
    getActionBadgeColor: function(actionType) {
        const colors = {
            'create': 'success',
            'update': 'primary',
            'delete': 'danger',
            'update_agency': 'info',
            'switch_to_agency': 'warning',
            'switch_to_ctv': 'secondary',
            'clear': 'dark',
            'status_change': 'primary',
            'complete': 'success',
            'payment': 'info'
        };
        return colors[actionType] || 'secondary';
    },
    
    /**
     * Get status color
     * @param {string} statusText - Status text
     * @returns {string} Color class
     */
    getStatusColor: function(statusText) {
        const colors = {
            'Chưa điều phối': 'secondary',
            'Đã điều phối': 'primary',
            'Đã hoàn thành': 'success',
            'Đã thanh toán': 'info'
        };
        return colors[statusText] || 'muted';
    },
    
    /**
     * Format status comment
     * @param {string} comment - Comment text
     * @returns {string} Formatted HTML
     */
    formatStatusComment: function(comment) {
        const regex = /Thay đổi trạng thái: (.+) → (.+)/;
        const match = comment.match(regex);

        if (match && match.length === 3) {
            const oldStatusText = match[1].trim();
            const newStatusText = match[2].trim();
            const oldStatusColor = this.getStatusColor(oldStatusText);
            const newStatusColor = this.getStatusColor(newStatusText);
            return `Thay đổi trạng thái: <span class="text-${oldStatusColor} fw-bold">${oldStatusText}</span> → <span class="text-${newStatusColor} fw-bold">${newStatusText}</span>`;
        }
        return comment;
    },
    
    /**
     * Get CTV changes from changes array
     * @param {Array} changes - Changes array
     * @returns {Array} Filtered CTV changes
     */
    getCtvChanges: function(changes) {
        return changes.filter(change => 
            change.field_name.includes('CTV') || 
            (!change.field_name.includes('đại lý') && 
             !change.field_name.includes('Đại lý') &&
             !change.field_name.includes('agency'))
        );
    },
    
    /**
     * Get agency changes from changes array
     * @param {Array} changes - Changes array
     * @returns {Array} Filtered agency changes
     */
    getAgencyChanges: function(changes) {
        return changes.filter(change => 
            change.field_name.includes('đại lý') || 
            change.field_name.includes('Đại lý') ||
            change.field_name.includes('agency')
        );
    }
};

