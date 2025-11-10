// Validation state
let validationErrors = {};

function showError($field, message) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;
    hideError($field);
    $field.addClass('is-invalid');
    $field.closest('.col-md-4').append(`<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`);
    validationErrors[fieldId] = true;
    updateButtonState();
}

function hideError($field) {
    let fieldId = $field.attr('id');
    if (!fieldId) return;
    $field.removeClass('is-invalid');
    $field.closest('.col-md-4').find(`.invalid-feedback[data-error-for="${fieldId}"]`).remove();
    delete validationErrors[fieldId];
    updateButtonState();
}

function updateButtonState() {
    let hasErrors = Object.keys(validationErrors).length > 0;
    $('#btnSearch').prop('disabled', hasErrors);
}

function validateSophieu() {
    const $input = $('#sophieu');
    const value = $input.val();
    hideError($input);
    if (value && !/^\d+$/.test(value)) {
        showError($input, "Số phiếu chỉ được nhập số.");
    } else if (value && value.length > 10) {
        showError($input, "Số phiếu không vượt quá 10 ký tự.");
    }
}

function validateSeri() {
    const $input = $('#seri');
    const value = $input.val();
    hideError($input);
    if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
        showError($input, "Seri chỉ nhập chữ và số.");
    } else if (value && value.length > 25) {
        showError($input, "Số seri không vượt quá 25 ký tự.");
    }
}

function validateProductName() {
    const $input = $('#product_name');
    const value = $input.val()?.trim() || '';
    hideError($input);
    const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹü\-\(\,+;/)]+$/;
    if (value && !validRegex.test(value)) {
        showError($input, "Tên sản phẩm chỉ nhập chữ và số, các ký tự cho phép.");
    } else if (value.length > 100) {
        showError($input, "Tên sản phẩm không vượt quá 100 ký tự.");
    }
}

function validateSdt() {
    const $input = $('#sdt');
    const value = $input.val();
    hideError($input);
    if (value && !/^0\d{9}$/.test(value)) {
        showError($input, "SĐT phải bắt đầu bằng 0 và có đúng 10 chữ số.");
    }
}

function validateKhachhang() {
    const $input = $('#khachhang');
    const value = $input.val()?.trim() || '';
    const nameRegex = /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;
    hideError($input);
    if (value && !nameRegex.test(value)) {
        showError($input, "Tên khách hàng chỉ nhập chữ.");
    } else if (value.length > 80) {
        showError($input, "Tên khách hàng không vượt quá 80 ký tự.");
    }
}

function validateKythuatvien() {
    const $input = $('#kythuatvien');
    const value = $input.val()?.trim() || '';
    const nameRegex = /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/;
    hideError($input);
    if (value && !nameRegex.test(value)) {
        showError($input, "Tên kỹ thuật viên chỉ nhập chữ.");
    } else if (value.length > 80) {
        showError($input, "Tên kỹ thuật viên không vượt quá 80 ký tự.");
    }
}

function loadTabData(tab, formData) {
    let url = (window.warrantyKuchenRoute || '') + "?tab=" + tab + "&" + formData;
    const brand = (window.brand || '');
    if (brand === 'hurom') {
        url = (window.warrantyHuromRoute || '') + "?tab=" + tab + "&" + formData;
    }
    $.get(url, function(response) {
        if (typeof response === 'object' && response.tab && response.table) {
            $('#warrantyTabs').html(response.tab);
            $('#tabContent').html(response.table);
            $('#warrantyTabs .nav-link').removeClass('active');
            $('#warrantyTabs .nav-link[data-tab="' + tab + '"]').addClass('active');
            localStorage.setItem('activeTab', tab);
        }
    });
}

$(document).ready(function() {
    // Bind validations
    $('#sophieu').on('input', validateSophieu);
    $('#seri').on('input', validateSeri);
    $('#product_name').on('input', validateProductName);
    $('#sdt').on('input', validateSdt);
    $('#khachhang').on('input', validateKhachhang);
    $('#kythuatvien').on('input', validateKythuatvien);

    // Tabs click
    $('#warrantyTabs').on('click', '.nav-link', function (e) {
        e.preventDefault();
        let tab = $(this).data('tab');
        let formData = $('#searchForm').serialize();
        loadTabData(tab, formData);
    });

    // Form submit
    $('#searchForm').on('submit', function (e) {
        e.preventDefault();
        let tab = localStorage.getItem('activeTab') || 'danhsach';
        let formData = $(this).serialize();
        loadTabData(tab, formData);
    });

    // Initial tab load from URL
    let urlParams = new URLSearchParams(window.location.search);
    let tabFromUrl = urlParams.get('tab') || 'danhsach';
    let formData = '';
    if (urlParams.get('kythuatvien')) {
        formData = 'kythuatvien=' + encodeURIComponent(urlParams.get('kythuatvien'));
        $('#kythuatvien').val('');
    } else if ($('#searchForm').length) {
        formData = $('#searchForm').serialize();
    }
    let page = urlParams.get('page');
    if (page) {
        formData += (formData ? '&' : '') + 'page=' + page;
    }
    loadTabData(tabFromUrl, formData);
    localStorage.setItem('activeTab', tabFromUrl);
});


