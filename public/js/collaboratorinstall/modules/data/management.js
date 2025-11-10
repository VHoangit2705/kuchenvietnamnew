/**
 * Data Management Module
 * Module tổng hợp quản lý dữ liệu CTV và Agency
 */

const CollaboratorInstallDataManagement = {
    /**
     * Check if has agency changes
     * @returns {boolean} True if has changes
     */
    hasAgencyChanges: function() {
        if (typeof CollaboratorInstallAgencyData !== 'undefined' &&
            typeof CollaboratorInstallAgencyData.hasChanges === 'function') {
            return CollaboratorInstallAgencyData.hasChanges();
        }
        return false;
    }
};

