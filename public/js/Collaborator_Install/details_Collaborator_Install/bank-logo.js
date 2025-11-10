/**
 * Xử lý logo ngân hàng từ VietQR API
 */

// Nạp danh sách ngân hàng từ VietQR API vào datalist
function loadBankList() {
    const banksUrl = window.BANKS_URL || "https://api.vietqr.io/v2/banks";
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
                updateBankLogoForCell($("#nganhang"));
                updateBankLogoForCell($("td[data-agency='agency_bank']"));
            })
            .catch(() => {});
    } catch (e) {}
}

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

