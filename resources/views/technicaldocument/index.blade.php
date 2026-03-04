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
                {{-- Desktop View: Horizontal search bar --}}
                <div class="d-none d-lg-block">
                    <x-technicaldocument.product-filter :categories="$categories" variant="desktop-pill" idPrefix="" />
                </div>

                <div class="d-lg-none">
                    <x-technicaldocument.product-filter :categories="$categories" variant="mobile" idPrefix="m" />
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3 mt-4">
                    {{-- Style xanh lá nhạt cho hành động thêm --}}
                    @can('technical_document.manage')
                        <a href="{{ route('warranty.document.create') }}" class="btn border-0 text-success fw-bold px-3 py-2"
                            style="background-color: #d1e7dd;">
                            <i class="bi bi-plus-square-fill me-2"></i><span class="d-none d-sm-inline">Thêm mã lỗi</span><span
                                class="d-sm-none">Thêm</span>
                        </a>
                    @endcan

                    {{-- Style xanh dương nhạt cho tài liệu --}}
                    @if(!Auth::check() || Auth::user()->can('technical_document.view'))
                        <a href="{{ route('warranty.document.documents.index') }}"
                            class="btn border-0 text-primary fw-bold px-3 py-2" style="background-color: #cfe2ff;">
                            <i class="bi bi-folder-fill me-2"></i><span class="d-none d-sm-inline">Quản lý tài liệu</span><span
                                class="d-sm-none">Quản lý</span>
                        </a>
                    @endif

                    @if(!Auth::check() || Auth::user()->can('technical_document.view'))
                    <a href="{{ route('warranty.document.shelfList') }}" class="btn border-0 text-success fw-bold px-3 py-2 text-dark"
                            style="background-color: #fffc5b;">
                            <i class="bi bi-send-fill me-2"></i><span class="d-none d-sm-inline">Danh sách sản phẩm mới lên kệ</span><span
                                class="d-sm-none">Danh sách sản phẩm mới lên kệ</span>
                        </a>
                    @endif

                    <a href="{{ route('warranty.document.content_reviews.index') }}" class="btn border-0 text-success fw-bold px-3 py-2 text-dark"
                            style="background-color: #fffc5b;">
                            <i class="bi bi-check-circle-fill me-2"></i><span class="d-none d-sm-inline">Duyệt nội dung sản phẩm</span><span
                                class="d-sm-none">Duyệt</span>
                        </a>

                    {{-- Style xám cho reset --}}
                    <button onclick="location.reload()" class="btn border-0 text-secondary px-3 py-2"
                        style="background-color: #e2e3e5;">
                        <i class="bi bi-arrow-repeat me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>

        <div id="resultsSection" class="container-xl px-0">
            <div class="d-flex align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0 me-auto">Kết quả tra cứu <span id="searchResultSummary"
                        class="text-primary small ms-2"></span></h5>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="errorTableCard">
                <div class="table-responsive">
                    <x-technicaldocument.error-search-table />
                </div>
            </div>

            <div id="emptyState" class="text-center py-5 d-none">
                <div class="card border-0 bg-transparent">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="bi bi-inbox-fill text-muted display-1 opacity-25"></i>
                        </div>
                        <h5 class="text-secondary">Không tìm thấy dữ liệu</h5>
                        <p class="text-muted">Sản phẩm này chưa được cập nhật mã lỗi nào.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="errorDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 90vw;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 85vh;">
                <div class="modal-body p-0">
                    <div class="row g-0 h-100">
                        <div class="col-lg-7 d-flex flex-column h-100">
                            <div class="p-4 border-bottom bg-white sticky-top">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill border border-danger-subtle" id="detailErrorCode">E-00</span>
                                        @can('technical_document.manage')
                                            <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnEditError">
                                                <i class="bi bi-pencil-square me-1"></i>Sửa
                                            </a>
                                        @endcan
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <h3 class="fw-bold text-dark mb-2" id="detailErrorName">Tên lỗi sản phẩm</h3>
                                <p class="text-muted mb-0" id="detailDescription">Mô tả chi tiết về hiện tượng...</p>
                            </div>

                            <div class="p-4 flex-grow-1 bg-light custom-scrollbar" style="overflow-y: auto;">
                                <div class="card border-0 shadow-sm rounded-3 mb-4">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold text-primary text-uppercase small mb-3"><i
                                                class="bi bi-tools me-2"></i>Quy trình xử lý</h6>
                                        <div id="detailSolution" class="tech-steps">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <h6 class="fw-bold text-secondary text-uppercase small mb-3"><i
                                            class="bi bi-paperclip me-2"></i>Tài liệu đính kèm</h6>
                                    <div class="list-group list-group-flush rounded-3 border-0" id="detailDocuments">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="col-lg-5 bg-dark d-flex flex-column justify-content-center align-items-center position-relative">
                            <div id="mediaCarousel" class="carousel slide w-100 h-100" data-bs-ride="false">
                                <div class="carousel-inner h-100 d-flex align-items-center" id="detailMediaInner">
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#mediaCarousel"
                                    data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-3"
                                        aria-hidden="true"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#mediaCarousel"
                                    data-bs-slide="next">
                                    <span class="carousel-control-next-icon bg-dark rounded-circle p-3"
                                        aria-hidden="true"></span>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="imagePreviewImg" src="" alt="Preview" class="img-fluid" style="max-height: 85vh; width: auto;">
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom Scrollbar for cleaner look */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Tech Steps Styling */
        .tech-steps ol {
            padding-left: 1.2rem;
        }

        .tech-steps li {
            margin-bottom: 0.5rem;
            color: #333;
        }

        /* Floating Search Bar Tweaks */
        .form-select:focus {
            box-shadow: none;
            background-color: #f8f9fa;
        }

        /* Modal Image Fit */
        .carousel-item img {
            max-height: 80vh;
            object-fit: contain;
            width: 100%;
        }
    </style>

    <script>
        window.technicalDocumentIndexConfig = {
            routes: {
                getProductsByCategory: "{{ route('warranty.document.getProductsByCategory') }}",
                getOriginsByProduct: "{{ route('warranty.document.getOriginsByProduct') }}",
                getModelsByOrigin: "{{ route('warranty.document.getModelsByOrigin') }}",
                getErrorsByModel: "{{ route('warranty.document.getErrorsByModel') }}",
                getErrorDetail: "{{ route('warranty.document.getErrorDetail') }}",
                downloadAllDocuments: "{{ route('warranty.document.downloadAllDocuments') }}",
                editError: "{{ route('warranty.document.errors.edit', ':id') }}"
            }
        };
    </script>
    <script src="{{ asset('js/technicaldocument/index.js') }}"></script>
@endsection
