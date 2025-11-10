/**
 * Agency Update Module
 * Xử lý cập nhật thông tin đại lý
 */

const CollaboratorInstallUpdateAgency = {
    /**
     * Update agency information
     * @param {string} orderCode - Order code
     * @param {Object} routes - Routes object với agencyUpdate
     */
    update: function(orderCode, routes) {
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            order_code: orderCode
        };
        
        let agencyPhone = '';
        $("td[data-agency]").each(function() {
            let $td = $(this);
            let agency = $td.data("agency");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
            }
            
            if (agency === "agency_phone") {
                agencyPhone = value;
            }
            
            if (agency === "agency_release_date" && value && value.includes('/')) {
                let parts = value.split('/');
                if (parts.length === 3) {
                    let day = parts[0].padStart(2, '0');
                    let month = parts[1].padStart(2, '0');
                    let year = parts[2];
                    value = year + '-' + month + '-' + day;
                }
            }
            
            data[agency] = value;
        });
        
        if (!agencyPhone) {
            return;
        }
        
        $.ajax({
            url: routes.agencyUpdate,
            method: "POST",
            data: data,
            success: function(response) {
                if (response.success) {
                    // Agency update successful
                }
            },
            error: function(xhr, status, error) {
                // Error updating agency
                console.error('Error updating agency:', error);
            }
        });
    }
};

