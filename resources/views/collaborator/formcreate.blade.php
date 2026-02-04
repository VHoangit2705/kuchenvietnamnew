<!-- Modal thêm mới cộng tác viên -->
<div class="modal fade" id="addCollaboratorModal" tabindex="-1" aria-labelledby="addCollaboratorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 id="tieude" class="modal-title" id="addCollaboratorLabel">Thêm mới cộng tác viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div id="formCreateCollaborator" data-id="">
                    <input type="text" id="id" name="id" value="" hidden>
                    <div class="form-group">
                        <label for="full_name" class="form-label mt-1">Họ tên (<span style="color: red;">*</span>)</label>
                        <input id="full_nameForm" name="full_name" type="text" class="form-control" placeholder="Họ tên" value="" required>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <!--<div class="form-group">-->
                    <!--    <label for="date_of_birth" class="form-label mt-1">Ngày sinh (<span style="color: red;">*</span>)</label>-->
                    <!--    <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" placeholder="Ngày sinh" value="" required>-->
                    <!--    <div class="error text-danger small mt-1"></div>-->
                    <!--</div>-->
                    <div class="form-group">
                        <label for="phone" class="form-label mt-1">Số điện thoại (<span style="color: red;">*</span>)</label>
                        <input id="phoneForm" name="phone" type="text" class="form-control" placeholder="Số điện thoại" value="" required>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="province" class="form-label mt-1">Chọn Tỉnh/TP (<span style="color: red;">*</span>)</label>
                        <select id="provinceForm" name="province" class="form-control" required>
                            <option value="" disabled selected>Tỉnh/TP</option>
                            @foreach ($lstProvince as $item)
                            <option value="{{ $item->province_id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="district" class="form-label mt-1">Chọn Quận/Huyện (<span style="color: red;">*</span>)</label>
                        <select id="districtForm" name="district" class="form-control" required>
                            <option value="" disabled selected>Quận/Huyện</option>
                        </select>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="ward" class="form-label mt-1">Chọn Xã/Phường (<span style="color: red;">*</span>)</label>
                        <select id="wardForm" name="ward" class="form-control" required>
                            <option value="" disabled selected>Xã/Phường</option>
                        </select>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label for="address" class="form-label mt-1">Địa chỉ (<span style="color: red;">*</span>)</label>
                        <textarea id="address" name="address" class="form-control" rows="2" maxlength="1024" required></textarea>
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <hr class="my-3">
                    <h6 class="mb-3">Thông tin ngân hàng</h6>
                    
                    <div class="form-group">
                        <label for="bank_account" class="form-label mt-1">Chủ tài khoản</label>
                        <input id="bank_account" name="bank_account" type="text" class="form-control" placeholder="Chủ tài khoản" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bank_name" class="form-label mt-1">Ngân hàng</label>
                        <input id="bank_name" name="bank_name" type="text" class="form-control" placeholder="Tên ngân hàng" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sotaikhoan" class="form-label mt-1">Số tài khoản</label>
                        <input id="sotaikhoan" name="sotaikhoan" type="text" class="form-control" placeholder="Số tài khoản" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="chinhanh" class="form-label mt-1">Chi nhánh</label>
                        <input id="chinhanh" name="chinhanh" type="text" class="form-control" placeholder="Chi nhánh" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <hr class="my-3">
                    <h6 class="mb-3">Thông tin CCCD/CMND</h6>
                    
                    <div class="form-group">
                        <label for="cccd" class="form-label mt-1">Số CCCD/CMND</label>
                        <input id="cccd" name="cccd" type="text" class="form-control" placeholder="Số CCCD/CMND" value="" maxlength="20">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ngaycap" class="form-label mt-1">Ngày cấp</label>
                        <input id="ngaycap" name="ngaycap" type="date" class="form-control" placeholder="Ngày cấp" value="">
                        <div class="error text-danger small mt-1"></div>
                    </div>
                    
                    <button id="hoantat" class="mt-1 btn btn-primary w-100">Thêm mới</button>
                </div>
            </div>
        </div>
    </div>
</div>
