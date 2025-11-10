/**
 * File chính kết nối tất cả các module của details
 */

$(document).ready(function() {
    // Lưu giá trị ban đầu khi trang load
    saveOriginalCtvData();
    saveOriginalAgencyData();

    // Xử lý checkbox "Đại lý lắp đặt"
    $("#isInstallAgency").on("change", function() {
        // NÂNG CẤP: Xóa tất cả cờ lỗi và chạy lại validation
        validationErrors = {};
        $('.validation-error').remove();
        
        if ($(this).is(":checked")) {
            // Clear các trường CTV (không lưu giá trị hiện tại)
            clearCtvData();
            
            $(".installCostRow").show();
            $(".ctv_row").hide();
            $("#install_cost_row").hide();
            $("#install_file").hide();
            $("#table_collaborator").hide();
        } else {
            // Khôi phục giá trị ban đầu
            restoreOriginalCtvData();
            
            $(".installCostRow").hide();
            $(".error").hide(); // 'error' là class cũ, có thể xóa
            $("#table_collaborator").show();
            $(".ctv_row").show();
            $("#install_cost_row").show();
            $("#install_file").show();
        }
        
        // NÂNG CẤP: Chạy lại validation cho các trường
        runAllInitialValidations();
    });

    // Khởi tạo trạng thái ban đầu
    if ($("#isInstallAgency").is(":checked")) {
        $(".installCostRow").show();
        $(".ctv_row").hide();
        $("#install_cost_row").hide();
        $("#install_file").hide();
        $("#table_collaborator").hide();
    } else {
        $(".installCostRow").hide();
        $(".error").hide(); // 'error' là class cũ, có thể xóa
        $("#table_collaborator").show();
    }

    // Xử lý chọn CTV từ bảng
    $('#tablecollaborator').on('click', '.choose-ctv', function() {
        let id = $(this).data("id");
        $.ajax({
            url: (window.ROUTES.collaborator_show || '/collaborator/:id').replace(':id', id),
            method: "GET",
            success: function(res) {
                $("#ctv_name").text(res.full_name);
                $("#ctv_phone").text(res.phone);
                updateField("sotaikhoan", res.sotaikhoan);
                updateField("chinhanh", res.chinhanh);
                const bankName = res.nganhang || res.bank_name || '';
                updateField("nganhang", bankName);
                // Đảm bảo cập nhật logo ngay lập tức
                updateBankLogoForCell($("#nganhang"));
                updateField("cccd", res.cccd);
                updateField("ngaycap", res.ngaycap);

                $(".ctv_row").show();
                $("#install_cost_row").show();
                $("#ctv_id").val(id);
            },
            error: function() {
                alert("Lỗi!");
            }
        });
    });

    // --- NÂNG CẤP: Gắn validation cho các trường tĩnh ---

    // 1. Chi phí lắp đặt (cả CTV và Đại lý)
    $(".install_cost").on("input", function() {
        formatCurrency($(this)); // Định dạng tiền
        validateInstallCost($(this)); // Xác thực
    }).on("blur", function() {
        validateInstallCost($(this)); // Xác thực khi rời đi
    });
    
    // 2. Ngày hoàn thành (cả CTV và Đại lý)
    $("#successed_at_ctv, #successed_at").on("change", function() {
        validateCompletionDate($(this));
    }).on("blur", function() {
        validateCompletionDate($(this)); // Xác thực khi rời đi
    });
    
    // 3. Chạy validation ban đầu khi tải trang
    runAllInitialValidations();
    
    // --- KẾT THÚC NÂNG CẤP TRƯỜNG TĨNH ---

    Update();
    
    // Xử lý nút xem lịch sử
    $('#btnViewHistory').on('click', function() {
        loadHistory();
        $('#historyModal').modal('show');
    });

    // Nạp danh sách ngân hàng
    loadBankList();
});

