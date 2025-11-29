// JS cho warranty.blade.php
// Lưu/khôi phục tab đang active và giữ tab khi phân trang

$(document).ready(function () {
    // Khi load lại trang, tự active tab đã lưu
    var activeTab = localStorage.getItem("activeTab");
    if (activeTab) {
        $('[data-bs-target="' + activeTab + '"]').tab('show');
    }

    // Khi người dùng bấm tab, lưu tab đó vào localStorage và reset page
    $('button[data-bs-toggle="tab"]').on('click', function () {
        var target = $(this).attr('data-bs-target');
        localStorage.setItem("activeTab", target);

        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('page');
        window.location.href = currentUrl.href;
    });

    // Khi bấm phân trang, giữ lại tab hiện tại
    $(document).on('click', '.pagination a', function (event) {
        event.preventDefault();
        var pageUrl = new URL($(this).attr('href'), window.location.origin);
        var currentUrl = new URL(window.location.href);

        // Lấy query params hiện tại và set page
        currentUrl.searchParams.set('page', pageUrl.searchParams.get('page'));

        // Giữ tab hiện tại bằng hash
        var activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            currentUrl.hash = activeTab;
        }
        window.location.href = currentUrl.href;
    });
});

