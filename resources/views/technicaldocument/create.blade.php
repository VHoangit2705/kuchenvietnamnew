@extends('layout.layout')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold text-primary"><i class="bi bi-plus-circle-fill me-2"></i>Thêm tài liệu kỹ thuật & hướng dẫn sửa chữa</h2>
                    <p class="text-muted mb-0">Chuẩn hóa mã lỗi, hướng dẫn sửa và tài liệu theo đúng sản phẩm – đúng model</p>
                </div>
                <div>
                    <a href="{{ route('warranty.document') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- BƯỚC 1–4: Chọn Danh mục → Sản phẩm → Xuất xứ → Mã SP -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="fw-bold text-secondary mb-0"><i class="bi bi-diagram-3 me-2"></i>Bước 1–4: Chọn thiết bị & model</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Danh mục sản phẩm <span class="text-danger">*</span></label>
                    <select class="form-select" id="createCategory">
                        <option value="">-- Chọn danh mục --</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name_vi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sản phẩm <span class="text-danger">*</span></label>
                    <select class="form-select" id="createProduct" disabled>
                        <option value="">-- Chọn sản phẩm --</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                        <span>Xuất xứ <span class="text-danger">*</span></span>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="btnAddOrigin" data-bs-toggle="modal" data-bs-target="#modalAddOrigin" disabled title="Thêm xuất xứ mới">
                            <i class="bi bi-plus-lg"></i> Thêm
                        </button>
                    </label>
                    <select class="form-select" id="createOrigin" disabled>
                        <option value="">-- Chọn xuất xứ --</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Mã sản phẩm (Model) <span class="text-danger">*</span></label>
                    <select class="form-select" id="createModelId" disabled>
                        <option value="">-- Chọn mã SP --</option>
                    </select>
                </div>
            </div>
            <p class="text-muted small mt-2 mb-0">Sau khi chọn đủ 4 bước, bạn có thể thêm mã lỗi và hướng dẫn sửa cho model này.</p>
        </div>
    </div>

    <div id="blockAfterModel" style="display: none;">
        <!-- BƯỚC 5: Thêm mã lỗi kỹ thuật -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-secondary mb-0"><i class="bi bi-bug me-2"></i>Bước 5: Thêm mã lỗi kỹ thuật</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddError">
                    <i class="bi bi-plus-lg me-1"></i>Thêm mã lỗi
                </button>
            </div>
            <div class="card-body">
                <div id="errorListContainer">
                    <p class="text-muted small mb-0">Chưa có mã lỗi nào. Bấm "Thêm mã lỗi" để thêm.</p>
                    <ul id="errorList" class="list-group list-group-flush mt-2"></ul>
                </div>
            </div>
        </div>

        <!-- BƯỚC 6–7: Hướng dẫn sửa & Gắn tài liệu -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold text-secondary mb-0"><i class="bi bi-wrench-adjustable me-2"></i>Bước 6–7: Hướng dẫn sửa & gắn tài liệu</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Chọn mã lỗi <span class="text-danger">*</span></label>
                    <select class="form-select" id="createErrorId">
                        <option value="">-- Chọn mã lỗi --</option>
                    </select>
                </div>
                <form id="guideForm">
                    <input type="hidden" id="guideErrorId" name="error_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu đề hướng dẫn <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="guideTitle" name="title" placeholder="VD: Tháo lắp và vệ sinh cảm biến LIDAR">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Các bước xử lý chuẩn <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="guideSteps" name="steps" rows="5" placeholder="Mỗi dòng một bước..."></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Thời gian sửa ước tính (phút)</label>
                            <input type="number" class="form-control" id="guideEstimatedTime" name="estimated_time" min="0" placeholder="30">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lưu ý an toàn</label>
                        <textarea class="form-control" id="guideSafetyNote" name="safety_note" rows="2" placeholder="Ngắt nguồn trước khi tháo..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><i class="bi bi-paperclip me-2"></i>Tài liệu / Ảnh / Video đính kèm</label>
                        <div class="border rounded-3 p-3 bg-light" style="border-style: dashed !important;">
                            <input type="file" class="form-control" id="docFiles" name="files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm">
                            <small class="text-muted">PDF, JPG, PNG, MP4 (khuyến nghị &lt; 20MB)</small>
                        </div>
                        <div id="uploadedDocList" class="mt-2"></div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu hướng dẫn & tài liệu</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnResetGuide">Làm mới form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Thêm xuất xứ -->
<div class="modal fade" id="modalAddOrigin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Thêm xuất xứ sản phẩm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAddOrigin">
                    <input type="hidden" name="product_id" id="originProductId" value="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Xuất xứ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="xuat_xu" placeholder="VD: Trung Quốc, Việt Nam" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mã model <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="model_code" placeholder="VD: KU-8881" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Phiên bản</label>
                            <input type="text" class="form-control" name="version" placeholder="VD: V1, 2024">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Năm phát hành</label>
                            <input type="number" class="form-control" name="release_year" placeholder="VD: 2024" min="1990" max="2100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm xuất xứ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Thêm mã lỗi -->
<div class="modal fade" id="modalAddError" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Thêm mã lỗi kỹ thuật</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAddError">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mã lỗi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="error_code" placeholder="VD: E-LIDAR, E01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên lỗi / Hiện tượng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="error_name" placeholder="VD: Lỗi cảm biến Lidar không quay" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mô tả / Điều kiện phát sinh</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Mô tả hiện tượng, điều kiện xảy ra lỗi..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mức độ</label>
                        <select class="form-select" name="severity">
                            <option value="normal">Thường</option>
                            <option value="common">Phổ biến</option>
                            <option value="critical">Nghiêm trọng</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm mã lỗi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/technicaldocument/create.css') }}">

<script>
    window.technicalDocumentCreateConfig = {
        csrfToken: "{{ csrf_token() }}",
        routes: {
            getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
            getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
            getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}",
            getErrorsByModel: "{{ route('warranty.document.getErrorsByModel') }}",
            storeOrigin: "{{ route('warranty.document.storeOrigin') }}",
            storeError: "{{ route('warranty.document.storeError') }}",
            storeRepairGuide: "{{ route('warranty.document.storeRepairGuide') }}"
        }
    };
</script>
<script src="{{ asset('js/technicaldocument/create.js') }}"></script>
@endsection
