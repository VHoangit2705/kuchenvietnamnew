/**
 * Address utility functions
 * Các hàm tiện ích cho load địa chỉ (tỉnh/thành phố, quận/huyện, xã/phường)
 */

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
    
    performAjaxRequest({
        url: url,
        method: 'GET',
        onSuccess: function(data) {
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
        onError: function(xhr) {
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
    
    performAjaxRequest({
        url: url,
        method: 'GET',
        onSuccess: function(data) {
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
        onError: function(xhr) {
            console.error('Lỗi khi load xã/phường:', xhr);
        }
    });
}

