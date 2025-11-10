$(document).ready(function() {
    $('.timeline-header, .timeline-details').click(function() {
        toggleTimelineDetails($(this).closest('.timeline-item'));
    });
    $('#quantity, #unit_price').on('input', updateTotalPrice);
    bindSolutionChange();
    bindSaveRepair();
    bindPrintRequest();
    initReplacementSuggestions();
});

function bindPrintRequest(){
    $('#printRequest').on('click', function(e){
        e.preventDefault();
        let id = $(this).data('id');
        $.ajax({
            url: (window.requestPrintBase || '') + "/" + id,
            type: "GET",
            data: { _token: (window.csrfToken || '') },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã yêu cầu',
                        timer: 1500,
                        showConfirmButton: false
                    })
                }
            },
            error: function () {
                alert("Có lỗi xảy ra, vui lòng thử lại!");
            }
        });
    });
}

function bindSolutionChange(){
    $('#solution').on('change', function(){
        const solution = $(this).val();
        if (solution === 'Sửa chữa tại chỗ (lỗi nhẹ)') {
            $('#des_error_container').closest('.mb-2').removeClass('d-none');
        } else {
            $('#des_error_container').closest('.mb-2').addClass('d-none');
        }
        const $replacementLabel = $('#replacement-label');
        const $replacementInput = $('#replacement');
        const $replacementContainer = $replacementInput.closest('.mb-2');
        if (solution === 'Đổi mới sản phẩm') {
            $replacementLabel.text('Sản phẩm thay thế');
            $replacementInput.attr('placeholder', 'Nhập sản phẩm thay thế');
            window.currentReplacementList = window.sanphamList || [];
        } else if (solution === 'Thay thế linh kiện/hardware') {
            $replacementLabel.text('Linh kiện thay thế');
            $replacementInput.attr('placeholder', 'Nhập linh kiện thay thế');
            window.currentReplacementList = window.linhkienList || [];
        } else {
            $replacementContainer.addClass('d-none');
            return;
        }
        $replacementContainer.removeClass('d-none');
        $replacementInput.val('');
        $('#replacement-suggestions').addClass('d-none').empty();
    });
}

function ShowFormRepair() {
    resetRepairForm();
    $('#repairModal').modal('show');
}

function resetRepairForm() {
    $('#repairForm')[0].reset();
    $('#repairForm .form-control').removeClass('is-invalid');
    $('#repairForm .error').text('').addClass('d-none');
    $('#replacement-label').text('Linh kiện thay thế');
    $('#replacement').attr('placeholder', 'Nhập linh kiện thay thế');
    $('#replacement').closest('.mb-2').removeClass('d-none');
    window.currentReplacementList = window.linhkienList;
}

function bindSaveRepair() {
    $('#saveRepairBtn').on('click', function(e) {
        e.preventDefault();
        if (validateRepairForm()) {
            let formData = $('#repairForm').serialize();
            OpenWaitBox();
            $.ajax({
                url: (window.updatedetailRoute || ''),
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': (window.csrfToken || '')
                },
                success: function() {
                    CloseWaitBox();
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        resetRepairForm();
                        $('#repairModal').modal('hide');
                        location.reload();
                    });
                },
                error: function() {
                    CloseWaitBox();
                }
            });
        }
    });
}

function validateRepairForm() {
    let isValid = true;
    let errorType = $('#error_type').val().trim();
    if (errorType === '') {
        $('.error_type').text('Vui lòng nhập lỗi gặp phải').removeClass('d-none');
        isValid = false;
    } else {
        $('.error_type').text('').addClass('d-none');
    }
    let solution = $('#solution').val();
    if (!solution) {
        $('.error_sl').text('Vui lòng chọn cách xử lý').removeClass('d-none');
        isValid = false;
    } else {
        $('.error_sl').text('').addClass('d-none');
    }
    let replacement = $('#replacement').val().trim();
    if ((solution === 'Thay thế linh kiện/hardware' || solution === 'Đổi mới sản phẩm') && replacement === '') {
        const fieldName = solution === 'Đổi mới sản phẩm' ? 'sản phẩm thay thế' : 'linh kiện thay thế';
        $('.error_replace').text(`Vui lòng nhập ${fieldName}`).removeClass('d-none');
        isValid = false;
    } else {
        $('.error_replace').text('').addClass('d-none');
    }
    let quantity = parseInt($('#quantity').val()) || 0;
    if ((solution === 'Thay thế linh kiện/hardware' || solution === 'Đổi mới sản phẩm') && quantity < 1) {
        $('.error_quan').text('Số lượng phải >= 1').removeClass('d-none');
        isValid = false;
    } else {
        $('.error_quan').text('').addClass('d-none');
    }
    let price = parseInt($('#unit_price').val()) || 0;
    if ((solution === 'Thay thế linh kiện/hardware' || solution === 'Đổi mới sản phẩm') && price < 0) {
        $('.error_price').text('Đơn giá không hợp lệ').removeClass('d-none');
        isValid = false;
    } else {
        $('.error_price').text('').addClass('d-none');
    }
    return isValid;
}

function updateTotalPrice() {
    let quantity = parseInt($('#quantity').val()) || 0;
    let unitPrice = parseInt($('#unit_price').val()) || 0;
    let total = quantity * unitPrice;
    let formatted = total.toLocaleString('en-US', { minimumFractionDigits: 0 });
    $('#total_price').val(formatted);
}

function toggleTimelineDetails(timelineItem) {
    const details = timelineItem.find('.timeline-details');
    $('.timeline-details').not(details).animate({ height: 0 }, 10);
    $('.toggle-details').not(timelineItem.find('.toggle-details')).text('▼');
    if (details.height() > 0) {
        details.animate({ height: 0 }, 10);
        timelineItem.find('.toggle-details').text('▼');
    } else {
        const fullHeight = details.prop('scrollHeight');
        details.animate({ height: fullHeight }, 10);
        timelineItem.find('.toggle-details').text('▲');
    }
}

function initReplacementSuggestions() {
    window.currentReplacementList = window.linhkienList;
    $('#replacement').on('input', function() {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#replacement-suggestions');
        $suggestionsBox.empty();
        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }
        const matchedReplacement = (window.currentReplacementList || []).filter(p =>
            (p.product_name || '').toLowerCase().includes(keyword)
        );
        if (matchedReplacement.length > 0) {
            matchedReplacement.slice(0, 10).forEach(p => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    $(document).on('click', '#replacement-suggestions button', function() {
        $('#replacement').val($(this).text());
        $('#replacement-suggestions').addClass('d-none');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#replacement, #replacement-suggestions').length) {
            $('#replacement-suggestions').addClass('d-none');
        }
    });
}


