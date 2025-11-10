/**
 * Edit Field Handler Module
 * Xử lý chức năng edit inline các trường dữ liệu
 */

const CollaboratorInstallEditField = {
    orderCode: '',
    fullAddress: '',
    routes: {},
    
    /**
     * Initialize edit field handler
     * @param {Object} config - Config object với orderCode, fullAddress, routes
     */
    init: function(config) {
        this.orderCode = config.orderCode || '';
        this.fullAddress = config.fullAddress || '';
        this.routes = config.routes || {};
        this.setupEditIconHandler();
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

            // Tạo ID duy nhất cho input để validation có thể xác định
            const inputId = field ? `edit_${field}` : `edit_${agency}`;
            
            let $input = $("<input>", {
                type: (fieldName === 'customer_address') ? 'textarea' : 'text',
                value: oldValue,
                class: "form-control d-inline-block w-100",
                id: inputId,
                name: inputId
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

            // Chặn nhập ký tự không hợp lệ ngay khi gõ
            self.setupInputRestriction($input, fieldName);

            $input.on("input change paste", function() {
                // Đợi một chút để đảm bảo giá trị đã được cập nhật (đặc biệt với paste)
                const $thisInput = $(this);
                const currentFieldName = $thisInput.data('field') || $thisInput.data('agency') || fieldName;
                setTimeout(function() {
                    if (typeof validateDynamicField === 'function') {
                        console.log('Calling validateDynamicField from input event:', currentFieldName, $thisInput.val());
                        validateDynamicField($thisInput, currentFieldName);
                    } else {
                        console.warn('validateDynamicField function not found');
                    }
                }, 10);
            });

            $input.on("blur", function() {
                const $thisInput = $(this);
                const currentFieldName = $thisInput.data('field') || $thisInput.data('agency') || fieldName;
                if (typeof validateDynamicField === 'function') {
                    console.log('Calling validateDynamicField from blur event:', currentFieldName, $thisInput.val());
                    validateDynamicField($thisInput, currentFieldName);
                }
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

                // Kiểm tra validation errors (từ validate_input/details.js)
                const fieldId = fieldName;
                const hasError = (typeof validationErrors !== 'undefined' && validationErrors[fieldId]);

                if (newValue === '') {
                    if (typeof hideError === 'function') {
                        hideError($(this));
                    }
                    $span.text('').show();
                } else if (!hasError) {
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
                        if (self.orderCode && self.routes.updateAddress) {
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
                    if (typeof hideError === 'function') {
                        hideError($(this));
                    }
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
                
                // Cập nhật trạng thái nút submit sau khi validate
                if (typeof updateSubmitButtons === 'function') {
                    setTimeout(function() {
                        updateSubmitButtons();
                    }, 50);
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
            
            if (typeof validateDynamicField === 'function') {
                console.log('Calling validateDynamicField after focus:', fieldName, $input.val());
                validateDynamicField($input, fieldName);
            }
        });
    },
    
    /**
     * Setup input restriction để chặn ký tự không hợp lệ
     * @param {jQuery} $input - jQuery object of the input
     * @param {string} fieldName - Name of the field
     */
    setupInputRestriction: function($input, fieldName) {
        const self = this;
        
        // Các trường chỉ cho phép số
        if (fieldName === 'sotaikhoan' || fieldName === 'agency_paynumber' || 
            fieldName === 'cccd' || fieldName === 'agency_cccd') {
            // Set maxlength
            let maxLength = 20; // Mặc định cho số tài khoản
            if (fieldName === 'cccd' || fieldName === 'agency_cccd') {
                maxLength = 12; // CCCD chỉ 12 số
            }
            $input.attr('maxlength', maxLength);
            
            // Chặn tất cả ký tự không phải số
            $input.on("keypress", function(e) {
                // Cho phép: số (0-9), Backspace, Delete, Tab, Escape, Enter, Arrow keys
                const allowedKeys = [8, 9, 27, 13, 46, 37, 38, 39, 40];
                const charCode = e.which ? e.which : e.keyCode;
                
                // Kiểm tra độ dài tối đa
                if ((charCode >= 48 && charCode <= 57)) {
                    const currentValue = $(this).val();
                    if (currentValue.length >= maxLength) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                if (allowedKeys.indexOf(charCode) !== -1 || 
                    (charCode >= 48 && charCode <= 57)) {
                    return true;
                }
                e.preventDefault();
                return false;
            });
            
            // Chặn paste ký tự không hợp lệ
            $input.on("paste", function(e) {
                e.preventDefault();
                const paste = (e.originalEvent || e).clipboardData.getData('text');
                // Chỉ giữ lại số
                let numbersOnly = paste.replace(/[^0-9]/g, '');
                
                // Nếu paste có ký tự không phải số, sẽ hiển thị lỗi sau
                const hasInvalidChars = numbersOnly !== paste;
                
                // Giới hạn độ dài
                if (numbersOnly.length > maxLength) {
                    numbersOnly = numbersOnly.substring(0, maxLength);
                }
                
                const currentValue = $input.val();
                const cursorPos = $input[0].selectionStart || 0;
                const selectionEnd = $input[0].selectionEnd || cursorPos;
                const beforeCursor = currentValue.substring(0, cursorPos);
                const afterCursor = currentValue.substring(selectionEnd);
                
                // Tính toán giá trị mới
                let newValue = beforeCursor + numbersOnly + afterCursor;
                // Giới hạn độ dài
                if (newValue.length > maxLength) {
                    newValue = newValue.substring(0, maxLength);
                }
                
                $input.val(newValue);
                // Đặt lại vị trí cursor
                const newCursorPos = Math.min(cursorPos + numbersOnly.length, maxLength);
                $input[0].setSelectionRange(newCursorPos, newCursorPos);
                
                // Validate sau khi paste - đảm bảo validation chạy
                setTimeout(function() {
                    if (typeof validateDynamicField === 'function') {
                        validateDynamicField($input, fieldName);
                    }
                    // Cập nhật nút submit
                    if (typeof updateSubmitButtons === 'function') {
                        updateSubmitButtons();
                    }
                }, 50);
            });
            
            // Thêm validation khi input thay đổi (để bắt các trường hợp đặc biệt)
            $input.on("input", function() {
                const $thisInput = $(this);
                const currentValue = $thisInput.val();
                // Kiểm tra nếu có ký tự không phải số (có thể do cách nào đó nhập được)
                if (currentValue && !/^[0-9]*$/.test(currentValue)) {
                    // Nếu có ký tự không hợp lệ, validate ngay
                    setTimeout(function() {
                        if (typeof validateDynamicField === 'function') {
                            validateDynamicField($thisInput, fieldName);
                        }
                    }, 10);
                }
            });
        }
        
        // Các trường chỉ cho phép chữ và dấu cách (chi nhánh)
        else if (fieldName === 'chinhanh' || fieldName === 'agency_branch') {
            $input.on("keypress", function(e) {
                const charCode = e.which ? e.which : e.keyCode;
                const char = String.fromCharCode(charCode);
                
                // Cho phép: Backspace, Delete, Tab, Escape, Enter, Arrow keys, Space
                const allowedKeys = [8, 9, 27, 13, 46, 32, 37, 38, 39, 40];
                if (allowedKeys.indexOf(charCode) !== -1) {
                    return true;
                }
                
                // Cho phép chữ tiếng Việt và chữ cái
                const vietnameseRegex = /^[a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY]$/;
                if (vietnameseRegex.test(char)) {
                    return true;
                }
                
                e.preventDefault();
                return false;
            });
            
            // Chặn paste ký tự không hợp lệ
            $input.on("paste", function(e) {
                e.preventDefault();
                const paste = (e.originalEvent || e).clipboardData.getData('text');
                // Chỉ giữ lại chữ và dấu cách
                let validChars = paste.replace(/[^a-zA-Z\sàáảãạăằắẳẵặâầấẩẫậÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬđĐèéẻẽẹêềếểễệÈÉẺẼẸÊỀẾỂỄỆìíỉĩịÌÍỈĨỊòóỏõọôồốổỗộơờớởỡợÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢùúủũụưừứửữựÙÚỦŨỤƯỪỨỬỮỰỳýỷỹỵỲÝỶỸY]/g, '');
                // Giới hạn độ dài
                const maxLength = 80;
                if (validChars.length > maxLength) {
                    validChars = validChars.substring(0, maxLength);
                }
                
                const currentValue = $input.val();
                const cursorPos = $input[0].selectionStart || 0;
                const selectionEnd = $input[0].selectionEnd || cursorPos;
                const beforeCursor = currentValue.substring(0, cursorPos);
                const afterCursor = currentValue.substring(selectionEnd);
                
                // Tính toán giá trị mới
                let newValue = beforeCursor + validChars + afterCursor;
                // Giới hạn độ dài
                if (newValue.length > maxLength) {
                    newValue = newValue.substring(0, maxLength);
                }
                
                $input.val(newValue);
                const newCursorPos = Math.min(cursorPos + validChars.length, maxLength);
                $input[0].setSelectionRange(newCursorPos, newCursorPos);
                
                // Validate sau khi paste
                setTimeout(function() {
                    if (typeof validateDynamicField === 'function') {
                        validateDynamicField($input, fieldName);
                    }
                    // Cập nhật nút submit
                    if (typeof updateSubmitButtons === 'function') {
                        updateSubmitButtons();
                    }
                }, 50);
            });
            
            // Thêm validation khi input thay đổi (để bắt các trường hợp đặc biệt)
            $input.on("input", function() {
                const $thisInput = $(this);
                const currentValue = $thisInput.val();
                // Kiểm tra nếu có số hoặc ký tự đặc biệt (không được phép cho chi nhánh)
                if (currentValue && /[0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(currentValue)) {
                    // Nếu có ký tự không hợp lệ, validate ngay
                    setTimeout(function() {
                        if (typeof validateDynamicField === 'function') {
                            validateDynamicField($thisInput, fieldName);
                        }
                    }, 10);
                }
            });
        }
        
        // Các trường khác: giới hạn độ dài tối đa
        else {
            // Giới hạn độ dài theo từng trường
            let maxLength = 80; // Mặc định
            if (fieldName === 'customer_address') {
                maxLength = 150;
            } else if (fieldName === 'sotaikhoan' || fieldName === 'agency_paynumber') {
                maxLength = 20;
            } else if (fieldName === 'cccd' || fieldName === 'agency_cccd') {
                maxLength = 12;
            }
            
            $input.attr('maxlength', maxLength);
            
            // Chặn nhập quá độ dài
            $input.on("input", function() {
                const currentValue = $(this).val();
                if (currentValue.length > maxLength) {
                    $(this).val(currentValue.substring(0, maxLength));
                }
            });
        }
    }
};

