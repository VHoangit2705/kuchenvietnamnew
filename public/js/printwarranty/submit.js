// Submit form tạo phiếu bảo hành

function SubmitForm() {
    $('.submit-btn').off('click');
    
    $(document).off('click', '.submit-btn').on('click', '.submit-btn', function(e) {
        e.preventDefault();
        
        if (!validateModalForm()) return;

        if (!routes.create || routes.create === '') {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Route không hợp lệ. Vui lòng tải lại trang.'
            });
            return;
        }

        OpenWaitBox();
        let actionType = $(this).data('action');
        let product = $('#product').val();
        let product_id = $('#product_id').val();
        let quantity = $('#quantity').val();
        let serial_range = ($('#serial_range').val() ?? '').toUpperCase().replace(/\n/g, ',').trim();
        let serial_option = $('input[name="serial_option"]:checked').val();
        let serial_file = $('#serial_file')[0].files[0];
        let formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr("content"));
        formData.append('product', product);
        formData.append('product_id', product_id);
        formData.append('quantity', quantity);
        formData.append('serial_range', serial_range);
        formData.append('serial_file', serial_file);
        formData.append('serial_option', serial_option);
        
        $.ajax({
            url: routes.create,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                CloseWaitBox();
                if(response.success){
                    Swal.fire({
                        icon: 'success',
                        title: 'Thêm thành công',
                        timer: '3000',
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(()=>{
                        $.get(routes.partial, function(html) {
                            $('#tableContent').html(html);
                            if (typeof initTableBody === 'function') {
                                initTableBody();
                            } else if (typeof loadTableBodyScript === 'function') {
                                loadTableBodyScript();
                            }
                        });
                    
                        if (actionType === 'add') {
                            $('#product').val('').focus();
                            $('#quantity').val('');
                            $('#serial_range').val('');
                        } else if (actionType === 'close') {
                            $('#warrantyModal').modal('hide');
                        }
                    });
                }
                else{
                    Swal.fire({
                        icon: 'warning',
                        title: 'Lỗi trùng số seri',
                        text: response.message,
                        timer: '3000',
                        showConfirmButton: true,
                        confirmButtonText: 'Đã hiểu'
                    });
                }
            },
            error: function(xhr) {
                CloseWaitBox();
                let errorMsg = 'Lỗi khi lưu. Vui lòng kiểm tra lại.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: errorMsg
                });
                console.log(xhr.responseText);
            }
        });
    });
}

