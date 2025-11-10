$(document).ready(function () {
    const productList = window.productList || [];
    OpenFormCreate();
    ProductInput();
    SubmitForm();
    Search();
    setupModalValidation();
    ShowHideComponents();
    checkFile();
    sanitizeSerialRangeInput();
    initSearchFormBindings();
});