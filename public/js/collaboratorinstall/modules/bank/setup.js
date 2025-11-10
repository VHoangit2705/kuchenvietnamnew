/**
 * Bank Setup Module
 * Xử lý setup danh sách ngân hàng và logo
 */

const CollaboratorInstallBankSetup = {
    /**
     * Setup bank list
     * @param {Object} routes - Routes object (optional, for banksUrl)
     */
    setup: function(routes) {
        const self = this;
        // Đợi common utils load xong trước khi sử dụng loadBanks
        const setupBankListInternal = function() {
            if (typeof loadBanks === 'function') {
                // Sử dụng hàm loadBanks từ utils/bank.js
                loadBanks('', 'Chọn ngân hàng', '', function() {
                    // Cập nhật logo ban đầu nếu có giá trị sẵn
                    if (typeof window.updateBankLogoForCell === 'function') {
                        window.updateBankLogoForCell($("#nganhang"));
                        window.updateBankLogoForCell($("td[data-agency='agency_bank']"));
                    }
                });
            } else {
                // Fallback: load bằng fetch nếu loadBanks chưa có
                const banksUrl = (routes && routes.banksUrl) || window.VIETQR_BANKS_URL || 'https://api.vietqr.io/v2/banks';
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
                            if (typeof window.updateBankLogoForCell === 'function') {
                                window.updateBankLogoForCell($("#nganhang"));
                                window.updateBankLogoForCell($("td[data-agency='agency_bank']"));
                            }
                        })
                        .catch(() => {});
                } catch (e) {}
            }
        };
        
        // Kiểm tra xem utils đã load chưa
        if (window.commonUtilsLoaded && typeof loadBanks === 'function') {
            setupBankListInternal();
        } else {
            // Đợi event commonUtils:loaded
            document.addEventListener('commonUtils:loaded', function() {
                setupBankListInternal();
            });
            // Fallback: thử lại sau 500ms nếu event không trigger
            setTimeout(function() {
                if (typeof loadBanks === 'function') {
                    setupBankListInternal();
                }
            }, 500);
        }
    }
};

