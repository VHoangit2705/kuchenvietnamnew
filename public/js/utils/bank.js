/**
 * Bank utility functions
 * Các hàm tiện ích cho xử lý ngân hàng và logo ngân hàng
 */

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
    
    performAjaxRequest({
        url: banksUrl,
        method: 'GET',
        onSuccess: function(response) {
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
        onError: function(xhr) {
            console.error('Lỗi khi load danh sách ngân hàng:', xhr);
            // Chỉ alert nếu đang trong form (có inputId)
            if (inputId) {
                showSwalMessage('error', 'Lỗi', 'Không thể tải danh sách ngân hàng. Vui lòng thử lại sau.');
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

