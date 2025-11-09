/**
 * Validation functions for printwarranty module
 */

/**
 * Validate form for printwarranty
 * @returns {boolean} true nếu hợp lệ, false nếu không
 */
function validateFormPrintWarranty() {
    const brand = window.brand || "";
    let productInput = $("#product").val().trim().toLowerCase();
    let productId = $("#product_id").val();
    let quantityInput = parseInt($("#quantity").val());

    $(".error").text("");

    if (!productInput) {
        $(".product_error").text("Sản phẩm không được để trống.");
        $("#product").focus();
        return false;
    }

    if (!productId) {
        $(".product_error").text("Vui lòng chọn sản phẩm từ danh sách gợi ý.");
        $("#product").focus();
        return false;
    }

    const productList = window.productList || [];
    let isValidProduct = productList.some(
        (p) =>
            p.product_name
                .trim()
                .replace(/\r?\n|\r/g, "")
                .toLowerCase() === productInput.replace(/\r?\n|\r/g, "") &&
            p.id == productId
    );

    if (!isValidProduct) {
        $(".product_error").text(
            "Sản phẩm không hợp lệ. Vui lòng chọn lại từ danh sách gợi ý."
        );
        $("#product").focus();
        return false;
    }

    if ($("#auto_serial").is(":checked")) {
        let quantityInput = parseInt($("#quantity").val());
        if (!quantityInput || quantityInput <= 0) {
            $(".quantity_error").text("Số lượng phải lơn hơn 0.");
            $("#quantity").focus();
            return false;
        }
    }
    if ($("#import_serial").is(":checked")) {
        let serial_range = $("#serial_range").val().trim();
        let error = validateSerialRanges(serial_range);
        let isValid = /^[A-Za-z0-9,\-\s]+$/.test(serial_range);
        if (!isValid) {
            $(".serial_range_error").text(
                "Chỉ được nhập chữ, số, dấu phẩy (,) và dấu gạch ngang (-)."
            );
            $("#serial_range").focus();
            return false;
        }

        if (error) {
            $(".serial_range_error").text(error);
            $("#serial_range").focus();
            return false;
        }
    }
    if ($("#import_excel").is(":checked")) {
        let file = $("#serial_file")[0].files[0];
        if (!file) {
            $(".serial_file_error").text("File không được để trống.");
            return false;
        }
    }

    return true;
}

/**
 * Validate serial ranges
 * @param {string} serialInput - Serial input string
 * @returns {string|null} Error message hoặc null nếu hợp lệ
 */
function validateSerialRanges(serialInput) {
    const cleanedInput = serialInput.toUpperCase().replace(/\n/g, ",").trim();
    const parts = cleanedInput
        .split(",")
        .map((s) => s.trim())
        .filter((s) => s);
    const allSerials = [];
    const duplicates = [];

    for (let range of parts) {
        if (range.includes("-")) {
            const [start, end] = range.split("-").map((s) => s.trim());

            if (start.length !== end.length) {
                return `Dải "${range}" không hợp lệ`;
            }

            const matchStart = start.match(/^([A-Z]*)(\d+)$/);
            const matchEnd = end.match(/^([A-Z]*)(\d+)$/);

            if (!matchStart || !matchEnd) {
                return `Dải "${range}" không hợp lệ`;
            }

            const prefixStart = matchStart[1];
            const numberStart = matchStart[2];
            const prefixEnd = matchEnd[1];
            const numberEnd = matchEnd[2];

            if (prefixStart !== prefixEnd && prefixEnd !== "") {
                return `Dải "${range}" không hợp lệ`;
            }

            if (parseInt(numberEnd) < parseInt(numberStart)) {
                return `Dải "${range}" không hợp lệ - số kết thúc nhỏ hơn số bắt đầu`;
            }

            // Tạo tất cả các số sê-ri trong phạm vi và kiểm tra các bản sao
            const length = numberStart.length;
            for (let i = parseInt(numberStart); i <= parseInt(numberEnd); i++) {
                const serial = prefixStart + i.toString().padStart(length, "0");
                if (allSerials.includes(serial)) {
                    duplicates.push(serial);
                } else {
                    allSerials.push(serial);
                }
            }
        } else {
            // Serial đơn
            if (allSerials.includes(range)) {
                duplicates.push(range);
            } else {
                allSerials.push(range);
            }
        }
    }

    // Kiểm tra các serial trùng lặp
    if (duplicates.length > 0) {
        return `Serial trùng lặp: ${duplicates.join(", ")}`;
    }

    return null;
}

