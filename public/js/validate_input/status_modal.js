/**
 * Validation functions for status_modal component
 */

/**
 * Validate số lượng trả linh kiện
 * @returns {Array|null} Mảng dữ liệu hợp lệ hoặc null nếu không hợp lệ
 */
function validateReturnQuantities() {
    let valid = true;
    const data = [];

    $('#componentTableBody tr').each(function () {
        const $input = $(this).find('input[type="number"]');
        const returnQty = parseInt($input.val());
        const detailId = $input.attr('name').match(/\d+/)[0];
        const sendQty = parseInt($(this).find('td').eq(1).text());

        if (isNaN(returnQty) || returnQty < 0 || returnQty > sendQty) {
            $input.addClass('is-invalid');
            valid = false;
        } else {
            $input.removeClass('is-invalid');
            data.push({ id: detailId, return_quantity: returnQty });
        }
    });

    return valid ? data : null;
}

