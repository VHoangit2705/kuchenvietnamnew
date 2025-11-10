/**
 * Collaborator Install Details Page JavaScript
 * Main file - sử dụng các module riêng biệt
 */

const CollaboratorInstallDetails = {
    // Global variables
    creationDate: null,
    orderCode: '',
    fullAddress: '',
    routes: {},
    
    /**
     * Initialize
     */
    init: function(config) {
        this.creationDate = config.creationDate || null;
        this.orderCode = config.orderCode || '';
        this.fullAddress = config.fullAddress || '';
        this.routes = config.routes || {};
        
        // Initialize data management modules
        if (typeof CollaboratorInstallCtvData !== 'undefined') {
            CollaboratorInstallCtvData.init({
                routes: this.routes,
                orderCode: this.orderCode
            });
        }
        
        if (typeof CollaboratorInstallAgencyData !== 'undefined') {
            CollaboratorInstallAgencyData.init({
                routes: this.routes,
                orderCode: this.orderCode
            });
        }
        
        // Setup event handlers
        this.setupEventHandlers();
        
        // Validation đã được khởi tạo sớm, chỉ cần cập nhật creationDate nếu cần
        // Không khởi tạo lại validation để tránh duplicate event handlers
        if (typeof CollaboratorInstallValidation !== 'undefined' && 
            CollaboratorInstallValidation.creationDate !== this.creationDate) {
            // Chỉ cập nhật creationDate và chạy lại validation nếu cần (skip empty khi page load)
            CollaboratorInstallValidation.creationDate = this.creationDate;
            CollaboratorInstallValidation.runInitialValidations(true);
        }
        
        // Setup bank list
        if (typeof CollaboratorInstallBankSetup !== 'undefined') {
            CollaboratorInstallBankSetup.setup(this.routes);
        }
        
        // Setup location filters
        if (typeof CollaboratorInstallLocationFilters !== 'undefined') {
            CollaboratorInstallLocationFilters.init(this.routes);
        }
        
        // Setup edit field handler
        if (typeof CollaboratorInstallEditField !== 'undefined') {
            CollaboratorInstallEditField.init({
                orderCode: this.orderCode,
                fullAddress: this.fullAddress,
                routes: this.routes
            });
        }
        
        // Setup form update
        if (typeof CollaboratorInstallFormUpdate !== 'undefined') {
            CollaboratorInstallFormUpdate.init({
                routes: this.routes,
                orderCode: this.orderCode
            });
        }
    },
    
    /**
     * Update field display
     */
    updateField: function(fieldId, value) {
        let td = $("#" + fieldId);
        let html = `<span class="text-value">${value ?? ''}</span>`;
        if (fieldId === 'nganhang') {
            html += ` <img class="bank-logo ms-2" alt="logo ngân hàng" style="height:50px; display:none;"/>`;
        }
        if (!value) {
            html += `<i class="bi bi-pencil ms-2 edit-icon" style="cursor:pointer;"></i>`;
        }
        td.html(html);
        if (fieldId === 'nganhang') {
            if (window.updateBankLogoForCell) {
                window.updateBankLogoForCell(td);
            }
        }
    },
    
    /**
     * Setup event handlers
     */
    setupEventHandlers: function() {
        const self = this;
        
        // Handle isInstallAgency checkbox
        $("#isInstallAgency").on("change", function() {
            // Xóa tất cả cờ lỗi và chạy lại validation
            if (typeof validationErrors !== 'undefined') {
            validationErrors = {};
            }
            $('.validation-error').remove();
            
            if ($(this).is(":checked")) {
                // Clear các trường CTV
                if (typeof CollaboratorInstallCtvData !== 'undefined') {
                    CollaboratorInstallCtvData.clear(self.updateField.bind(self));
                }
                
                $(".installCostRow").show();
                $(".ctv_row").hide();
                $("#install_cost_row").hide();
                $("#install_file").hide();
                $("#table_collaborator").hide();
            } else {
                // Khôi phục giá trị ban đầu
                if (typeof CollaboratorInstallCtvData !== 'undefined') {
                    CollaboratorInstallCtvData.restoreOriginal(self.updateField.bind(self));
                }
                
                $(".installCostRow").hide();
                $(".error").hide();
                $("#table_collaborator").show();
                $(".ctv_row").show();
                $("#install_cost_row").show();
                $("#install_file").show();
            }
            
            // Chạy lại validation cho các trường (không skip empty khi thay đổi checkbox)
            if (typeof runAllInitialValidations === 'function') {
                runAllInitialValidations(self.creationDate, false);
            }
            
            // Cập nhật trạng thái nút submit
            if (typeof updateSubmitButtons === 'function') {
                setTimeout(function() {
                    updateSubmitButtons();
                }, 100);
            }
        });
        
        // Handle initial state
        if ($("#isInstallAgency").is(":checked")) {
            $(".installCostRow").show();
            $(".ctv_row").hide();
            $("#install_cost_row").hide();
            $("#install_file").hide();
            $("#table_collaborator").hide();
        } else {
            $(".installCostRow").hide();
            $(".error").hide();
            $("#table_collaborator").show();
        }
        
        // Handle choose CTV
        $('#tablecollaborator').on('click', '.choose-ctv', function() {
            let id = $(this).data("id");
            $.ajax({
                url: self.routes.collaboratorShow.replace(':id', id),
                method: "GET",
                success: function(res) {
                    $("#ctv_name").text(res.full_name);
                    $("#ctv_phone").text(res.phone);
                    self.updateField("sotaikhoan", res.sotaikhoan);
                    self.updateField("chinhanh", res.chinhanh);
                    const bankName = res.nganhang || res.bank_name || '';
                    self.updateField("nganhang", bankName);
                    // Đảm bảo cập nhật logo ngay lập tức
                    if (window.updateBankLogoForCell) {
                        window.updateBankLogoForCell($("#nganhang"));
                    }
                    self.updateField("cccd", res.cccd);
                    self.updateField("ngaycap", res.ngaycap);

                    $(".ctv_row").show();
                    $("#install_cost_row").show();
                    $("#ctv_id").val(id);
                    
                    // Chạy lại validation sau khi chọn CTV (không skip empty)
                    if (typeof runAllInitialValidations === 'function') {
                        runAllInitialValidations(self.creationDate, false);
                    }
                },
                error: function() {
                    showSwalMessage('error', 'Lỗi!', 'Không thể tải thông tin CTV');
                }
            });
        });
        
        // Handle view history button
        $('#btnViewHistory').on('click', function() {
            if (typeof CollaboratorInstallHistory !== 'undefined' && self.routes.orderHistory) {
                CollaboratorInstallHistory.load(self.orderCode, self.routes.orderHistory);
            }
            $('#historyModal').modal('show');
        });
    }
};
