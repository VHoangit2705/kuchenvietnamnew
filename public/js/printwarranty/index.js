/**
 * JavaScript cho index.blade.php
 * File chính - khởi tạo và điều phối các chức năng
 */

if (typeof productList === 'undefined') {
    var productList = [];
}
if (typeof mainProductList === 'undefined') {
    var mainProductList = [];
}
if (typeof routes === 'undefined') {
    var routes = {
        create: '',
        search: '',
        partial: '',
        exportActiveWarranty: ''
    };
}

var isInitialized = false;

// Khởi tạo tất cả chức năng
function initPrintWarranty() {
    // Chỉ khởi tạo một lần
    if (isInitialized) {
        return;
    }
    
    // UI components
    OpenFormCreate();
    ProductInput();
    ShowHideComponents();
    
    // Form handling
    SubmitForm();
    setupSerialRangeInput();
    checkFile();
    
    // Search
    Search();
    setupMainProductSearch();
    setupResetFilters();
    
    // Validation
    setupModalValidation();
    setupSearchValidation();
    
    // Export
    setupExportActiveWarranty();
    
    isInitialized = true;
}