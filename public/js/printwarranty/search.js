function initSearchFormBindings() {
    const mainProductList = window.mainProductList || [];

    $('#tensp').on('input', function() {
        if (typeof validateTensp === 'function') {
            validateTensp();
        }

        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#tensp-suggestions');
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        const matchedProducts = mainProductList.filter(productName =>
            productName.toLowerCase().includes(keyword)
        );

        if (matchedProducts.length > 0) {
            matchedProducts.slice(0, 10).forEach(productName => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${productName}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    $(document).on('mousedown', '#tensp-suggestions button', function() {
        $('#tensp').val($(this).text());
        $('#tensp-suggestions').addClass('d-none');
        if (typeof validateTensp === 'function') {
            validateTensp();
        }
    });

    $('#sophieu').on('input', function() {
        if (typeof validateSophieu === 'function') {
            validateSophieu();
        }
    });
    $('#tungay, #denngay').on('change', function() {
        if (typeof validateDates === 'function') {
            validateDates();
        }
    });

    if (typeof validateSophieu === 'function') validateSophieu();
    if (typeof validateTensp === 'function') validateTensp();
    if (typeof validateDates === 'function') validateDates();
}

function Search() {
    $("#searchCard").on("click", function (e) {
        if (typeof validationErrors !== 'undefined') {
            if (typeof validateSophieu === 'function') validateSophieu();
            if (typeof validateTensp === 'function') validateTensp();
            if (typeof validateDates === 'function') validateDates();
            if (Object.keys(validationErrors).length > 0) {
                e.preventDefault();
                return;
            }
        }

        const sophieu = $("#sophieu").val();
        const tensp = $("#tensp").val();
        const tungay = $("#tungay").val();
        const denngay = $("#denngay").val();
        $.get(
            window.warrantyCardSearchRoute || "",
            {
                sophieu: sophieu,
                tensp: tensp,
                tungay: tungay,
                denngay: denngay,
            },
            function (html) {
                $("#tableContent").html(html);
            }
        );
    });

    $("#resetFilters").on("click", function (e) {
        e.preventDefault();
        // Xóa các input
        $("#sophieu").val("");
        $("#tensp").val("");
        $("#tungay").val("");
        $("#denngay").val("");
        $("#tensp-suggestions").empty().addClass("d-none");

        // Xóa thông báo lỗi validation (nếu có)
        if (typeof validationErrors !== 'undefined') {
            validationErrors = {};
            $(".is-invalid").removeClass("is-invalid");
            $(".invalid-feedback").remove();
            if (typeof updateButtonState === 'function') {
                updateButtonState();
            }
        }

        // Tải lại dữ liệu mặc định
        $.get(
            window.warrantyCardSearchRoute || "",
            {},
            function (html) {
                $("#tableContent").html(html);
            }
        );
    });
}


