/**
 * Base validation file - Load tất cả các file validate cần thiết
 * 
 * File này được import trong layout.blade.php và sẽ tự động load các file validate base.
 * Các file module-specific (formwarranty.js, checkwarranty.js, report.js, etc.) 
 * sẽ được load trong các view riêng khi cần.
 * 
 * Thứ tự load:
 * 1. common.js - Các hàm utility chung 
 *    (validatePhoneNumber, validateDateInput, formatDateToInput, parseDate, formatDateToDMY)
 * 2. validate_input/helpers.js - Các hàm showError, hideError
 * 3. validate_input/common.js - Các hàm validate chung 
 *    (validateRequired, validateRequiredFields)
 * 
 * Sau khi load xong, sẽ trigger event 'validation:loaded' để các module khác biết.
 * 
 * Cách sử dụng trong các view:
 * - Đợi event 'validation:loaded' hoặc kiểm tra window.validationFilesLoaded
 * - Load các file validate module-specific:
 *   <script src="{{ asset('js/validate_input/formwarranty.js') }}"></script>
 *   <script src="{{ asset('js/validate_input/checkwarranty.js') }}"></script>
 *   etc.
 */

(function() {
    'use strict';
    
    // Lấy đường dẫn base từ script hiện tại
    const currentScript = document.currentScript || document.querySelector('script[src*="validateform.js"]');
    let scriptSrc = currentScript ? currentScript.getAttribute('src') : '';
    
    // Xử lý đường dẫn: loại bỏ query string và hash nếu có
    if (scriptSrc) {
        scriptSrc = scriptSrc.split('?')[0].split('#')[0];
    }
    
    // Lấy base path (thư mục chứa validateform.js)
    let basePath = '/js/';
    if (scriptSrc) {
        const lastSlashIndex = scriptSrc.lastIndexOf('/');
        if (lastSlashIndex !== -1) {
            basePath = scriptSrc.substring(0, lastSlashIndex + 1);
        }
    }
    
    // Đảm bảo basePath kết thúc bằng /
    if (!basePath.endsWith('/')) {
        basePath += '/';
    }
    
    const validateInputPath = basePath + 'validate_input/';
    
    // Danh sách các file cần load theo thứ tự
    const requiredFiles = [
        basePath + 'common.js',                    // 1. Common utilities
        validateInputPath + 'helpers.js',          // 2. Validation helpers (showError, hideError)
        validateInputPath + 'common.js'            // 3. Common validation functions
    ];
    
    /**
     * Load script file và trả về Promise
     * @param {string} src - Đường dẫn file
     * @returns {Promise}
     */
    function loadScript(src) {
        return new Promise(function(resolve, reject) {
            // Kiểm tra xem script đã được load chưa
            const existingScript = document.querySelector(`script[src="${src}"]`);
            if (existingScript) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = src;
            script.async = false; // Load đồng bộ để đảm bảo thứ tự
            script.onload = resolve;
            script.onerror = function() {
                console.warn('Không thể load file:', src);
                resolve(); // Vẫn resolve để không block các file khác
            };
            document.head.appendChild(script);
        });
    }
    
    /**
     * Load tất cả các file cần thiết
     */
    async function loadValidationFiles() {
        // Chờ jQuery và các thư viện khác load xong
        if (typeof jQuery === 'undefined') {
            // Nếu jQuery chưa load, đợi một chút
            await new Promise(resolve => {
                const checkJQuery = setInterval(() => {
                    if (typeof jQuery !== 'undefined') {
                        clearInterval(checkJQuery);
                        resolve();
                    }
                }, 50);
                
                // Timeout sau 5 giây
                setTimeout(() => {
                    clearInterval(checkJQuery);
                    resolve();
                }, 5000);
            });
        }
        
        // Load các file theo thứ tự
        for (const file of requiredFiles) {
            await loadScript(file);
        }
        
        // Đánh dấu đã load xong
        window.validationFilesLoaded = true;
        
        // Trigger event để các module khác biết đã load xong
        if (typeof jQuery !== 'undefined') {
            $(document).trigger('validation:loaded');
        }
    }
    
    // Bắt đầu load khi DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadValidationFiles);
    } else {
        // DOM đã ready
        loadValidationFiles();
    }
    
    // Export các hàm validate chính để có thể sử dụng ngay (nếu đã load)
    // Các hàm này sẽ được định nghĩa trong các file được load
    window.validateRequired = window.validateRequired || function(formSelector) {
        console.warn('validateRequired chưa được load. Vui lòng đợi validation files load xong.');
        return false;
    };
    
})();
