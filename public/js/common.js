/**
 * Common utility functions for all JavaScript files
 * This file loads all utility modules from utils/ directory
 * 
 * Load order:
 * 1. swal.js - SweetAlert functions
 * 2. ajax.js - AJAX functions (depends on swal.js)
 * 3. download.js - File download functions (depends on swal.js, ajax.js)
 * 4. date.js - Date utility functions
 * 5. validation.js - Validation functions
 * 6. autocomplete.js - Autocomplete functions
 * 7. ui.js - UI utility functions
 * 8. currency.js - Currency functions
 * 9. address.js - Address functions (depends on ajax.js)
 * 10. bank.js - Bank functions (depends on ajax.js, swal.js)
 */

(function() {
    'use strict';
    
    // Lấy đường dẫn base từ script hiện tại
    const currentScript = document.currentScript || document.querySelector('script[src*="common.js"]');
    let scriptSrc = currentScript ? currentScript.getAttribute('src') : '';
    
    // Xử lý đường dẫn: loại bỏ query string và hash nếu có
    if (scriptSrc) {
        scriptSrc = scriptSrc.split('?')[0].split('#')[0];
    }
    
    // Lấy base path (thư mục chứa common.js)
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
    
    const utilsPath = basePath + 'utils/';
    
    // Danh sách các file cần load theo thứ tự
    const requiredFiles = [
        utilsPath + 'swal.js',           // 1. SweetAlert functions
        utilsPath + 'ajax.js',           // 2. AJAX functions
        utilsPath + 'download.js',       // 3. File download functions
        utilsPath + 'date.js',           // 4. Date utility functions
        utilsPath + 'validation.js',     // 5. Validation functions
        utilsPath + 'autocomplete.js',   // 6. Autocomplete functions
        utilsPath + 'ui.js',             // 7. UI utility functions
        utilsPath + 'currency.js',       // 8. Currency functions
        utilsPath + 'address.js',        // 9. Address functions
        utilsPath + 'bank.js'            // 10. Bank functions
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
            script.async = false; // Load tuần tự, không async
            script.defer = false; // Không defer
            
            script.onload = function() {
                resolve();
            };
            script.onerror = function() {
                console.error('Lỗi khi load script:', src);
                reject(new Error('Failed to load script: ' + src));
            };
            
            // Append vào head để load ngay
            const firstScript = document.getElementsByTagName('script')[0];
            if (firstScript && firstScript.parentNode) {
                firstScript.parentNode.insertBefore(script, firstScript);
            } else {
                document.head.appendChild(script);
            }
        });
    }
    
    /**
     * Load tất cả các file theo thứ tự
     */
    function loadAllUtils() {
        let promiseChain = Promise.resolve();
        
        requiredFiles.forEach(function(file) {
            promiseChain = promiseChain.then(function() {
                return loadScript(file);
            });
        });
        
        promiseChain.then(function() {
            // Đánh dấu đã load xong
            window.commonUtilsLoaded = true;
            
            // Trigger event để các module khác biết
            const event = new CustomEvent('commonUtils:loaded');
            document.dispatchEvent(event);
        }).catch(function(error) {
            console.error('Lỗi khi load common utilities:', error);
        });
    }
    
    // Load ngay khi script được execute (không đợi DOMContentLoaded)
    // Sử dụng IIFE để đảm bảo load ngay lập tức
    loadAllUtils();
})();
