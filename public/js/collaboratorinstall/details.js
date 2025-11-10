/**
 * Collaborator Install Details Page JavaScript
 * Xử lý logic chính cho trang chi tiết đơn hàng lắp đặt
 */

const CollaboratorInstallDetails = {
    // Global variables
    originalCtvData: {},
    originalAgencyData: {},
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
        
        // Lưu giá trị ban đầu
        this.saveOriginalCtvData();
        this.saveOriginalAgencyData();
        
        // Setup event handlers
        this.setupEventHandlers();
        this.setupValidation();
        this.setupBankList();
        this.setupLocationFilters();
    },
    
    /**
     * Save original CTV data
     */
    saveOriginalCtvData: function() {
        this.originalCtvData = {
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
     * Save original agency data
     */
    saveOriginalAgencyData: function() {
        this.originalAgencyData = {
            agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
            agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
            agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
            agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
            agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
            agency_branch: $("td[data-agency='agency_branch'] .text-value").text().trim(),
            agency_cccd: $("td[data-agency='agency_cccd'] .text-value").text().trim(),
            agency_release_date: $("td[data-agency='agency_release_date'] .text-value").text().trim()
        };
    },
    
    /**
     * Restore original CTV data
     */
    restoreOriginalCtvData: function() {
        // Lưu thông tin đại lý hiện tại trước khi chuyển về CTV
        this.saveAgencyDataBeforeSwitch();
        
        $("#ctv_name").text(this.originalCtvData.ctv_name);
        $("#ctv_phone").text(this.originalCtvData.ctv_phone);
        $("#ctv_id").val(this.originalCtvData.ctv_id);
        this.updateField("sotaikhoan", this.originalCtvData.sotaikhoan);
        this.updateField("chinhanh", this.originalCtvData.chinhanh);
        this.updateField("nganhang", this.originalCtvData.nganhang);
        this.updateField("cccd", this.originalCtvData.cccd);
        this.updateField("ngaycap", this.originalCtvData.ngaycap);
        $("#install_cost_ctv").val(this.originalCtvData.install_cost_ctv);
        $("#successed_at_ctv").val(this.originalCtvData.successed_at_ctv);
        
        // Ghi log việc chuyển từ "Đại lý lắp đặt" về CTV
        this.logSwitchToCtv();
    },
    
    /**
     * Clear CTV data
     */
    clearCtvData: function() {
        $("#ctv_name").text('');
        $("#ctv_phone").text('');
        $("#ctv_id").val('');
        this.updateField("sotaikhoan", '');
        this.updateField("chinhanh", '');
        this.updateField("nganhang", '');
        this.updateField("cccd", '');
        this.updateField("ngaycap", '');
        $("#install_cost_ctv").val('');
        $("#successed_at_ctv").val('');
        $("#install_review").val('');
        
        // Gửi AJAX để clear CTV data trên server
        this.clearCtvDataOnServer();
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
            validationErrors = {};
            $('.validation-error').remove();
            
            if ($(this).is(":checked")) {
                // Clear các trường CTV
                self.clearCtvData();
                
                $(".installCostRow").show();
                $(".ctv_row").hide();
                $("#install_cost_row").hide();
                $("#install_file").hide();
                $("#table_collaborator").hide();
            } else {
                // Khôi phục giá trị ban đầu
                self.restoreOriginalCtvData();
                
                $(".installCostRow").hide();
                $(".error").hide();
                $("#table_collaborator").show();
                $(".ctv_row").show();
                $("#install_cost_row").show();
                $("#install_file").show();
            }
            
            // Chạy lại validation cho tất cả các trường sau khi UI đã cập nhật
            setTimeout(function() {
                runAllInitialValidations(self.creationDate);
            }, 100);
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
                    
                    // Validate tất cả các field CTV sau khi cập nhật
                    setTimeout(function() {
                        if (typeof validateAllDynamicFields === 'function') {
                            validateAllDynamicFields('ctv');
                        }
                        if (typeof runAllInitialValidations === 'function') {
                            runAllInitialValidations(self.creationDate);
                        }
                    }, 100);
                },
                error: function() {
                    showSwalMessage('error', 'Lỗi!', 'Không thể tải thông tin CTV');
                }
            });
        });
        
        // Handle view history button
        $('#btnViewHistory').on('click', function() {
            self.loadHistory();
            $('#historyModal').modal('show');
        });
        
        // Setup edit icon handler
        this.setupEditIconHandler();
        
        // Setup update buttons
        this.setupUpdateButtons();
    },
    
    /**
     * Setup validation
     */
    setupValidation: function() {
        const self = this;
        
        // Format lại giá trị ban đầu cho các input chi phí (nếu có giá trị)
        $(".install_cost").each(function() {
            const $input = $(this);
            const value = $input.val();
            if (value && value.trim() !== '') {
                // Format lại giá trị ban đầu (xử lý cả dấu chấm và dấu phẩy)
                formatCurrency($input);
                // Validate ngay sau khi format
                setTimeout(function() {
                    validateInstallCost($input);
                }, 50);
            }
        });
        
        // Chi phí lắp đặt
        $(".install_cost").on("input", function() {
            const $input = $(this);
            formatCurrency($input);
            // Chờ một chút để đảm bảo format đã hoàn tất
            setTimeout(function() {
                validateInstallCost($input);
            }, 10);
        }).on("blur", function() {
            const $input = $(this);
            // Format lại trước khi validate
            formatCurrency($input);
            validateInstallCost($input);
        });
        
        // Ngày hoàn thành
        $("#successed_at_ctv, #successed_at").on("change", function() {
            validateCompletionDate($(this), self.creationDate);
        }).on("blur", function() {
            validateCompletionDate($(this), self.creationDate);
        });
        
        // Chạy validation ban đầu (sau khi đã format)
        runAllInitialValidations(self.creationDate);
    },
    
    /**
     * Setup edit icon handler
     */
    setupEditIconHandler: function() {
        const self = this;
        
        $(document).on("click", ".edit-icon", function() {
            let $td = $(this).closest("td");
            let $span = $td.find(".text-value");
            let oldValue = $span.text().trim();

            let field = $td.data("field");
            let agency = $td.data("agency");
            let fieldName = field || agency;

            let $input = $("<input>", {
                type: (fieldName === 'customer_address') ? 'textarea' : 'text',
                value: oldValue,
                class: "form-control d-inline-block w-100"
            });
            
            if (field) $input.attr('data-field', field);
            if (agency) $input.attr('data-agency', agency);

            if (fieldName === "ngaycap" || fieldName === "agency_release_date") {
                $input.attr("type", "date");
                if (oldValue && oldValue.includes('/')) {
                    let parts = oldValue.split('/');
                    if (parts.length === 3) {
                        let day = parts[0].padStart(2, '0');
                        let month = parts[1].padStart(2, '0');
                        let year = parts[2];
                        $input.val(year + '-' + month + '-' + day);
                    }
                }
            }
            
            if (fieldName === "nganhang" || fieldName === "agency_bank") {
                $input.attr('list', 'bankList');
            }

            $input.on("input change", function() {
                validateDynamicField($(this), fieldName);
            });

            $input.on("blur", function() {
                validateDynamicField($(this), fieldName);
                let newValue = $(this).val().trim();
                
                let oldDisplayValue = $("#customer_address_full").val() || oldValue;
                if (fieldName === 'customer_address' && !oldDisplayValue) {
                    let fullAddress = self.fullAddress;
                    if (oldValue && fullAddress) {
                        oldDisplayValue = oldValue + ", " + fullAddress;
                    } else if (fullAddress) {
                        oldDisplayValue = fullAddress;
                    } else {
                        oldDisplayValue = oldValue;
                    }
                }

                if (newValue === '') {
                    hideError($(this));
                    $span.text('').show();
                } else if (!validationErrors[fieldName]) {
                    // Format ngày tháng
                    if (fieldName === "ngaycap" || fieldName === "agency_release_date") {
                        if (newValue && newValue.includes('-')) {
                            let parts = newValue.split('-');
                            if (parts.length === 3) {
                                let year = parts[0];
                                let month = parts[1];
                                let day = parts[2];
                                newValue = day + '/' + month + '/' + year;
                            }
                        }
                    }
                    
                    // Lưu địa chỉ khách hàng
                    if (fieldName === 'customer_address') {
                        if (self.orderCode) {
                            $.ajax({
                                url: self.routes.updateAddress,
                                method: "POST",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr("content"),
                                    order_code: self.orderCode,
                                    address: newValue
                                },
                                success: function(response) {
                                    if (response.success) {
                                        let fullAddress = self.fullAddress;
                                        let fullAddressText = newValue;
                                        if (newValue && fullAddress) {
                                            fullAddressText = newValue + ", " + fullAddress;
                                        } else if (fullAddress) {
                                            fullAddressText = fullAddress;
                                        }
                                        $span.text(fullAddressText).show();
                                        $("#customer_address_full").val(fullAddressText);
                                        $("#customer_address_detail").val(newValue);
                                    } else {
                                        showSwalMessage('error', 'Lỗi', response.message || 'Không thể cập nhật địa chỉ');
                                        $span.text(oldDisplayValue).show();
                                    }
                                },
                                error: function(xhr) {
                                    showSwalMessage('error', 'Lỗi', 'Có lỗi xảy ra khi cập nhật địa chỉ');
                                    $span.text(oldDisplayValue).show();
                                }
                            });
                        } else {
                            $span.text(newValue).show();
                        }
                    } else {
                        $span.text(newValue).show();
                    }
                } else {
                    hideError($(this));
                    let displayValue = (fieldName === 'customer_address') ? oldDisplayValue : oldValue;
                    $span.text(displayValue).show();
                }

                $td.find(".edit-icon").show();
                $(this).remove();

                if (fieldName === 'nganhang' || fieldName === 'agency_bank') {
                    if (window.updateBankLogoForCell) {
                        window.updateBankLogoForCell($td);
                    }
                }
            });

            $input.on("keypress", function(e) {
                if (e.which === 13) $(this).blur();
            });

            $span.hide();
            if (fieldName === 'customer_address') {
                $td.contents().filter(function() { return this.nodeType === 3; }).remove();
            }
            $(this).hide();
            $td.prepend($input);
            $input.focus();
            
            validateDynamicField($input, fieldName);
        });
    },
    
    /**
     * Setup update buttons
     */
    setupUpdateButtons: function() {
        const self = this;
        
        $("#btnUpdate, #btnComplete, #btnPay").on("click", function(e) {
            e.preventDefault();
            
            if (!self.validateAll()) {
                return;
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
                            formData.append("installcost", getCurrencyValue($('#install_cost_agency')));
                        } else {
                            formData.append("ctv_id", $("#ctv_id").val());
                            formData.append("successed_at", $("#successed_at_ctv").val().trim());
                            formData.append("installcost", getCurrencyValue($('#install_cost_ctv')));
                            
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
                                        self.updateCollaborator();
                                        if (self.hasAgencyChanges()) {
                                            self.updateAgency();
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
                                handleAjaxError(xhr);
                            }
                        });
                    }
                });
        });
    },
    
    /**
     * Validate basic info
     */
    validateBasicInfo: function() {
        if ($("#isInstallAgency").is(":checked")) {
            return parseInt(getCurrencyValue($('#install_cost_agency')), 10) > 0;
        } else {
            return $("#ctv_id").val() !== '' && parseInt(getCurrencyValue($('#install_cost_ctv')), 10) > 0;
        }
    },
    
    /**
     * Validate all
     */
    validateAll: function() {
        runAllInitialValidations(this.creationDate);
        
        if (!this.validateBasicInfo()) {
            showSwalMessage('error', 'Lỗi thông tin cơ bản', 'Vui lòng chọn CTV và nhập chi phí, hoặc chọn "Đại lý lắp đặt" và nhập chi phí.', {
                timer: 3000,
                showConfirmButton: false
            });
            return false;
        }
        
        let hasErrors = Object.keys(validationErrors).length > 0;
        if (hasErrors) {
            showSwalMessage('error', 'Lỗi điền thông tin', 'Vui lòng sửa các lỗi được tô đỏ trước khi tiếp tục.', {
                timer: 3000,
                showConfirmButton: false
            });
            return false;
        }
        
        return true;
    },
    
    /**
     * Update collaborator
     */
    updateCollaborator: function() {
        let id = $("#ctv_id").val();
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            id: id,
            order_code: this.orderCode
        };
        
        $("td[data-field]").each(function() {
            let $td = $(this);
            let field = $td.data("field");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
            }
            
            if (field === 'ngaycap' && value && value.includes('/')) {
                let parts = value.split('/');
                if (parts.length === 3) value = parts[2] + '-' + parts[1] + '-' + parts[0];
            }
            
            data[field] = value;
        });

        $.ajax({
            url: this.routes.ctvUpdate,
            method: "POST",
            data: data,
            success: function(response) {
                // Collaborator updated successfully
            },
            error: function(xhr, status, error) {
                // Error updating collaborator
            }
        });
    },
    
    /**
     * Update agency
     */
    updateAgency: function() {
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            order_code: this.orderCode
        };
        
        let agencyPhone = '';
        $("td[data-agency]").each(function() {
            let $td = $(this);
            let agency = $td.data("agency");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
            }
            
            if (agency === "agency_phone") {
                agencyPhone = value;
            }
            
            if (agency === "agency_release_date" && value && value.includes('/')) {
                let parts = value.split('/');
                if (parts.length === 3) {
                    let day = parts[0].padStart(2, '0');
                    let month = parts[1].padStart(2, '0');
                    let year = parts[2];
                    value = year + '-' + month + '-' + day;
                }
            }
            
            data[agency] = value;
        });
        
        if (!agencyPhone) {
            return;
        }
        
        $.ajax({
            url: this.routes.agencyUpdate,
            method: "POST",
            data: data,
            success: function(response) {
                if (response.success) {
                    // Agency update successful
                }
            },
            error: function(xhr, status, error) {
                // Error updating agency
            }
        });
    },
    
    /**
     * Save agency data before switch
     */
    saveAgencyDataBeforeSwitch: function() {
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            order_code: this.orderCode
        };
        
        $("td[data-agency]").each(function() {
            let $td = $(this);
            let agency = $td.data("agency");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
            }
            
            if (agency === "agency_release_date" && value && value.includes('/')) {
                let parts = value.split('/');
                if (parts.length === 3) {
                    let day = parts[0].padStart(2, '0');
                    let month = parts[1].padStart(2, '0');
                    let year = parts[2];
                    value = year + '-' + month + '-' + day;
                }
            }
            
            data[agency] = value;
        });
        
        $.ajax({
            url: this.routes.agencyUpdate,
            method: "POST",
            data: data,
            success: function(response) {
                console.log('Agency data saved before switch to CTV');
            },
            error: function(xhr, status, error) {
                console.log('Error saving agency data before switch:', error);
            }
        });
    },
    
    /**
     * Log switch to CTV
     */
    logSwitchToCtv: function() {
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
    clearCtvDataOnServer: function() {
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
    },
    
    /**
     * Check if has agency changes
     */
    hasAgencyChanges: function() {
        let currentAgencyData = {
            agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
            agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
            agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
            agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
            agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
            agency_branch: $("td[data-agency='agency_branch'] .text-value").text().trim(),
            agency_cccd: $("td[data-agency='agency_cccd'] .text-value").text().trim(),
            agency_release_date: $("td[data-agency='agency_release_date'] .text-value").text().trim()
        };

        for (let field in this.originalAgencyData) {
            if (this.originalAgencyData[field] !== currentAgencyData[field]) {
                return true;
            }
        }
        
        return false;
    },
    
    /**
     * Setup location filters
     */
    setupLocationFilters: function() {
        const self = this;
        
        $('#province').on('change', function() {
            let provinceId = $(this).val();
            $('#district').empty().append('<option value="" selected>Quận/Huyện</option>');
            $('#ward').empty().append('<option value="" selected>Phường/Xã</option>');
            let url = self.routes.getDistrict.replace(':province_id', provinceId);
            if (provinceId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        let $district = $('#district');
                        $district.empty();
                        $district.append('<option value="" disabled selected>Quận/Huyện</option>');
                        data.forEach(function(item) {
                            $district.append('<option value="' + item.district_id + '">' + item.name + '</option>');
                        });
                    },
                });
            }
            self.filterCollaborators();
        });

        $('#district').on('change', function() {
            let districtId = $(this).val();
            let url = self.routes.getWard.replace(':district_id', districtId);
            if (districtId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        let $ward = $('#ward');
                        $ward.empty();
                        $ward.append('<option value="" disabled selected>Xã/Phường</option>');
                        data.forEach(function(item) {
                            $ward.append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                        });
                    },
                });
            }
            self.filterCollaborators();
        });

        $('#ward').change(function() {
            self.filterCollaborators();
        });
    },
    
    /**
     * Filter collaborators
     */
    filterCollaborators: function() {
        let province = $('#province').val();
        let district = $('#district').val();
        let ward = $('#ward').val();
        $.ajax({
            url: this.routes.filterCollaborators,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                province: province,
                district: district,
                ward: ward
            },
            success: function(res) {
                $('#tablecollaborator').html(res.html);
            },
            error: function(xhr) {
                showSwalMessage('error', 'Lỗi', 'Lỗi khi xử lý');
            }
        });
    },
    
    /**
     * Load history
     */
    loadHistory: function() {
        $('#historyLoading').show();
        $('#historyContent').hide();
        $('#historyEmpty').hide();
        
        if (!this.orderCode) {
            $('#historyLoading').hide();
            $('#historyEmpty').show();
            return;
        }
        
        $.ajax({
            url: this.routes.orderHistory.replace(':order_code', this.orderCode),
            method: "GET",
            success: (response) => {
                $('#historyLoading').hide();
                if (response.success && response.data.history.length > 0) {
                    this.displayHistory(response.data.history);
                    $('#historyContent').show();
                } else {
                    $('#historyEmpty').show();
                }
            },
            error: (xhr, status, error) => {
                $('#historyLoading').hide();
                $('#historyEmpty').show();
                console.error('Lỗi khi tải lịch sử:', error);
            }
        });
    },
    
    /**
     * Display history
     */
    displayHistory: function(history) {
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
     * Get CTV changes
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
     * Get agency changes
     */
    getAgencyChanges: function(changes) {
        return changes.filter(change => 
            change.field_name.includes('đại lý') || 
            change.field_name.includes('Đại lý') ||
            change.field_name.includes('agency')
        );
    },
    
    /**
     * Setup bank list
     */
    setupBankList: function() {
        const banksUrl = this.routes.banksUrl || 'https://api.vietqr.io/v2/banks';
        window.bankNameToLogo = window.bankNameToLogo || {};
        window.bankShortToLogo = window.bankShortToLogo || {};
        window.bankCodeToLogo = window.bankCodeToLogo || {};
        
        try {
            fetch(banksUrl)
                .then(res => res.json())
                .then(json => {
                    if (!json || !json.data) return;
                    const list = document.getElementById('bankList');
                    if (!list) return;
                    list.innerHTML = '';
                    json.data.forEach(function(b){
                        const opt = document.createElement('option');
                        opt.value = (b.shortName ? b.shortName : b.name);
                        opt.label = b.name || b.shortName || '';
                        list.appendChild(opt);
                        const logo = b.logo || '';
                        if (b.name && logo) window.bankNameToLogo[b.name.toLowerCase()] = logo;
                        if (b.shortName && logo) window.bankShortToLogo[b.shortName.toLowerCase()] = logo;
                        if (b.code && logo) window.bankCodeToLogo[b.code.toLowerCase()] = logo;
                    });
                    // Cập nhật logo ban đầu nếu có giá trị sẵn
                    if (window.updateBankLogoForCell) {
                        window.updateBankLogoForCell($("#nganhang"));
                        window.updateBankLogoForCell($("td[data-agency='agency_bank']"));
                    }
                })
                .catch(() => {});
        } catch (e) {}
        
        window.resolveBankLogoByText = function(text){
            if (!text) return null;
            const key = text.toLowerCase();
            return window.bankShortToLogo[key] || window.bankNameToLogo[key] || window.bankCodeToLogo[key] || null;
        };
        
        window.updateBankLogoForCell = function($td){
            if (!$td || !$td.length) return;
            const text = $td.find('.text-value').text().trim();
            const logo = window.resolveBankLogoByText(text);
            const $img = $td.find('img.bank-logo');
            if (!$img.length) return;
            if (logo) {
                $img.attr('src', logo).show();
            } else {
                $img.hide().attr('src', '');
            }
        };
    }
};


