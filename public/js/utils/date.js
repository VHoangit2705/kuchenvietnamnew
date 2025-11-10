/**
 * Date utility functions
 * Các hàm tiện ích cho xử lý ngày tháng
 */

/**
 * Format date to input format (YYYY-MM-DD)
 * @param {string|Date} dateInput - Date string or Date object
 * @returns {string} Formatted date string (YYYY-MM-DD)
 */
function formatDateToInput(dateInput) {
    try {
        const date = new Date(dateInput);
        if (isNaN(date.getTime())) return ''; // Trả về chuỗi rỗng nếu không hợp lệ

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Tháng bắt đầu từ 0
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    } catch (e) {
        return '';
    }
}

/**
 * Format date to DD/MM/YYYY format
 * @param {string|Date} dateString - Date string or Date object
 * @returns {string} Formatted date string (DD/MM/YYYY)
 */
function formatDateToDMY(dateString) {
    const date = new Date(dateString);
    if (isNaN(date)) return '';
    
    const day = ('0' + date.getDate()).slice(-2);
    const month = ('0' + (date.getMonth() + 1)).slice(-2);
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
}

/**
 * Parse date from DD/MM/YYYY format
 * @param {string} dateStr - Date string in DD/MM/YYYY format
 * @returns {Date|null} Date object or null if invalid
 */
function parseDate(dateStr) {
    if (!dateStr || !/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) return null;
    const [day, month, year] = dateStr.split('/');
    const date = new Date(`${year}-${month}-${day}`);
    date.setHours(0, 0, 0, 0);
    return date;
}

