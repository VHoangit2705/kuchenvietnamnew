$(document).ready(function() {
    initTooltipsAndResize();
    initReplacementSuggestions();
    initReportValidation();
    initTabSwitching();
    initFormSubmit();
});

function initTabSwitching() {
    // Xử lý click tab
    $(document).on('click', '#warrantyTabs .nav-link', function (e) {
        e.preventDefault();
        let tab = $(this).data('tab');
        // Update hidden input
        $('#activeTabInput').val(tab);
        let formData = $('#reportFilterForm').serialize();
        loadReportData(tab, formData);
    });
}

function initFormSubmit() {
    // Xử lý submit form
    $('#reportFilterForm').on('submit', function (e) {
        e.preventDefault();
        let activeTab = $('#warrantyTabs .nav-link.active').data('tab') || 'warranty';
        // Update hidden input
        $('#activeTabInput').val(activeTab);
        let formData = $(this).serialize();
        loadReportData(activeTab, formData);
    });
}


