function ShowHideComponents() {
    function toggleSerialFields() {
        if ($("#auto_serial").is(":checked")) {
            $("#quantityInput").show();
            $("#serialRangeInput").hide();
            $("#fileExcel").hide();
        } else if ($("#import_serial").is(":checked")) {
            $("#quantityInput").hide();
            $("#serialRangeInput").show();
            $("#fileExcel").hide();
        } else {
            $("#fileExcel").show();
            $("#quantityInput").hide();
            $("#serialRangeInput").hide();
        }
    }
    toggleSerialFields();
    $('input[name="serial_option"]').on("change", toggleSerialFields);
}

function OpenFormCreate() {
    $("#openform").on("click", function (e) {
        e.preventDefault();
        $("#product_id").val("");
        $("#product").val("");
        $("#quantity").val("");
        $("#serial_range").val("");
        $(".error").text("");
        var myModal = new bootstrap.Modal(
            document.getElementById("warrantyModal")
        );
        myModal.show();
    });
}

function ProductInput() {
    const productList = window.productList || [];
    $("#product").on("input", function () {
        let keyword = $(this).val().toLowerCase().trim();
        let $suggestionsBox = $("#product_suggestions");
        $suggestionsBox.empty();

        $("#product_id").val("");

        if (!keyword) {
            $suggestionsBox.addClass("d-none");
            return;
        }

        let matchedProducts = productList.filter((p) =>
            p.product_name.toLowerCase().includes(keyword)
        );

        if (matchedProducts.length > 0) {
            matchedProducts.slice(0, 10).forEach((p) => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action" data-id="${p.id}">${p.product_name}</button>`
                );
            });
            $suggestionsBox.removeClass("d-none");
        } else {
            $suggestionsBox.addClass("d-none");
        }
    });
}

$(document).on("mousedown", "#product_suggestions button", function () {
    $("#product").val($(this).text());
    $("#product_id").val($(this).data("id"));
    $("#product_suggestions").addClass("d-none");
    if (typeof validateModalProduct === 'function') {
        validateModalProduct();
    }
});

$(document).on("click", function (e) {
    if (!$(e.target).closest("#product, #product_suggestions").length) {
        $("#product_suggestions").addClass("d-none");
    }
});


