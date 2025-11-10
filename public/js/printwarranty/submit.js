function SubmitForm() {
    $(".submit-btn").on("click", function (e) {
        e.preventDefault();
        if (typeof validateModalForm === 'function') {
            if (!validateModalForm()) return;
        }
        OpenWaitBox();
        let actionType = $(this).data("action");
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Thêm thành công',
                        timer: 3000,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
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
                    Swal.fire({
                        icon: 'warning',
                        title: 'Lỗi trùng số seri',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: true,
                        confirmButtonText: 'Đã hiểu'
                    });
                }
            },
            error: function (xhr) {
                CloseWaitBox();
                alert('Lỗi khi lưu. Vui lòng kiểm tra lại.');
                console.log(xhr.responseText);
            },
        });
    });
}


