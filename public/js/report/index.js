$(document).ready(function () {
    initTooltipsAndResize();
    initReplacementSuggestions();
    initReportValidation();
    initTabSwitching();
    initFormSubmit();
});

function initTabSwitching() {
    // Xử lý click tab
    $(document).on("click", "#warrantyTabs .nav-link", function (e) {
        e.preventDefault();
        let tab = $(this).data("tab");
        // Update hidden input
        $("#activeTabInput").val(tab);
        let formData = $("#reportFilterForm").serialize();
        loadReportData(tab, formData);
    });
}

function initFormSubmit() {
    // Xử lý submit form
    $("#reportFilterForm").on("submit", function (e) {
        e.preventDefault();
        let activeTab =
            $("#warrantyTabs .nav-link.active").data("tab") || "warranty";
        // Update hidden input
        $("#activeTabInput").val(activeTab);
        let formData = $(this).serialize();
        loadReportData(activeTab, formData);
    });

    // Xử lý filter reportType riêng cho tab work_process
    // Xử lý sự kiện khi bấm nút "Xem danh sách" (#btnFilter)
    $(document).on("click", "#btnFilter", function (e) {
        e.preventDefault(); // Quan trọng: Ngăn form submit reload trang

        let activeTab = "work_process"; // Hoặc lấy dynamic: $('.nav-tabs .active').data('tab')

        // Lấy giá trị reportType riêng (nếu input này nằm ngoài form)
        let reportType = $("#reportType").val() || "weekly";

        // Lấy dữ liệu từ form
        // Lưu ý: Nếu input #reportType ĐÃ NẰM TRONG form thì không cần dòng "+= '&reportType...'" ở dưới
        let formData = $("#reportFilterForm").serialize();

        // Kiểm tra xem trong formData đã có reportType chưa, nếu chưa thì nối thêm
        if (formData.indexOf("reportType=") === -1) {
            formData += "&reportType=" + encodeURIComponent(reportType);
        }

        // Update hidden input (nếu cần thiết cho logic khác)
        $("#activeTabInput").val(activeTab);

        console.log("Đang lọc với data:", formData); // Debug xem data gửi đi đúng chưa

        // Gọi hàm load dữ liệu
        loadReportData(activeTab, formData);
    });

    // Hàm cập nhật text tuần/tháng (sẽ được gọi sau khi load dữ liệu)
    function updateReportPeriodInfo() {
        // Text sẽ được cập nhật tự động khi server trả về response.filter
        // Không cần xử lý thêm ở đây
    }

    // Xử lý Enter key trong select reportType
    $(document).on("keypress", "#reportType", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $("#btnFilterReportType").click();
        }
    });
}
