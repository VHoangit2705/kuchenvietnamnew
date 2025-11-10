/**
 * Agency Data Management Module
 * Xử lý lưu và quản lý dữ liệu đại lý
 */

const CollaboratorInstallAgencyData = {
    originalData: {},
    routes: {},
    orderCode: '',
    
    /**
     * Initialize agency data management
     * @param {Object} config - Config object với routes, orderCode
     */
    init: function(config) {
        this.routes = config.routes || {};
        this.orderCode = config.orderCode || '';
        this.saveOriginal();
    },
    
    /**
     * Save original agency data
     */
    saveOriginal: function() {
        this.originalData = {
            agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
            agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
            agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
            agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
            agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
            agency_branch: $("td[data-agency='agency_branch'] .text-value").text().trim(),
            agency_cccd: $("td[data-agency='agency_cccd'] .text-value").text().trim(),
            agency_release_date: $("td[data-agency='agency_release_date'] .text-value").text().trim()
        };
    },
    
    /**
     * Check if has agency changes
     * @returns {boolean} True if has changes
     */
    hasChanges: function() {
        let currentAgencyData = {
            agency_name: $("td[data-agency='agency_name'] .text-value").text().trim(),
            agency_phone: $("td[data-agency='agency_phone'] .text-value").text().trim(),
            agency_address: $("td[data-agency='agency_address'] .text-value").text().trim(),
            agency_paynumber: $("td[data-agency='agency_paynumber'] .text-value").text().trim(),
            agency_bank: $("td[data-agency='agency_bank'] .text-value").text().trim(),
            agency_branch: $("td[data-agency='agency_branch'] .text-value").text().trim(),
            agency_cccd: $("td[data-agency='agency_cccd'] .text-value").text().trim(),
            agency_release_date: $("td[data-agency='agency_release_date'] .text-value").text().trim()
        };

        for (let field in this.originalData) {
            if (this.originalData[field] !== currentAgencyData[field]) {
                return true;
            }
        }
        
        return false;
    },
    
    /**
     * Save agency data before switch to CTV
     * @param {string} orderCode - Order code
     * @param {Object} routes - Routes object với agencyUpdate
     */
    saveBeforeSwitch: function(orderCode, routes) {
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            order_code: orderCode
        };
        
        $("td[data-agency]").each(function() {
            let $td = $(this);
            let agency = $td.data("agency");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
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
        
        if (!routes.agencyUpdate) {
            return;
        }
        
        $.ajax({
            url: routes.agencyUpdate,
            method: "POST",
            data: data,
            success: function(response) {
                console.log('Agency data saved before switch to CTV');
            },
            error: function(xhr, status, error) {
                console.log('Error saving agency data before switch:', error);
            }
        });
    }
};

