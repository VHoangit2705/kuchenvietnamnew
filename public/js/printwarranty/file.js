// Kiểm tra file Excel

function checkFile() {
    $("#serial_file").on("change", function() {
        let file = this.files[0];
        let $errorDiv = $(".serial_file_error");
    
        if (!file) {
            $errorDiv.text("");
            return;
        }
    
        let allowedExtensions = [".xls", ".xlsx"];
        let fileName = file.name.toLowerCase();
        let isValid = allowedExtensions.some(ext => fileName.endsWith(ext));
    
        if (!isValid) {
            $errorDiv.text("File không hợp lệ! Vui lòng chọn file .xls, .xlsx");
            $(this).val("");
        } else {
            $errorDiv.text("");
        }
    });
}

