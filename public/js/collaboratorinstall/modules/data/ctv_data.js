/**
 * CTV Data Management Module
 * Xử lý lưu, khôi phục và xóa dữ liệu CTV
 */

const CollaboratorInstallCtvData = {
    originalData: {},
    routes: {},
    orderCode: '',
    
    /**
     * Initialize CTV data management
     * @param {Object} config - Config object với routes, orderCode
     */
    init: function(config) {
        this.routes = config.routes || {};
        this.orderCode = config.orderCode || '';
        this.saveOriginal();
    },
    
    /**
     * Save original CTV data
     */
    saveOriginal: function() {
        this.originalData = {
            ctv_name: $("#ctv_name").text().trim(),
            ctv_phone: $("#ctv_phone").text().trim(),
            ctv_id: $("#ctv_id").val(),
            sotaikhoan: $("#sotaikhoan .text-value").text().trim(),
            chinhanh: $("#chinhanh .text-value").text().trim(),
            nganhang: $("#nganhang .text-value").text().trim(),
            cccd: $("#cccd .text-value").text().trim(),
            ngaycap: $("#ngaycap .text-value").text().trim(),
            install_cost_ctv: $("#install_cost_ctv").val(),
            successed_at_ctv: $("#successed_at_ctv").val()
        };
    },
    
    /**
     * Restore original CTV data
     * @param {Function} updateField - Function to update field display
     */
    restoreOriginal: function(updateField) {
        // Lưu thông tin đại lý hiện tại trước khi chuyển về CTV
        if (typeof CollaboratorInstallAgencyData !== 'undefined' &&
            typeof CollaboratorInstallAgencyData.saveBeforeSwitch === 'function') {
            CollaboratorInstallAgencyData.saveBeforeSwitch(this.orderCode || this.routes.orderCode, this.routes);
        }
        
        $("#ctv_name").text(this.originalData.ctv_name);
        $("#ctv_phone").text(this.originalData.ctv_phone);
        $("#ctv_id").val(this.originalData.ctv_id);
        if (updateField) {
            updateField("sotaikhoan", this.originalData.sotaikhoan);
            updateField("chinhanh", this.originalData.chinhanh);
            updateField("nganhang", this.originalData.nganhang);
            updateField("cccd", this.originalData.cccd);
            updateField("ngaycap", this.originalData.ngaycap);
        }
        $("#install_cost_ctv").val(this.originalData.install_cost_ctv);
        $("#successed_at_ctv").val(this.originalData.successed_at_ctv);
        
        // Ghi log việc chuyển từ "Đại lý lắp đặt" về CTV
        this.logSwitchToCtv();
    },
    
    /**
     * Clear CTV data
     * @param {Function} updateField - Function to update field display
     */
    clear: function(updateField) {
        $("#ctv_name").text('');
        $("#ctv_phone").text('');
        $("#ctv_id").val('');
        if (updateField) {
            updateField("sotaikhoan", '');
            updateField("chinhanh", '');
            updateField("nganhang", '');
            updateField("cccd", '');
            updateField("ngaycap", '');
        }
        $("#install_cost_ctv").val('');
        $("#successed_at_ctv").val('');
        $("#install_review").val('');
        
        // Gửi AJAX để clear CTV data trên server
        this.clearOnServer();
    },
    
    /**
     * Log switch to CTV
     */
    logSwitchToCtv: function() {
        if (!this.routes.ctvSwitch) {
            return;
        }
        
        $.ajax({
            url: this.routes.ctvSwitch,
            method: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                order_code: this.orderCode
            },
            success: function(response) {
                console.log('Logged switch to CTV');
            },
            error: function(xhr, status, error) {
                console.log('Error logging switch to CTV:', error);
            }
        });
    },
    
    /**
     * Clear CTV data on server
     */
    clearOnServer: function() {
        if (!this.routes.ctvClear) {
            return;
        }
        
        $.ajax({
            url: this.routes.ctvClear,
            method: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                order_code: this.orderCode
            },
            success: function(response) {
                console.log('CTV data cleared on server');
            },
            error: function(xhr, status, error) {
                console.log('Error clearing CTV data:', error);
            }
        });
    }
};

