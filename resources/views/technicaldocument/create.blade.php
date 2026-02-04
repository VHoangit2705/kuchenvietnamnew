@extends('layout.layout')

@section('content')
<div class="container-fluid py-5 bg-light">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm">
                <div>
                    <h2 class="fw-bold text-primary mb-1"><i class="bi bi-folder-plus me-2"></i>Thêm Tài Liệu Kỹ Thuật</h2>
                    <p class="text-muted mb-0 small">Thiết lập dữ liệu sửa chữa chuẩn hóa cho từng Model sản phẩm</p>
                </div>
                <div>
                    <div class="d-flex gap-2">
                    <a href="{{ route('warranty.document') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-arrow-left me-1"></i>Quay lại tra cứu
                    </a>
                    <a href="{{ route('warranty.document.documents.index') }}" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-folder2-open me-1"></i>Quản lý tài liệu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-header bg-primary bg-gradient text-white py-3">
            <h6 class="fw-bold mb-0 text-uppercase ls-1"><i class="bi bi-crosshair me-2"></i>Bước 1: Xác định thiết bị</h6>
        </div>
        <div class="card-body p-4 bg-white">
            <x-technicaldocument.product-filter 
                :categories="$categories" 
                variant="desktop-floating" 
                idPrefix="create" 
                :showAddOriginButton="true"
            />
            <div class="mt-3 d-flex align-items-center text-muted small">
                <i class="bi bi-info-circle me-2"></i>
                <span>Vui lòng chọn đầy đủ thông tin từ trái sang phải để mở khóa các bước tiếp theo.</span>
            </div>
        </div>
    </div>

    <div id="blockAfterModel" style="display: none;">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-secondary mb-0 text-uppercase ls-1">Danh sách lỗi</h6>
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAddError">
                            <i class="bi bi-plus-lg me-1"></i>Thêm
                        </button>
                    </div>
                    <div class="card-body px-4">
                        <div id="errorListContainer" class="mt-2 custom-scrollbar" style="max-height: 500px; overflow-y: auto;">
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-bug fs-1 opacity-25"></i>
                                <p class="small mt-2">Chưa có mã lỗi nào.<br>Bấm "Thêm" để tạo mới.</p>
                            </div>
                            <ul id="errorList" class="list-group list-group-flush"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-white border-bottom pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-medical me-2 text-primary"></i>Soạn thảo hướng dẫn</h5>
                        <p class="text-muted small">Nhập chi tiết các bước sửa chữa và tài liệu đính kèm</p>
                    </div>
                    <div class="card-body p-4">
                        <form id="guideForm">
                            <input type="hidden" id="guideErrorId" name="error_id">
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary small text-uppercase">Áp dụng cho lỗi</label>
                                <select class="form-select form-select-lg bg-light border-0 fw-bold text-danger" id="createErrorId">
                                    <option value="">-- Vui lòng chọn mã lỗi bên trái --</option>
                                </select>
                                <div id="repairGuidesListCard" class="mt-3 card border-0 bg-light rounded-3 overflow-hidden" style="display: none;">
                                    <div class="card-header py-2 px-3 fw-semibold small text-secondary">Hướng dẫn sửa của mã lỗi này</div>
                                    <div id="repairGuidesList" class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;"></div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="guideTitle" name="title" placeholder="Tiêu đề">
                                        <label for="guideTitle">Tiêu đề hướng dẫn <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="number" class="form-control" id="guideEstimatedTime" name="estimated_time" min="0" placeholder="Phút">
                                        <label for="guideEstimatedTime">Thời gian (phút)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-floating">
                                    <textarea class="form-control" id="guideSteps" name="steps" style="height: 150px" placeholder="Các bước"></textarea>
                                    <label for="guideSteps">Các bước xử lý chuẩn <span class="text-danger">*</span></label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-floating">
                                    <textarea class="form-control text-danger border-danger-subtle" id="guideSafetyNote" name="safety_note" style="height: 80px" placeholder="Lưu ý"></textarea>
                                    <label for="guideSafetyNote" class="text-danger"><i class="bi bi-shield-exclamation me-1"></i>Lưu ý an toàn</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold mb-2">Tài liệu đính kèm</label>
                                <div class="upload-zone p-4 text-center border rounded-3 bg-light position-relative" style="border: 2px dashed #cbd5e1 !important;">
                                    <i class="bi bi-cloud-arrow-up fs-2 text-primary mb-2 d-block"></i>
                                    <span class="fw-semibold text-dark">Click hoặc kéo thả file vào đây</span>
                                    <p class="text-muted small mb-0">Hỗ trợ: PDF, JPG, PNG, MP4 (Max 20MB)</p>
                                    <input type="file" class="form-control position-absolute top-0 start-0 w-100 h-100 opacity-0" id="docFiles" name="files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm" style="cursor: pointer;">
                                </div>
                                <div id="uploadedDocList" class="mt-3 d-flex flex-wrap gap-2"></div>
                            </div>

                            <hr class="text-muted opacity-25">

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light text-secondary fw-semibold" id="btnResetGuide">Làm mới</button>
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm"><i class="bi bi-save me-2"></i>Lưu Hướng Dẫn</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddOrigin" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold">Thêm Xuất Xứ Mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formAddOrigin">
                    <input type="hidden" name="product_id" id="originProductId" value="">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="xuat_xu" id="modalOrigin" placeholder="Xuất xứ" required>
                        <label for="modalOrigin">Tên quốc gia/Xuất xứ <span class="text-danger">*</span></label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control fw-bold" name="model_code" id="modalModelCode" placeholder="Model" required>
                        <label for="modalModelCode">Mã Model (SKU) <span class="text-danger">*</span></label>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="version" id="modalVersion" placeholder="Version">
                                <label for="modalVersion">Phiên bản</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" name="release_year" id="modalYear" placeholder="Năm">
                                <label for="modalYear">Năm phát hành</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">Xác nhận thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddError" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="modalAddErrorTitle"><i class="bi bi-bug me-2"></i>Khai báo lỗi mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formAddError">
                    <input type="hidden" id="errorEditId" name="error_edit_id" value="">
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="form-floating">
                                <input type="text" class="form-control fw-bold text-danger" name="error_code" id="modalErrorCode" placeholder="Mã" required>
                                <label for="modalErrorCode">Mã lỗi</label>
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="form-floating">
                                <select class="form-select" name="severity" id="modalSeverity">
                                    <option value="normal">Thường</option>
                                    <option value="common">Phổ biến</option>
                                    <option value="critical">Nghiêm trọng</option>
                                </select>
                                <label for="modalSeverity">Mức độ</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="error_name" id="modalErrorName" placeholder="Tên lỗi" required>
                        <label for="modalErrorName">Tên lỗi / Hiện tượng <span class="text-danger">*</span></label>
                    </div>
                    <div class="form-floating mb-4">
                        <textarea class="form-control" name="description" id="modalDesc" placeholder="Mô tả" style="height: 100px"></textarea>
                        <label for="modalDesc">Mô tả chi tiết</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Lưu mã lỗi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/technicaldocument/create.css') }}">
<script>
    // ... Giữ nguyên config script cũ ...
    window.technicalDocumentCreateConfig = {
        csrfToken: "{{ csrf_token() }}",
        routes: {
            getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
            getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
            getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}",
            getErrorsByModel: "{{ route('warranty.document.getErrorsByModel') }}",
            getErrorById: "{{ url('baohanh/tailieukithuat/common-errors') }}",
            storeOrigin: "{{ route('warranty.document.storeOrigin') }}",
            storeError: "{{ route('warranty.document.storeError') }}",
            updateError: "{{ url('baohanh/tailieukithuat/common-errors') }}",
            destroyError: "{{ url('baohanh/tailieukithuat/common-errors') }}",
            storeRepairGuide: "{{ route('warranty.document.storeRepairGuide') }}",
            getRepairGuidesByError: "{{ route('warranty.document.repairGuides.byError') }}",
            editRepairGuide: "{{ url('baohanh/tailieukithuat/repair-guides/edit') }}",
            updateRepairGuide: "{{ url('baohanh/tailieukithuat/repair-guides') }}",
            destroyRepairGuide: "{{ url('baohanh/tailieukithuat/repair-guides') }}"
        }
    };
</script>
<script src="{{ asset('js/technicaldocument/create.js') }}"></script>
@endsection
