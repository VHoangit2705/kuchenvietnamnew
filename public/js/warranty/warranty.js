$(document).ready(function () {
    var activeTab = localStorage.getItem(window.activeTabStorageKey || "activeTab");
    if (activeTab) {
        $('[data-bs-target="' + activeTab + '"]').tab('show');
    }

    $('button[data-bs-toggle="tab"]').on('click', function () {
        var target = $(this).attr('data-bs-target');
        localStorage.setItem(window.activeTabStorageKey || "activeTab", target);
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('page');
        window.location.href = currentUrl.href;
    });

    $(document).on('click', '.pagination a', function (event) {
        event.preventDefault();
        var pageUrl = new URL($(this).attr('href'), window.location.origin);
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('page', pageUrl.searchParams.get('page'));
        var activeTab = localStorage.getItem(window.activeTabStorageKey || "activeTab");
        if (activeTab) {
            currentUrl.hash = activeTab;
        }
        window.location.href = currentUrl.href;
    });
});
