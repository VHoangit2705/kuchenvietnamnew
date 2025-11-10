function Delete(id) {
    const routes = {
        delete: $('#tabContent').data('route-delete') || '',
        getList: $('#tabContent').data('route-getlist') || ''
    };

    Swal.fire({
        title: 'Xác nhận xoá',
        text: "Bạn có chắc chắn muốn xoá bản ghi này không?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xoá',
        cancelButtonText: 'Huỷ'
    }).then((result) => {
        if (result.isConfirmed) {
            if (routes.delete) {
                let url = routes.delete.replace(':id', id);
                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Notification('success', response.message, 1000, false);
                        if (routes.getList) {
                            $.get(routes.getList, function(html) {
                                $('#tabContent').html(html);
                                // Cập nhật logo ngân hàng sau khi load lại bảng
                                if (typeof updateBankLogosInTable === 'function') {
                                    updateBankLogosInTable();
                                } else if (typeof loadBanks === 'function' && window.bankNameToLogo && Object.keys(window.bankNameToLogo).length > 0) {
                                    updateBankLogosInTable();
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Lỗi', 'Có lỗi xảy ra khi xoá.', 'error');
                        console.error(xhr.responseText);
                    }
                });
            }
        }
    });
}

$(document).on('click', '.edit-row', function() {
    const routes = {
        getById: $('#tabContent').data('route-getbyid') || ''
    };

    const button = $(this);
    const id = button.data('id');
    
    if (routes.getById) {
        $.ajax({
            url: routes.getById,
            type: 'POST',
            data: {
                id: id
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                const item = response.data.collaborator;
                const province = response.data.provinces;
                const district = response.data.districts;
                const ward = response.data.wards;
                
                // Clear existing options
                $('#districtForm').empty();
                $('#wardForm').empty();
                
                district.forEach(function(item) {
                    $('#districtForm').append('<option value="' + item.district_id + '">' + item.name + '</option>');
                });
                ward.forEach(function(item) {
                    $('#wardForm').append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                });
                // Load danh sách ngân hàng trước khi set giá trị
                loadBanks('bank_nameForm', 'Chọn ngân hàng', '', function() {
                    // Sau khi load xong, set các giá trị
                    $('#tieude').text("Cập nhật cộng tác viên");
                    $('#hoantat').text('Cập nhật');
                    $('#full_nameForm').val(item.full_name);
                    $('#date_of_birth').val(item.date_of_birth ? formatDateToInput(item.date_of_birth) : '');
                    $('#phoneForm').val(item.phone);
                    $('#provinceForm').val(item.province_id);
                    $('#districtForm').val(item.district_id);
                    $('#wardForm').val(item.ward_id);
                    $('#address').val(item.address);
                    
                    // Chuyển đổi tên đầy đủ sang shortName nếu cần (để xử lý dữ liệu cũ)
                    let bankNameToDisplay = item.bank_name || '';
                    if (bankNameToDisplay && typeof window.convertFullNameToShortName === 'function') {
                        const shortName = window.convertFullNameToShortName(bankNameToDisplay);
                        if (shortName) {
                            bankNameToDisplay = shortName;
                        }
                    }
                    $('#bank_nameForm').val(bankNameToDisplay);
                    $('#bank_branchForm').val(item.chinhanh || '');
                    $('#bank_accountForm').val(item.sotaikhoan || '');
                    $('#id').val(item.id);
                    
                    // Cập nhật logo sau khi set giá trị
                    setTimeout(function() {
                        updateBankLogoForInput($('#bank_nameForm'));
                    }, 200);
                });
            },
            error: function(xhr) {
                alert('Có lỗi xảy ra khi lấy dữ liệu cộng tác viên.');
                console.log(xhr.responseText);
            }
        });
    }
});

