/**
 * History Loader Module
 * Xử lý tải lịch sử đơn hàng
 */

const CollaboratorInstallHistory = {
    /**
     * Load history for order
     * @param {string} orderCode - Order code
     * @param {string} historyUrl - History API URL
     */
    load: function(orderCode, historyUrl) {
        $('#historyLoading').show();
        $('#historyContent').hide();
        $('#historyEmpty').hide();
        
        if (!orderCode) {
            $('#historyLoading').hide();
            $('#historyEmpty').show();
            return;
        }
        
        const url = historyUrl.replace(':order_code', orderCode);
        
        $.ajax({
            url: url,
            method: "GET",
            success: (response) => {
                $('#historyLoading').hide();
                if (response.success && response.data.history.length > 0) {
                    // Import và sử dụng display module
                    if (typeof CollaboratorInstallHistoryDisplay !== 'undefined') {
                        CollaboratorInstallHistoryDisplay.display(response.data.history);
                    }
                    $('#historyContent').show();
                } else {
                    $('#historyEmpty').show();
                }
            },
            error: (xhr, status, error) => {
                $('#historyLoading').hide();
                $('#historyEmpty').show();
                console.error('Lỗi khi tải lịch sử:', error);
            }
        });
    }
};

