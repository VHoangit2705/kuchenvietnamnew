@extends('layout.layout')

@section('content')
<div class="container-fluid py-5 bg-light min-vh-100">
    <div class="row mb-5">
        <div class="col-md-8 mx-auto text-center">
            <h1 class="fw-bold text-primary mb-2">Tra Cứu Lỗi Kỹ Thuật</h1>
            <p class="text-muted">Hệ thống cơ sở dữ liệu sửa chữa & bảo hành chính hãng</p>
        </div>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-xl-10">
            <div class="card border-0 shadow-lg rounded-pill overflow-hidden d-none d-lg-block">
                <div class="card-body p-1">
                    <div class="row g-0 align-items-center">
                        <div class="col-3 border-end">
                            <select class="form-select border-0 py-3 ps-4 fw-semibold" id="categorySelect" style="border-radius: 30px 0 0 30px;">
                                <option selected disabled>Danh mục sản phẩm</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name_vi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3 border-end">
                            <select class="form-select border-0 py-3 ps-3" id="productNameSelect">
                                <option selected disabled>Chọn sản phẩm...</option>
                            </select>
                        </div>
                        <div class="col-2 border-end">
                            <select class="form-select border-0 py-3 ps-3" id="originSelect" disabled>
                                <option selected disabled>Xuất xứ...</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <select class="form-select border-0 py-3 ps-3 fw-bold text-primary" id="productCodeSelect" disabled>
                                <option selected disabled>Mã Model...</option>
                            </select>
                        </div>
                        <div class="col-1 pe-1">
                            <button class="btn btn-primary w-100 rounded-pill py-3 h-100" type="button" id="btnSearch" disabled>
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow rounded-4 d-lg-none">
                <div class="card-body p-3">
                    <div class="d-grid gap-3">
                        <select class="form-select" id="categorySelect_m"><option>Danh mục...</option></select>
                        <div class="alert alert-info small mb-0"><i class="bi bi-pc-display me-1"></i> Vui lòng sử dụng máy tính để có trải nghiệm tra cứu tốt nhất.</div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="{{ route('warranty.document.create') }}" class="btn btn-link text-decoration-none text-secondary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Thêm dữ liệu mới
                </a>
                <span class="text-muted mx-2">|</span>
                <button class="btn btn-link text-decoration-none text-secondary btn-sm" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Làm mới bộ lọc
                </button>
            </div>
        </div>
    </div>

    <div id="resultsSection" class="container-xl px-0">
        <div class="d-flex align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0 me-auto">Kết quả tra cứu</h5>
            </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="errorTableCard">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="errorTable">
                    <thead class="bg-light text-uppercase text-secondary small">
                        <tr>
                            <th class="py-3 ps-4" style="width: 50px;">#</th>
                            <th class="py-3" style="min-width: 100px;">Mã lỗi</th>
                            <th class="py-3">Hiện tượng / Tên lỗi</th>
                            <th class="py-3" style="width: 120px;">Mức độ</th>
                            <th class="py-3">Mô tả sơ bộ</th>
                            <th class="py-3 text-center" style="width: 100px;">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody id="errorTableBody" class="bg-white">
                        <tr id="placeholderRow">
                            <td colspan="6" class="text-center py-5">
                                <div class="py-4">
                                    <img src="https://cdn-icons-png.flaticon.com/512/6104/6104865.png" alt="Search" width="80" class="opacity-25 mb-3">
                                    <h6 class="text-muted fw-normal">Vui lòng chọn đầy đủ thông tin ở thanh tìm kiếm phía trên</h6>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="emptyState" class="text-center py-5 d-none">
            <div class="card border-0 bg-transparent">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="bi bi-inbox-fill text-muted display-1 opacity-25"></i>
                    </div>
                    <h5 class="text-secondary">Không tìm thấy dữ liệu</h5>
                    <p class="text-muted">Model này chưa được cập nhật mã lỗi nào.</p>
                    <a href="{{ route('warranty.document.create') }}" class="btn btn-outline-primary rounded-pill mt-2">
                        <i class="bi bi-plus-lg me-1"></i>Đóng góp dữ liệu ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="errorDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body p-0">
                <div class="row g-0 h-100">
                    <div class="col-lg-7 d-flex flex-column h-100">
                        <div class="p-4 border-bottom bg-white sticky-top">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill border border-danger-subtle" id="detailErrorCode">E-00</span>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <h3 class="fw-bold text-dark mb-2" id="detailErrorName">Tên lỗi sản phẩm</h3>
                            <p class="text-muted mb-0" id="detailDescription">Mô tả chi tiết về hiện tượng...</p>
                        </div>
                        
                        <div class="p-4 flex-grow-1 bg-light custom-scrollbar" style="overflow-y: auto;">
                            <div class="card border-0 shadow-sm rounded-3 mb-4">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-primary text-uppercase small mb-3"><i class="bi bi-tools me-2"></i>Quy trình xử lý</h6>
                                    <div id="detailSolution" class="tech-steps">
                                        </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <h6 class="fw-bold text-secondary text-uppercase small mb-3"><i class="bi bi-paperclip me-2"></i>Tài liệu đính kèm</h6>
                                <div class="list-group list-group-flush rounded-3 border-0" id="detailDocuments">
                                    </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 bg-dark d-flex flex-column justify-content-center align-items-center position-relative">
                        <div id="mediaCarousel" class="carousel slide w-100 h-100" data-bs-ride="false"> <div class="carousel-inner h-100 d-flex align-items-center" id="detailMediaInner">
                                </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#mediaCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#mediaCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                            </button>
                        </div>
                        
                        <div class="position-absolute bottom-0 start-0 w-100 p-3 bg-gradient-dark text-center">
                            <a href="#" class="btn btn-light rounded-pill btn-sm fw-bold shadow" id="btnDownloadAll">
                                <i class="bi bi-download me-2"></i>Tải toàn bộ tài liệu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview PDF -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="pdfPreviewTitle">Xem tài liệu PDF</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfPreviewIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview Ảnh -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0 bg-dark bg-opacity-75 text-white">
                <h5 class="modal-title" id="imagePreviewTitle">Xem hình ảnh</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="imagePreviewImg" src="" alt="Preview" class="img-fluid" style="max-height: 85vh; width: auto;">
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Scrollbar for cleaner look */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    
    /* Tech Steps Styling */
    .tech-steps ol { padding-left: 1.2rem; }
    .tech-steps li { margin-bottom: 0.5rem; color: #333; }
    
    /* Floating Search Bar Tweaks */
    .form-select:focus { box-shadow: none; background-color: #f8f9fa; }
    
    /* Modal Image Fit */
    .carousel-item img { max-height: 80vh; object-fit: contain; width: 100%; }
</style>

<script>
    window.technicalDocumentIndexConfig = {
        routes: {
            getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
            getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
            getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}",
            getErrorsByModel: "{{ route('warranty.document.getErrorsByModel') }}",
            getErrorDetail: "{{ route('warranty.document.getErrorDetail') }}",
            downloadAllDocuments: "{{ route('warranty.document.downloadAllDocuments') }}"
        }
    };
</script>
<script src="{{ asset('js/technicaldocument/index.js') }}"></script>
@endsection
