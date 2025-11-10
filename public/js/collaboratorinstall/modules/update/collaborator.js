/**
 * Collaborator Update Module
 * Xử lý cập nhật thông tin CTV
 */

const CollaboratorInstallUpdateCollaborator = {
    /**
     * Update collaborator information
     * @param {string} orderCode - Order code
     * @param {Object} routes - Routes object với ctvUpdate
     */
    update: function(orderCode, routes) {
        let id = $("#ctv_id").val();
        if (!id) {
            return;
        }
        
        let data = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            id: id,
            order_code: orderCode
        };
        
        $("td[data-field]").each(function() {
            let $td = $(this);
            let field = $td.data("field");
            let value;
            if ($td.find("input").length) {
                value = $td.find("input").val().trim();
            } else {
                value = $td.find(".text-value").text().trim();
            }
            
            if (field === 'ngaycap' && value && value.includes('/')) {
                let parts = value.split('/');
                if (parts.length === 3) value = parts[2] + '-' + parts[1] + '-' + parts[0];
            }
            
            data[field] = value;
        });

        $.ajax({
            url: routes.ctvUpdate,
            method: "POST",
            data: data,
            success: function(response) {
                // Collaborator updated successfully
            },
            error: function(xhr, status, error) {
                // Error updating collaborator
                console.error('Error updating collaborator:', error);
            }
        });
    }
};

