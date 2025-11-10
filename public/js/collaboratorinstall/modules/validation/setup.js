/**
 * Validation Setup Module
 * Sử dụng validation từ validate_input/details.js
 */

const CollaboratorInstallValidation = {
    creationDate: null,
    initialized: false,
    
    /**
     * Initialize validation
     * @param {string} creationDate - Creation date for validation
     */
    init: function(creationDate) {
        // Chỉ khởi tạo một lần để tránh duplicate event handlers
        if (this.initialized) {
            // Nếu đã khởi tạo, chỉ cập nhật creationDate và chạy lại validation
            this.creationDate = creationDate;
            this.runInitialValidations();
            return;
        }
        
        this.creationDate = creationDate;
        this.setupInstallCostValidation();
        this.setupCompletionDateValidation();
        this.runInitialValidations();
        this.initialized = true;
    },
    
    /**
     * Setup install cost validation
     */
    setupInstallCostValidation: function() {
        const self = this;
        
        // Hàm setup format currency cho install_cost
        const setupInstallCostFormat = function() {
            // Format lại giá trị ban đầu cho các input chi phí (nếu có giá trị)
            $(".install_cost").each(function() {
                const $input = $(this);
                const value = $input.val();
                if (value && value.trim() !== '') {
                    // Format lại giá trị ban đầu (xử lý cả dấu chấm và dấu phẩy)
                    if (typeof formatCurrency === 'function') {
                        formatCurrency($input);
                    }
                    // Validate ngay sau khi format (chỉ validate nếu có giá trị)
                    setTimeout(function() {
                        if (typeof validateInstallCost === 'function') {
                            const currentValue = $input.val().trim();
                            // Chỉ validate nếu vẫn còn giá trị sau khi format
                            if (currentValue) {
                                validateInstallCost($input);
                            }
                        }
                    }, 50);
                } else {
                    // Nếu trường rỗng, đảm bảo xóa lỗi nếu có
                    if (typeof hideError === 'function') {
                        hideError($input);
                    }
                }
            });
            
            // Chi phí lắp đặt
            $(".install_cost").off("input.install_cost blur.install_cost").on("input.install_cost", function() {
                const $input = $(this);
                if (typeof formatCurrency === 'function') {
                    formatCurrency($input);
                }
                // Chờ một chút để đảm bảo format đã hoàn tất
                setTimeout(function() {
                    if (typeof validateInstallCost === 'function') {
                        validateInstallCost($input);
                    }
                }, 10);
            }).on("blur.install_cost", function() {
                const $input = $(this);
                // Format lại trước khi validate
                if (typeof formatCurrency === 'function') {
                    formatCurrency($input);
                }
                if (typeof validateInstallCost === 'function') {
                    validateInstallCost($input);
                }
            });
        };
        
        // Đợi common utils load xong trước khi setup format currency
        if (window.commonUtilsLoaded && typeof formatCurrency === 'function') {
            setupInstallCostFormat();
        } else {
            // Đợi event commonUtils:loaded
            document.addEventListener('commonUtils:loaded', function() {
                if (typeof formatCurrency === 'function') {
                    setupInstallCostFormat();
                }
            });
            // Fallback: thử lại sau 500ms nếu event không trigger
            setTimeout(function() {
                if (typeof formatCurrency === 'function' && !$(".install_cost").data('format-setup')) {
                    setupInstallCostFormat();
                    $(".install_cost").data('format-setup', true);
                }
            }, 500);
        }
    },
    
    /**
     * Setup completion date validation
     */
    setupCompletionDateValidation: function() {
        const self = this;
        
        $("#successed_at_ctv, #successed_at").on("change", function() {
            if (typeof validateCompletionDate === 'function') {
                validateCompletionDate($(this), self.creationDate);
            }
        }).on("blur", function() {
            if (typeof validateCompletionDate === 'function') {
                validateCompletionDate($(this), self.creationDate);
            }
        });
    },
    
    /**
     * Run all initial validations
     * @param {boolean} skipEmpty - Nếu true, bỏ qua validation cho các trường rỗng
     */
    runInitialValidations: function(skipEmpty) {
        // Đợi một chút để đảm bảo DOM đã sẵn sàng
        const self = this;
        setTimeout(function() {
            if (typeof runAllInitialValidations === 'function') {
                // Khi page load lần đầu, skip validation cho các trường rỗng
                runAllInitialValidations(self.creationDate, skipEmpty !== false);
            }
            // Cập nhật trạng thái nút submit
            if (typeof updateSubmitButtons === 'function') {
                setTimeout(function() {
                    updateSubmitButtons();
                }, 50);
            }
        }, 100);
    },
    
    /**
     * Validate basic info
     * @returns {boolean} True if valid
     */
    validateBasicInfo: function() {
        // Helper function để lấy giá trị currency
        const getCurrencyValueSafe = function($input) {
            if (typeof getCurrencyValue === 'function') {
                return getCurrencyValue($input);
            }
            // Fallback nếu hàm chưa load
            return $input.val().replace(/[^0-9]/g, '') || '0';
        };
        
        if ($("#isInstallAgency").is(":checked")) {
            return parseInt(getCurrencyValueSafe($('#install_cost_agency')), 10) > 0;
        } else {
            return $("#ctv_id").val() !== '' && parseInt(getCurrencyValueSafe($('#install_cost_ctv')), 10) > 0;
        }
    },
    
    /**
     * Validate all
     * @returns {boolean} True if all valid
     */
    validateAll: function() {
        // Chạy lại tất cả validation (không skip empty khi submit)
        if (typeof runAllInitialValidations === 'function') {
            runAllInitialValidations(this.creationDate, false);
        }
        
        if (!this.validateBasicInfo()) {
            showSwalMessage('error', 'Lỗi thông tin cơ bản', 'Vui lòng chọn CTV và nhập chi phí, hoặc chọn "Đại lý lắp đặt" và nhập chi phí.', {
                timer: 3000,
                showConfirmButton: false
            });
            return false;
        }
        
        // Kiểm tra validation errors từ validate_input/details.js
        if (typeof validationErrors !== 'undefined') {
            let hasErrors = Object.keys(validationErrors).length > 0;
            if (hasErrors) {
                showSwalMessage('error', 'Lỗi điền thông tin', 'Vui lòng sửa các lỗi được tô đỏ trước khi tiếp tục.', {
                    timer: 3000,
                    showConfirmButton: false
                });
                return false;
            }
        }
        
        return true;
    }
};

