$(document).on('click', '.edit-serial-icon', function() {
    const td = $(this).closest('td');
    td.find('.serial-text').hide();
    td.find('.serial-input').removeClass('d-none').focus();
});

$(document).on('blur', '.serial-input', function() {
    const input = $(this);
    const newValue = input.val();
    const td = input.closest('td');
    const type = td.data('type');
    const id = td.data('id');
    const currentValue = td.find('.serial-text').text().trim();

    if (newValue === currentValue) {
        input.addClass('d-none');
        td.find('.serial-text').show();
        return;
    }
    
    $.ajax({
        url: (window.updateSerialRoute || ''),
        method: 'POST',
        data: { id: id, type: type, value: newValue },
        headers: { 'X-CSRF-TOKEN': (window.csrfToken || '') },
        success: function(response) {
            if(response.success){
                Swal.fire({
                    icon: 'success',
                    title: response.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    let displayValue = newValue;
                    if (type === 'return_date' || type === 'shipment_date') {
                        const date = new Date(newValue);
                        const day = String(date.getDate()).padStart(2, '0');
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const year = date.getFullYear();
                        displayValue = `${day}/${month}/${year}`;
                    }
                    td.find('.serial-text').text(displayValue).show();
                    input.addClass('d-none');
                });
            }
            else{
                Swal.fire({
                    icon: 'error',
                    title: response.message,
                    timer: 1000,
                    showConfirmButton: false
                }).then(() => {
                    let displayValue = response.old_value;
                    if (type === 'return_date' || type === 'shipment_date') {
                        const date = new Date(response.old_value);
                        const day = String(date.getDate()).padStart(2, '0');
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const year = date.getFullYear();
                        displayValue = `${day}/${month}/${year}`;
                    }
                    td.find('.serial-text').text(displayValue).show();
                    input.val('');
                    input.addClass('d-none');
                });
            }
        },
        error: function() {
            alert('Lỗi khi cập nhật serial!');
            input.focus();
        }
    });
});

