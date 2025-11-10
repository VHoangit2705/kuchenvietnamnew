/**
 * Location Filters Module
 * Xử lý filter CTV theo địa điểm (tỉnh/huyện/xã)
 */

const CollaboratorInstallLocationFilters = {
    routes: {},
    
    /**
     * Initialize location filters
     * @param {Object} routes - Routes object với getDistrict, getWard, filterCollaborators
     */
    init: function(routes) {
        this.routes = routes;
        this.setupProvinceFilter();
        this.setupDistrictFilter();
        this.setupWardFilter();
    },
    
    /**
     * Setup province filter
     */
    setupProvinceFilter: function() {
        const self = this;
        $('#province').on('change', function() {
            let provinceId = $(this).val();
            $('#district').empty().append('<option value="" selected>Quận/Huyện</option>');
            $('#ward').empty().append('<option value="" selected>Phường/Xã</option>');
            let url = self.routes.getDistrict.replace(':province_id', provinceId);
            if (provinceId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        let $district = $('#district');
                        $district.empty();
                        $district.append('<option value="" disabled selected>Quận/Huyện</option>');
                        data.forEach(function(item) {
                            $district.append('<option value="' + item.district_id + '">' + item.name + '</option>');
                        });
                    },
                });
            }
            self.filterCollaborators();
        });
    },
    
    /**
     * Setup district filter
     */
    setupDistrictFilter: function() {
        const self = this;
        $('#district').on('change', function() {
            let districtId = $(this).val();
            let url = self.routes.getWard.replace(':district_id', districtId);
            if (districtId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        let $ward = $('#ward');
                        $ward.empty();
                        $ward.append('<option value="" disabled selected>Xã/Phường</option>');
                        data.forEach(function(item) {
                            $ward.append('<option value="' + item.wards_id + '">' + item.name + '</option>');
                        });
                    },
                });
            }
            self.filterCollaborators();
        });
    },
    
    /**
     * Setup ward filter
     */
    setupWardFilter: function() {
        const self = this;
        $('#ward').change(function() {
            self.filterCollaborators();
        });
    },
    
    /**
     * Filter collaborators based on location
     */
    filterCollaborators: function() {
        let province = $('#province').val();
        let district = $('#district').val();
        let ward = $('#ward').val();
        $.ajax({
            url: this.routes.filterCollaborators,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                province: province,
                district: district,
                ward: ward
            },
            success: function(res) {
                $('#tablecollaborator').html(res.html);
            },
            error: function(xhr) {
                showSwalMessage('error', 'Lỗi', 'Lỗi khi xử lý');
            }
        });
    }
};

