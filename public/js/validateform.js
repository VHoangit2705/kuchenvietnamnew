// bắt buộc nhập
function validateRequired(formSelector) {
    let isValid = true;
    $(formSelector + ' .error').text(''); // Xóa lỗi cũ

    $(formSelector + ' [required]').each(function () {
        let $field = $(this);
        let value = $field.val();

        if (value === undefined || value === null) value = '';
        if (typeof value !== 'string') value = value.toString();
        let isEmpty = false;
        if ($field.is('select')) {
            isEmpty = (value === '');
        } else {
            isEmpty = (value.trim() === '');
        }

        if (isEmpty) {
            isValid = false;
            let $error = $field.siblings('.error');
            $error.text('Trường này là bắt buộc.');
        }
    });

    // Nếu là số điện thoại
    $(formSelector + ' input[type=text]#phone').each(function () {
        let result = validatePhoneNumber(this);
        if (!result.valid) {
            isValid = false;
            let $error = $(this).siblings('.error');
            $error.text(result.message);
        }
    })

    // Validate các input type=date có required
    $(formSelector + ' input[type=date]').each(function () {
        let result = validateDateInput(this);
        if (!result.valid) {
            isValid = false;
            let $error = $(this).siblings('.error');
            $error.text(result.message);
        }
    });

    return isValid;
}

// ngày tháng năm
function validateDateInput(selector) {
    let $input = $(selector);
    let val = $input.val();
    // Kiểm tra định dạng yyyy-mm-dd (HTML5 date input chuẩn trả về định dạng này)
    let regex = /^\d{4}-\d{2}-\d{2}$/;
    if (!regex.test(val)) {
        return { valid: false, message: 'Ngày không đúng định dạng.' };
    }

    // Kiểm tra ngày có hợp lệ hay không
    const parts = val.split('-');
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);

    // Tạo đối tượng Date JS
    const dateObj = new Date(year, month - 1, day);

    // Kiểm tra ngày hợp lệ: Date obj phải khớp đúng với giá trị nhập
    if (
        dateObj.getFullYear() !== year ||
        dateObj.getMonth() + 1 !== month ||
        dateObj.getDate() !== day
    ) {
        return { valid: false, message: 'Ngày không hợp lệ.' };
    }

    return { valid: true };
}
// số điện thoại
function validatePhoneNumber(selector) {
    let $input = $(selector);
    let val = $input.val().trim();
    let regex = /^[0-9]{10,11}$/;
    if (!regex.test(val)) {
        return {
            valid: false,
            message: 'Số điện thoại không hợp lệ. Chỉ gồm 10–11 chữ số.'
        };
    }
    return { valid: true };
}

// format date
function formatDateToInput(dateInput) {
    try {
        const date = new Date(dateInput);
        if (isNaN(date.getTime())) return ''; // Trả về chuỗi rỗng nếu không hợp lệ

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Tháng bắt đầu từ 0
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    } catch (e) {
        return '';
    }
}
