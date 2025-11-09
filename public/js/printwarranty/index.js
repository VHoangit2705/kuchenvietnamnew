const productList = window.productList || [];
$(document).ready(function () {
    OpenFormCreate();
    ProductInput();
    SubmitForm();
    Search();
    ShowHideComponents();
    checkFile();
    // xóa khoảng trắng
    const $serialRange = $("#serial_range");
    $serialRange.on("keydown", function (e) {
        if (e.key === " " || e.keyCode === 32) {
            e.preventDefault();
        }
    });
    $serialRange.on("paste", function (e) {
        e.preventDefault();
        const text = (e.originalEvent || e).clipboardData.getData("text");
        const cleanText = text.replace(/\s+/g, "");
        document.execCommand("insertText", false, cleanText);
    });
});

function checkFile() {
    $("#serial_file").on("change", function () {
        let file = this.files[0];
        let $errorDiv = $(".serial_file_error");

        if (!file) {
            $errorDiv.text("");
            return;
        }

        let allowedExtensions = [".xls", ".xlsx"];
        let fileName = file.name.toLowerCase();
        let isValid = allowedExtensions.some((ext) => fileName.endsWith(ext));

        if (!isValid) {
            $errorDiv.text("File không hợp lệ! Vui lòng chọn file .xls, .xlsx");
            $(this).val("");
        } else {
            $errorDiv.text("");
        }
    });
}

function ShowHideComponents() {
    // const brand = "{{ session('brand') }}";
    // if (brand === 'hurom'){
    //     $('#radTypeSerial').addClass('d-none');
    //     $('#quantityInput').addClass('d-none');
    // }
    // else{
    //     function toggleSerialFields() {
    //         if ($('#auto_serial').is(':checked')) {
    //             $('#quantityInput').show();
    //             $('#serialRangeInput').hide();
    //         } else {
    //             $('#quantityInput').hide();
    //             $('#serialRangeInput').show();
    //         }
    //     }
    //     toggleSerialFields();
    //     $('input[name="serial_option"]').on('change', toggleSerialFields);
    // }
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

function SubmitForm() {
    $(".submit-btn").on("click", function (e) {
        e.preventDefault();
        $(".error").text("");

        if (!validateFormPrintWarranty()) return;
        OpenWaitBox();
        let actionType = $(this).data("action"); // 'add' hoặc 'close'
        let product = $("#product").val();
        let product_id = $("#product_id").val();
        let quantity = $("#quantity").val();
        let serial_range = ($("#serial_range").val() ?? "")
            .toUpperCase()
            .replace(/\n/g, ",")
            .trim();
        let serial_option = $('input[name="serial_option"]:checked').val();
        let serial_file = $("#serial_file")[0].files[0];
        let formData = new FormData();
        formData.append("_token", window.csrfToken || "");
        formData.append("product", product);
        formData.append("product_id", product_id);
        formData.append("quantity", quantity);
        formData.append("serial_range", serial_range);
        formData.append("serial_file", serial_file);
        formData.append("serial_option", serial_option);

        $.ajax({
            url: window.warrantyCardCreateRoute || "",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                CloseWaitBox();
                if (response.success) {
                    showSwalMessage("success", "Thêm thành công", "", {
                        timer: 3000,
                        showConfirmButton: true,
                        confirmButtonText: "OK"
                    }).then(() => {
                        $.get(
                            window.warrantyCardPartialRoute || "",
                            function (html) {
                                $("#tableContent").html(html);
                            }
                        );

                        if (actionType === "add") {
                            $("#product").val("").focus();
                            $("#quantity").val("");
                            $("#serial_range").val("");
                        } else if (actionType === "close") {
                            $("#warrantyModal").modal("hide");
                        }
                    });
                } else {
                    showSwalMessage("warning", "Lỗi trùng số seri", response.message, {
                        timer: 3000,
                        showConfirmButton: true,
                        confirmButtonText: "Đã hiểu"
                    });
                }
            },
            error: function (xhr) {
                handleAjaxError(xhr);
            },
        });
    });
}

/**
 * NOTE: Các hàm validateForm và validateSerialRanges đã được di chuyển sang /js/validate_input/printwarranty.js
 * Vui lòng đảm bảo file sau được load trước file này:
 * - /js/validate_input/printwarranty.js
 */

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
    $("#product").on("input", function () {
        let keyword = $(this).val().toLowerCase().trim();
        let $suggestionsBox = $("#product_suggestions");
        $suggestionsBox.empty();

        // Clear product_id when user types
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
// Khi người dùng chọn sản phẩm gợi ý
$(document).on("mousedown", "#product_suggestions button", function () {
    $("#product").val($(this).text());
    $("#product_id").val($(this).data("id"));
    $("#product_suggestions").addClass("d-none");
});
// Ẩn gợi ý khi click ra ngoài
$(document).on("click", function (e) {
    if (!$(e.target).closest("#product, #product_suggestions").length) {
        $("#product_suggestions").addClass("d-none");
    }
});

function Search() {
    $("#searchCard").on("click", function () {
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
}

$("#exportActiveWarranty").on("click", function (e) {
    e.preventDefault();
    const tungay = $("#tungay").val();
    const denngay = $("#denngay").val();
    const queryParams = new URLSearchParams({
        fromDate: tungay,
        toDate: denngay,
    });
    
    const url = `${window.baoCaoKichHoatBaoHanhRoute || ""}?${queryParams.toString()}`;
    
    downloadFile(url, {
        defaultFilename: "Báo cáo kích hoạt bảo hành.xlsx",
        acceptHeader: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        useSwalLoading: false
    });
});
