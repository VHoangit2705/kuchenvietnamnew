@extends('layout.layout')

@section('content')
    <div class="container mt-4">
        <form id="searchForm">
            <!-- @csrf -->
            <div class="row">
                <div class="col-md-4 mb-1">
                    @if (session('position') != 'Kỹ thuật viên')
                        <select class="form-select" name="chinhanh">
                            <option value="">Tất cả chi nhánh</option>
                            @if (session('brand') == 'kuchen')
                                <option value="KUCHEN VINH" {{ request('chinhanh') == 'KUCHEN VINH' ? 'selected' : '' }}>KUCHEN VINH
                                </option>
                                <option value="KUCHEN HÀ NỘI" {{ request('chinhanh') == 'KUCHEN HÀ NỘI' ? 'selected' : '' }}>KUCHEN HÀ
                                    NỘI</option>
                                <option value="KUCHEN HCM" {{ request('chinhanh') == 'Kuchen HCM' ? 'selected' : '' }}>KUCHEN HCM
                                </option>
                            @else
                                <option value="HUROM VINH" {{ request('chinhanh') == 'HUROM VINH' ? 'selected' : '' }}>HUROM VINH
                                </option>
                                <option value="HUROM HÀ NỘI" {{ request('chinhanh') == 'HUROM HÀ NỘI' ? 'selected' : '' }}>HUROM HÀ
                                    NỘI</option>
                                <option value="HUROM HCM" {{ request('chinhanh') == 'HUROM HCM' ? 'selected' : '' }}>HUROM HCM
                                </option>
                            @endif
                        </select>
                    @else
                        <select class="form-select" name="branch_display" disabled>
                            <option selected>{{ $userBranch }}</option>
                        </select>
                        <input type="hidden" name="chinhanh" value="{{ $userBranch }}">
                    @endif
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="sophieu" name="sophieu" class="form-control" placeholder="Nhập số phiếu"
                        value="{{ request('sophieu') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="seri" name="seri" class="form-control" placeholder="Nhập số seri"
                        value="{{ request('seri') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="sdt" name="sdt" class="form-control" placeholder="Nhập số điện thoại"
                        value="{{ request('sdt') }}">
                </div>
                <div class="col-md-4 mb-1 position-relative">
                    <input type="text" id="product_name" name="product_name" class="form-control"
                        placeholder="Nhập tên sản phẩm" value="">
                    <div id="product-suggestions" class="list-group position-absolute w-100 d-none"
                        style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="khachhang" name="khachhang" class="form-control"
                        placeholder="Nhập tên khách hàng" value="{{ request('khachhang') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <input type="text" id="kythuatvien" name="kythuatvien" class="form-control"
                        placeholder="Nhập tên kỹ thuật viên" value="{{ request('kythuatvien') }}">
                </div>
                <div class="col-md-4 mb-1">
                    <div class="d-flex align-items-center">
                        <input type="date" id="fromDate" name="fromDate" class="form-control"
                            value="{{ $fromDate->toDateString() }}">
                        <label for="toDate" class="mb-0 me-2 ms-1">đến</label>
                        <input type="date" id="toDate" name="toDate" class="form-control"
                            value="{{ $toDate->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-4 mb-1">
                    <div class="d-flex gap-2 h-100">
                            <button id="btnSearch" class="btn btn-primary flex-fill" style="height: 38px;">Tìm kiếm</button>
                            <button id="btnReset" class="btn btn-secondary flex-fill" onclick="resetFilters()" style="height: 38px;">
                                Xóa bộ lọc
                            </button>
                            @if (in_array(strtolower(session('position') ?? ''), ['admin', 'quản trị viên']))
                                <button type="button" 
                                        class="btn btn-warning flex-fill" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#anomalyAlertsModal" 
                                        onclick="loadAnomalyAlerts()"
                                        style="height: 38px;">
                                    <i class="bi bi-exclamation-triangle"></i> Cảnh báo
                                </button>
                            @endif
                        </div>
                </div>
            </div>
        </form>
    </div>
    <div class="container-fluid mt-3">
        <div class="d-flex" style="overflow-x: auto; white-space: nowrap;">
            @include('components.tabheader', ['counts' => $counts, 'activeTab' => $tab ?? ''])
        </div>
        <!-- Nội dung tab -->
        <div id="tabContent">
            @include('components.tabcontent')
        </div>
        @include('components.status_modal')
    </div>

    <!-- Modal Cảnh báo bất thường -->
    @if (in_array(strtolower(session('position') ?? ''), ['admin', 'quản trị viên']))
    <div class="modal fade" id="anomalyAlertsModal" tabindex="-1" aria-labelledby="anomalyAlertsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="anomalyAlertsModalLabel">Cảnh báo nhân viên tiếp nhận bất thường</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="modalFilterDate" class="form-label">Lọc theo ngày:</label>
                            <input type="date" class="form-control" id="modalFilterDate" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="modalFilterBranch" class="form-label">Lọc theo chi nhánh:</label>
                            <select class="form-select" id="modalFilterBranch">
                                <option value="">Tất cả</option>
                                @if (session('brand') == 'kuchen')
                                    <option value="KUCHEN VINH">KUCHEN VINH</option>
                                    <option value="KUCHEN HÀ NỘI">KUCHEN HÀ NỘI</option>
                                    <option value="KUCHEN HCM">KUCHEN HCM</option>
                                @else
                                    <option value="HUROM VINH">HUROM VINH</option>
                                    <option value="HUROM HÀ NỘI">HUROM HÀ NỘI</option>
                                    <option value="HUROM HCM">HUROM HCM</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="modalFilterResolved" class="form-label">Trạng thái:</label>
                            <select class="form-select" id="modalFilterResolved">
                                <option value="">Tất cả</option>
                                <option value="0">Chưa xử lý</option>
                                <option value="1">Đã xử lý</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="loadAnomalyAlerts()">Tải lại</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Ngày</th>
                                    <th>Chi nhánh</th>
                                    <th>Nhân viên</th>
                                    <th>Số ca nhận</th>
                                    <th>Tổng ca kho</th>
                                    <th>Số NV trong kho</th>
                                    <th>Trung bình</th>
                                    <th>Ngưỡng</th>
                                    <th>Mức độ</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="modalAlertsTableBody">
                                <tr>
                                    <td colspan="11" class="text-center">Đang tải...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        window.HOME_WARRANTY_CONFIG = {
            brand: '{{ session('brand') }}',
            routes: {
                listKuchen: '{{ route("warranty.kuchen") }}',
                listHurom: '{{ route("warranty.hurom") }}',
                anomalyAlerts: '{{ route("warranty.anomaly.alerts") }}',
                anomalyResolve: '{{ route("warranty.anomaly.resolve", ":id") }}',
                anomalyUnblock: '{{ route("warranty.anomaly.unblock", ":id") }}',
                anomalyDelete: '{{ route("warranty.anomaly.delete", ":id") }}',
            },
            defaultFromDate: '{{ $fromDate->toDateString() }}',
            defaultToDate: '{{ $toDate->toDateString() }}',
            products: {!! json_encode($products ?? []) !!},
            csrfToken: '{{ csrf_token() }}',
            anomalyEnabled: {{ in_array(strtolower(session('position') ?? ''), ['admin', 'quản trị viên']) ? 'true' : 'false' }}
        };
    </script>
    <script src="{{ asset('public/js/warranty/homewarranty.js') }}"></script>
@endsection
