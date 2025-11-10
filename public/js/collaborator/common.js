/**
 * File chứa các hàm và constants dùng chung cho module quản lý cộng tác viên
 */

// ==================== REGEX PATTERNS ====================
const VALIDATION_PATTERNS = {
    // Regex cho họ tên (chỉ chữ và khoảng trắng)
    NAME: /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/,
    // Regex cho số điện thoại (chỉ số)
    PHONE: /^\d+$/,
    // Regex cho địa chỉ (chữ, số, khoảng trắng và các ký tự .,-/)
    ADDRESS: /^[a-zA-Z0-9àáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s\.,\/-]*$/
};

// ==================== VALIDATION CONSTANTS ====================
const VALIDATION_LIMITS = {
    NAME_MAX_LENGTH: 50,
    PHONE_MIN_LENGTH: 9,
    PHONE_MAX_LENGTH: 10,
    ADDRESS_MAX_LENGTH: 80
};

// ==================== UTILITY FUNCTIONS ====================

/**
 * Hàm định dạng ngày tháng Y-m-d để gán cho input type="date"
 * @param {string} dateString - Chuỗi ngày tháng cần format
 * @returns {string} - Ngày tháng đã format hoặc chuỗi rỗng
 */
function formatDateToInput(dateString) {
    return dateString ? new Date(dateString).toISOString().split('T')[0] : '';
}

/**
 * Hàm validate họ tên (dùng chung cho cả form và search)
 * @param {string} name - Tên cần validate
 * @param {boolean} isRequired - Có bắt buộc hay không
 * @returns {object} - {isValid: boolean, message: string}
 */
function validateName(name, isRequired = false) {
    const trimmedName = name.trim();
    
    if (isRequired && trimmedName.length === 0) {
        return { isValid: false, message: "Trường này là bắt buộc." };
    }
    
    if (trimmedName.length > VALIDATION_LIMITS.NAME_MAX_LENGTH) {
        return { isValid: false, message: `Họ tên không được vượt quá ${VALIDATION_LIMITS.NAME_MAX_LENGTH} ký tự.` };
    }
    
    if (trimmedName.length > 0 && !VALIDATION_PATTERNS.NAME.test(trimmedName)) {
        return { isValid: false, message: "Họ tên chỉ được chứa chữ." };
    }
    
    return { isValid: true, message: "" };
}

/**
 * Hàm validate số điện thoại (dùng chung cho cả form và search)
 * @param {string} phone - Số điện thoại cần validate
 * @param {boolean} isRequired - Có bắt buộc hay không
 * @returns {object} - {isValid: boolean, message: string}
 */
function validatePhone(phone, isRequired = false) {
    const phoneRaw = phone;
    const phoneTrimmed = phone.trim();
    
    if (isRequired && phoneTrimmed.length === 0) {
        return { isValid: false, message: "Trường này là bắt buộc." };
    }
    
    if (phoneTrimmed.length === 0) {
        return { isValid: true, message: "" }; // Không bắt buộc và rỗng thì hợp lệ
    }
    
    if (/\s/.test(phoneRaw)) {
        return { isValid: false, message: "Số điện thoại không được chứa dấu cách." };
    }
    
    if (!VALIDATION_PATTERNS.PHONE.test(phoneTrimmed)) {
        return { isValid: false, message: "Số điện thoại chỉ được chứa số." };
    }
    
    if (phoneTrimmed.length < VALIDATION_LIMITS.PHONE_MIN_LENGTH || phoneTrimmed.length > VALIDATION_LIMITS.PHONE_MAX_LENGTH) {
        return { isValid: false, message: `Số điện thoại phải có từ ${VALIDATION_LIMITS.PHONE_MIN_LENGTH} đến ${VALIDATION_LIMITS.PHONE_MAX_LENGTH} chữ số.` };
    }
    
    return { isValid: true, message: "" };
}

/**
 * Hàm validate địa chỉ
 * @param {string} address - Địa chỉ cần validate
 * @param {boolean} isRequired - Có bắt buộc hay không
 * @returns {object} - {isValid: boolean, message: string}
 */
function validateAddress(address, isRequired = false) {
    const trimmedAddress = address.trim();
    
    if (isRequired && trimmedAddress.length === 0) {
        return { isValid: false, message: "Trường này là bắt buộc." };
    }
    
    if (trimmedAddress.length > VALIDATION_LIMITS.ADDRESS_MAX_LENGTH) {
        return { isValid: false, message: `Địa chỉ không được vượt quá ${VALIDATION_LIMITS.ADDRESS_MAX_LENGTH} ký tự.` };
    }
    
    if (trimmedAddress.length > 0 && !VALIDATION_PATTERNS.ADDRESS.test(trimmedAddress)) {
        return { isValid: false, message: "Địa chỉ chỉ chứa chữ, số và các ký tự .,-/" };
    }
    
    return { isValid: true, message: "" };
}

/**
 * Hàm load danh sách quận/huyện từ API
 * @param {string} provinceId - ID của tỉnh/thành phố
 * @param {string} routeUrl - URL route để lấy danh sách quận/huyện
 * @param {string} selectId - ID của select element để hiển thị
 * @param {string} placeholder - Text placeholder cho option đầu tiên
 * @param {string} selectedValue - Giá trị đã chọn (nếu có)
 * @param {function} callback - Callback function sau khi load xong
 */
function loadDistricts(provinceId, routeUrl, selectId, placeholder = 'Quận/Huyện', selectedValue = '', callback = null) {
    if (!provinceId || !routeUrl) return;
    
    const url = routeUrl.replace(':province_id', provinceId);
    $.ajax({
        url: url,
        type: 'GET',
        success: function(data) {
            const $select = $('#' + selectId);
            $select.empty();
            // Thêm option placeholder disabled
            $select.append(`<option value="" disabled>${placeholder}</option>`);
            
            data.forEach(function(item) {
                const selected = item.district_id == selectedValue ? 'selected' : '';
                $select.append(`<option value="${item.district_id}" ${selected}>${item.name}</option>`);
            });
            
            // Đảm bảo không có option nào được chọn tự động nếu không có selectedValue
            // Bằng cách set giá trị rỗng và trigger change để đảm bảo placeholder được hiển thị
            if (!selectedValue) {
                $select.val('').trigger('change');
            }
            
            if (callback && typeof callback === 'function') {
                callback();
            }
        },
        error: function(xhr) {
            console.error('Lỗi khi load quận/huyện:', xhr);
        }
    });
}

/**
 * Hàm load danh sách xã/phường từ API
 * @param {string} districtId - ID của quận/huyện
 * @param {string} routeUrl - URL route để lấy danh sách xã/phường
 * @param {string} selectId - ID của select element để hiển thị
 * @param {string} placeholder - Text placeholder cho option đầu tiên
 * @param {string} selectedValue - Giá trị đã chọn (nếu có)
 * @param {function} callback - Callback function sau khi load xong
 */
function loadWards(districtId, routeUrl, selectId, placeholder = 'Xã/Phường', selectedValue = '', callback = null) {
    if (!districtId || !routeUrl) return;
    
    const url = routeUrl.replace(':district_id', districtId);
    $.ajax({
        url: url,
        type: 'GET',
        success: function(data) {
            const $select = $('#' + selectId);
            $select.empty();
            // Thêm option placeholder disabled
            $select.append(`<option value="" disabled>${placeholder}</option>`);
            
            data.forEach(function(item) {
                const selected = item.wards_id == selectedValue ? 'selected' : '';
                $select.append(`<option value="${item.wards_id}" ${selected}>${item.name}</option>`);
            });
            
            // Đảm bảo không có option nào được chọn tự động nếu không có selectedValue
            // Bằng cách set giá trị rỗng và trigger change để đảm bảo placeholder được hiển thị
            if (!selectedValue) {
                $select.val('').trigger('change');
            }
            
            if (callback && typeof callback === 'function') {
                callback();
            }
        },
        error: function(xhr) {
            console.error('Lỗi khi load xã/phường:', xhr);
        }
    });
}

// Biến toàn cục để lưu danh sách ngân hàng
let banksList = [];

// Mapping logo ngân hàng
window.bankNameToLogo = window.bankNameToLogo || {};
window.bankShortToLogo = window.bankShortToLogo || {};
window.bankCodeToLogo = window.bankCodeToLogo || {};

/**
 * Hàm load danh sách ngân hàng từ API VietQR
 * @param {string} inputId - ID của input element để hiển thị
 * @param {string} placeholder - Text placeholder
 * @param {string} selectedValue - Giá trị đã chọn (nếu có)
 * @param {function} callback - Callback function sau khi load xong
 */
function loadBanks(inputId, placeholder = 'Chọn ngân hàng', selectedValue = '', callback = null) {
    const banksUrl = window.VIETQR_BANKS_URL || 'https://api.vietqr.io/v2/banks';
    
    $.ajax({
        url: banksUrl,
        type: 'GET',
        success: function(response) {
            if (response && response.data && Array.isArray(response.data)) {
                banksList = response.data;
                
                // Reset logo mappings
                window.bankNameToLogo = {};
                window.bankShortToLogo = {};
                window.bankCodeToLogo = {};
                
                // Tạo datalist options và lưu logo mapping (chỉ nếu có datalist)
                const $datalist = $('#bankList');
                if ($datalist.length) {
                    $datalist.empty();
                }
                
                response.data.forEach(function(bank) {
                    const optValue = bank.shortName ? bank.shortName : bank.name;
                    if ($datalist.length) {
                        $datalist.append(`<option value="${optValue}" data-code="${bank.code}">${bank.name}</option>`);
                    }
                    
                    // Lưu logo mapping
                    const logo = bank.logo || '';
                    if (bank.name && logo) {
                        window.bankNameToLogo[bank.name.toLowerCase()] = logo;
                    }
                    if (bank.shortName && logo) {
                        window.bankShortToLogo[bank.shortName.toLowerCase()] = logo;
                    }
                    if (bank.code && logo) {
                        window.bankCodeToLogo[bank.code.toLowerCase()] = logo;
                    }
                });
                
                // Set giá trị nếu có inputId và selectedValue
                if (inputId && selectedValue) {
                    $('#' + inputId).val(selectedValue);
                    // Cập nhật logo sau khi set giá trị
                    setTimeout(function() {
                        updateBankLogoForInput($('#' + inputId));
                    }, 100);
                }
                
                // Cập nhật logo cho tất cả các cell trong bảng
                updateBankLogosInTable();
                
                if (callback && typeof callback === 'function') {
                    callback();
                }
            }
        },
        error: function(xhr) {
            console.error('Lỗi khi load danh sách ngân hàng:', xhr);
            // Chỉ alert nếu đang trong form (có inputId)
            if (inputId) {
                alert('Không thể tải danh sách ngân hàng. Vui lòng thử lại sau.');
            }
        }
    });
}

/**
 * Hàm tìm logo ngân hàng theo text
 * @param {string} text - Tên ngân hàng, shortName hoặc code
 * @returns {string|null} - URL logo hoặc null
 */
window.resolveBankLogoByText = function(text) {
    if (!text) return null;
    const key = text.toLowerCase();
    // Ưu tiên tìm theo shortName trước
    return window.bankShortToLogo[key] || window.bankNameToLogo[key] || window.bankCodeToLogo[key] || null;
};

/**
 * Hàm chuyển đổi tên đầy đủ sang shortName (để xử lý dữ liệu cũ)
 * @param {string} fullName - Tên đầy đủ của ngân hàng
 * @returns {string|null} - shortName hoặc null nếu không tìm thấy
 */
window.convertFullNameToShortName = function(fullName) {
    if (!fullName || !banksList || banksList.length === 0) return null;
    const fullNameLower = fullName.toLowerCase();
    const foundBank = banksList.find(function(bank) {
        return bank.name && bank.name.toLowerCase() === fullNameLower;
    });
    return foundBank ? (foundBank.shortName || foundBank.name || foundBank.code || null) : null;
};

/**
 * Hàm cập nhật logo cho input ngân hàng trong form
 * @param {jQuery} $input - jQuery object của input element
 */
function updateBankLogoForInput($input) {
    if (!$input || !$input.length) return;
    
    const bankName = $input.val().trim();
    const logo = window.resolveBankLogoByText(bankName);
    
    // Tìm hoặc tạo img element
    let $img = $input.siblings('.bank-logo-preview');
    if (!$img.length) {
        $img = $('<img class="bank-logo-preview ms-2" alt="logo ngân hàng" style="height: 30px; vertical-align: middle; display: none;">');
        $input.parent().append($img);
    }
    
    if (logo) {
        $img.attr('src', logo).show();
    } else {
        $img.hide().attr('src', '');
    }
}

/**
 * Hàm cập nhật logo cho cell trong bảng
 * @param {jQuery} $td - jQuery object của td element
 */
window.updateBankLogoForCell = function($td) {
    if (!$td || !$td.length) return;
    
    const text = $td.find('.bank-name-text').text().trim() || $td.text().trim();
    const logo = window.resolveBankLogoByText(text);
    const $img = $td.find('img.bank-logo');
    
    if (!$img.length) {
        // Tạo img nếu chưa có
        const $imgNew = $('<img class="bank-logo ms-2" alt="logo ngân hàng" style="height: 30px; vertical-align: middle; display: none;">');
        $td.append($imgNew);
        if (logo) {
            $imgNew.attr('src', logo).show();
        }
    } else {
        if (logo) {
            $img.attr('src', logo).show();
        } else {
            $img.hide().attr('src', '');
        }
    }
};

/**
 * Hàm cập nhật logo cho tất cả các cell ngân hàng trong bảng
 */
function updateBankLogosInTable() {
    $('td[data-bank-name]').each(function() {
        const $td = $(this);
        const bankNameFromData = $td.data('bank-name');
        const $textSpan = $td.find('.bank-name-text');
        
        // Chuyển đổi tên đầy đủ sang shortName nếu cần (để xử lý dữ liệu cũ)
        if (bankNameFromData && typeof window.convertFullNameToShortName === 'function') {
            const shortName = window.convertFullNameToShortName(bankNameFromData);
            if (shortName && shortName !== bankNameFromData) {
                // Cập nhật text hiển thị thành shortName
                if ($textSpan.length) {
                    $textSpan.text(shortName);
                } else {
                    // Nếu không có span, cập nhật text của td (nhưng giữ lại data attribute)
                    const currentText = $td.text().trim();
                    if (currentText === bankNameFromData) {
                        $td.contents().filter(function() {
                            return this.nodeType === 3; // Text node
                        }).first().replaceWith(shortName);
                    }
                }
                // Cập nhật data attribute để nhất quán
                $td.attr('data-bank-name', shortName);
            }
        }
        
        window.updateBankLogoForCell($td);
    });
    
    // Cũng cập nhật cho các td chứa ngân hàng (nếu không có data attribute)
    $('table tbody tr').each(function() {
        const $td = $(this).find('td').eq(8); // Cột ngân hàng (index 8)
        if ($td.length) {
            window.updateBankLogoForCell($td);
        }
    });
}


