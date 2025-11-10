/**
 * Form Update Module
 * Xử lý submit form update/complete/pay
 */

const CollaboratorInstallFormUpdate = {
    routes: {},
    orderCode: '',
    
    /**
     * Initialize form update handler
     * @param {Object} config - Config object với routes, orderCode
     */
    init: function(config) {
        this.routes = config.routes || {};
        this.orderCode = config.orderCode || '';
        this.setupUpdateButtons();
    },
    
    /**
     * Setup update buttons
     */
    setupUpdateButtons: function() {
        const self = this;
        
        $("#btnUpdate, #btnComplete, #btnPay").on("click", function(e) {
            e.preventDefault();
            
            // Validate all trước khi submit
            if (typeof CollaboratorInstallValidation !== 'undefined' && 
                typeof CollaboratorInstallValidation.validateAll === 'function') {
                if (!CollaboratorInstallValidation.validateAll()) {
                    return;
                }
            } else if (typeof validationErrors !== 'undefined') {
                // Fallback: kiểm tra validation errors trực tiếp
                let hasErrors = Object.keys(validationErrors).length > 0;
                if (hasErrors) {
                    showSwalMessage('error', 'Lỗi điền thông tin', 'Vui lòng sửa các lỗi được tô đỏ trước khi tiếp tục.', {
                        timer: 3000,
                        showConfirmButton: false
                    });
                    return;
                }
            }
            
            showSwalConfirm('Bạn có chắc chắn?', "Hành động này không thể hoàn tác!", 'Có, tiếp tục!', 'Hủy bỏ')
                .then((result) => {
                    if (result.isConfirmed) {
                        const urlParams = new URLSearchParams(window.location.search);
                        const type = urlParams.get('type');
                        
                        let action = $(e.currentTarget).data('action');
                        let isInstallAgency = $("#isInstallAgency").is(":checked") ? 1 : 0;
                        let formData = new FormData();
                        formData.append("_token", $('meta[name="csrf-token"]').attr("content"));
                        formData.append("id", self.routes.modelId);
                        formData.append("action", action);
                        formData.append("type", type);
                        formData.append("product", $('#product_name').val());

                        if (isInstallAgency === 1) {
                            formData.append("ctv_id", 1);
                            formData.append("successed_at", $("#successed_at").val().trim());
                            // Sử dụng getCurrencyValue từ utils/currency.js
                            const installCostAgency = (typeof getCurrencyValue === 'function') 
                                ? getCurrencyValue($('#install_cost_agency')) 
                                : $('#install_cost_agency').val().replace(/[^0-9]/g, '') || '0';
                            formData.append("installcost", installCostAgency);
                        } else {
                            formData.append("ctv_id", $("#ctv_id").val());
                            formData.append("successed_at", $("#successed_at_ctv").val().trim());
                            // Sử dụng getCurrencyValue từ utils/currency.js
                            const installCostCtv = (typeof getCurrencyValue === 'function') 
                                ? getCurrencyValue($('#install_cost_ctv')) 
                                : $('#install_cost_ctv').val().replace(/[^0-9]/g, '') || '0';
                            formData.append("installcost", installCostCtv);
                            
                            let file = $("#install_review")[0].files[0];
                            if (file) {
                                formData.append("installreview", file);
                            }
                        }
                        
                        if (typeof OpenWaitBox === 'function') {
                            OpenWaitBox();
                        }
                        
                        $.ajax({
                            url: self.routes.update,
                            method: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(res) {
                                if (typeof CloseWaitBox === 'function') {
                                    CloseWaitBox();
                                }
                                if (res.success) {
                                    showSwalMessage('success', res.message, '', {
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => {
                                        // Update collaborator và agency nếu cần
                                        if (typeof CollaboratorInstallUpdateCollaborator !== 'undefined') {
                                            CollaboratorInstallUpdateCollaborator.update(self.orderCode, self.routes);
                                        }
                                        if (typeof CollaboratorInstallUpdateAgency !== 'undefined') {
                                            if (typeof CollaboratorInstallDataManagement !== 'undefined' &&
                                                typeof CollaboratorInstallDataManagement.hasAgencyChanges === 'function') {
                                                if (CollaboratorInstallDataManagement.hasAgencyChanges()) {
                                                    CollaboratorInstallUpdateAgency.update(self.orderCode, self.routes);
                                                }
                                            }
                                        }
                                        location.reload();
                                    });
                                } else {
                                    showSwalMessage('error', res.message, '', {
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                }
                            },
                            error: function(xhr) {
                                if (typeof CloseWaitBox === 'function') {
                                    CloseWaitBox();
                                }
                                if (typeof handleAjaxError === 'function') {
                                    handleAjaxError(xhr);
                                } else {
                                    showSwalMessage('error', 'Lỗi', 'Có lỗi xảy ra khi cập nhật');
                                }
                            }
                        });
                    }
                });
        });
    }
};

