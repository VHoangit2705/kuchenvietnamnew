@extends('layout.layout')

@section('content')
@if(request('embed'))
<style>
    /* Ẩn layout chung khi nhúng vào modal */
    body { padding-top: 0 !important; background: #ffffff !important; }
    header, footer, nav.navbar, .navbar, .topbar, .main-header, .sidebar, .page-header { display: none !important; }
    .container, .container-fluid { max-width: 100% !important; }
</style>
@endif
<style>
    .preview-container {
        max-width: 100%;
        overflow-x: auto;
    }
    .preview-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        font-family: 'Times New Roman', serif;
    }
    .preview-table th,
    .preview-table td {
        border: 1px solid #ddd;
        padding: 6px;
        text-align: left;
        white-space: nowrap;
    }
    .preview-table th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }
    .preview-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .preview-header {
        text-align: center;
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 10px;
    }
    .preview-date {
        text-align: right;
        font-weight: bold;
        margin-bottom: 10px;
    }
    .sheet-tab {
        margin-bottom: 20px;
    }
    .sheet-tab button {
        margin-right: 5px;
        padding: 8px 16px;
        border: 1px solid #ddd;
        background-color: #f8f9fa;
        cursor: pointer;
    }
    .sheet-tab button.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .sheet-content {
        display: none;
    }
    .sheet-content.active {
        display: block;
    }
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
</style>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-file-excel me-2"></i>Xem trước báo cáo Excel
            </h4>
            <div>
                <a href="#" id="btnDownloadExcelPreview"
                   class="btn btn-light btn-sm"
                   onclick="downloadExcel()">
                    <i class="fas fa-download me-1"></i>Tải xuống Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Sheet Tabs -->
            <div class="sheet-tab">
                <button class="btn btn-sm active" onclick="showSheet(1)">BÁO CÁO THỐNG KÊ TRƯỜNG HỢP BẢO HÀNH</button>
                <button class="btn btn-sm" onclick="showSheet(2)">QUÁ TRÌNH LÀM VIỆC</button>
                <button class="btn btn-sm" onclick="showSheet(3)">TỔNG HỢP BẢO HÀNH</button>
            </div>
            
            <!-- Sheet 1: Báo cáo thống kê trường hợp bảo hành -->
            <div id="sheet1" class="sheet-content active">
                <div class="preview-header">BÁO CÁO THỐNG KÊ TRƯỜNG HỢP BẢO HÀNH</div>
                <div class="preview-date">Chi nhánh: {{ $branch }}     Từ ngày: {{ $fromDateFormatted }} đến ngày: {{ $toDateFormatted }}</div>
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã serial</th>
                                <th>Mã serial thân máy</th>
                                <th>Tên sản phẩm</th>
                                <th>Chi nhánh</th>
                                <th>Khách hàng</th>
                                <th>Số điện thoại</th>
                                <th>Kỹ thuật viên</th>
                                <th>Ngày tiếp nhận</th>
                                <th>Lỗi ban đầu</th>
                                <th>Ngày xuất kho</th>
                                <th>Tình trạng BH</th>
                                <th>Linh kiện</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th>Khách hàng chi trả</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheet1Data as $row)
                            <tr>
                                <td class="text-center">{{ $row['stt'] }}</td>
                                <td>{{ $row['serial_number'] }}</td>
                                <td>{{ $row['serial_thanmay'] }}</td>
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td>{{ $row['full_name'] }}</td>
                                <td>{{ $row['phone_number'] }}</td>
                                <td>{{ $row['staff_received'] }}</td>
                                <td>{{ $row['received_date'] }}</td>
                                <td>{{ $row['initial_fault_condition'] }}</td>
                                <td>{{ $row['shipment_date'] }}</td>
                                <td>{{ $row['BH'] }}</td>
                                <td>{{ $row['replacement'] }}</td>
                                <td class="text-right">{{ number_format($row['replacement_price'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ $row['quantity'] }}</td>
                                <td class="text-right">{{ number_format($row['total_price'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Table -->
                <div style="margin-top: 20px;">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>Phân loại</th>
                                <th>Số lượng ca bảo hành</th>
                                <th>Số lượng linh kiện đã thay</th>
                                <th>Chi phí linh kiện</th>
                                <th>Chi phí bảo hành</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheet1Summary as $summary)
                            <tr>
                                <td><strong>{{ $summary['label'] }}</strong></td>
                                <td class="text-center">{{ number_format($summary['count'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($summary['linh_kien'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($summary['chi_phi_linh_kien'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($summary['chi_phi_bao_hanh'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Sheet 2: Quá trình làm việc -->
            <div id="sheet2" class="sheet-content">
                <div class="preview-header">BÁO CÁO TỔNG HỢP QUÁ TRÌNH LÀM VIỆC CỦA NHÂN VIÊN</div>
                <div class="preview-date">Chi nhánh: {{ $branch }}     Từ ngày: {{ $fromDateFormatted }} đến ngày: {{ $toDateFormatted }}</div>
                <div style="margin-bottom: 10px; font-style: italic; font-size: 10px;">
                    Ghi chú: - "% so với CN" = Phần trăm số ca nhân viên tiếp nhận so với tổng số ca của chi nhánh<br>
                    - Các phần trăm khác (%) = Phần trăm số ca của từng trạng thái so với tổng số ca nhân viên đó tiếp nhận
                </div>
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Chi nhánh</th>
                                <th>Tên kỹ thuật viên</th>
                                <th>Tổng tiếp nhận</th>
                                <th>% so với CN</th>
                                <th>Đang sửa chữa</th>
                                <th>Đang sửa chữa %</th>
                                <th>Chờ KH phản hồi</th>
                                <th>Chờ KH phản hồi %</th>
                                <th>Đã hoàn tất</th>
                                <th>Đã hoàn tất %</th>
                                <th>Tỉ lệ trễ ca bảo hành (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheet2Data as $row)
                            <tr>
                                <td class="text-center">{{ $row['stt'] }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td>{{ $row['staff_received'] }}</td>
                                <td class="text-center">{{ number_format($row['tong_tiep_nhan'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($row['phan_tram_chi_nhanh'], 2) }}%</td>
                                <td class="text-center">{{ number_format($row['dang_sua_chua'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($row['dang_sua_chua_percent'], 2) }}%</td>
                                <td class="text-center">{{ number_format($row['cho_khach_hang_phan_hoi'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($row['cho_khach_hang_phan_hoi_percent'], 2) }}%</td>
                                <td class="text-center">{{ number_format($row['da_hoan_tat'], 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($row['da_hoan_tat_percent'], 2) }}%</td>
                                <td class="text-center">{{ number_format($row['ti_le_qua_han'], 2) }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(!empty($sheet2BranchTotals) && is_array($sheet2BranchTotals) && count($sheet2BranchTotals) > 0)
                <div style="margin-top: 20px;">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th colspan="{{ count($sheet2BranchTotals) }}">TỔNG CA BẢO HÀNH TIẾP NHẬN</th>
                            </tr>
                            <tr>
                                @foreach($sheet2BranchTotals as $branchName => $total)
                                <th>{{ $branchName }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @foreach($sheet2BranchTotals as $branchName => $total)
                                <td class="text-center">{{ number_format($total, 0, ',', '.') }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            
            <!-- Sheet 3: Tổng hợp bảo hành -->
            <div id="sheet3" class="sheet-content">
                <div class="preview-header">BÁO CÁO TỔNG HỢP BẢO HÀNH THEO SẢN PHẨM</div>
                <div class="preview-date">Chi nhánh: {{ $branch }}     Từ ngày: {{ $fromDateFormatted }} đến ngày: {{ $toDateFormatted }}</div>
                <div class="preview-container">
                    @if($productStats->isEmpty())
                        <p>Không có dữ liệu báo cáo trong khoảng thời gian này.</p>
                    @else
                        @foreach($productStats as $categoryData)
                            <div style="margin-bottom: 20px;">
                                <div style="background-color: #E0E0E0; padding: 10px; font-weight: bold; font-size: 12px;">
                                    {{ $categoryData['category_name'] }}
                                </div>
                                <table class="preview-table" style="margin-top: 0;">
                                    <thead>
                                        <tr>
                                            <th>TT</th>
                                            <th>Tên sản phẩm</th>
                                            <th>Tổng số TRƯỜNG HỢP LỖI</th>
                                            <th>Bảo hành</th>
                                            <th>Hết bảo hành</th>
                                            <th>Tổng số tiền thu khách</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categoryData['products'] as $product)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td>{{ $product['product_name'] }}</td>
                                            <td class="text-center">{{ number_format($product['tong_so_loi'], 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($product['bao_hanh'], 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($product['het_bao_hanh'], 0, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format($product['tong_tien'], 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background-color: #FFFFE0; font-weight: bold;">
                                            <td colspan="2" class="text-end">Tổng</td>
                                            <td class="text-center">{{ number_format($categoryData['total_loi'], 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($categoryData['total_bao_hanh'], 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($categoryData['total_het_bao_hanh'], 0, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format($categoryData['total_tien'], 0, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showSheet(sheetNumber) {
        // Ẩn tất cả sheet
        document.querySelectorAll('.sheet-content').forEach(sheet => {
            sheet.classList.remove('active');
        });
        
        // Ẩn tất cả button
        document.querySelectorAll('.sheet-tab button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Hiển thị sheet được chọn
        document.getElementById('sheet' + sheetNumber).classList.add('active');
        
        // Active button được chọn
        event.target.classList.add('active');
    }
    
    function downloadExcel() {
        const params = new URLSearchParams(window.location.search);
        params.delete('embed');
        window.location.href = '{{ route("xuatbaocao") }}?' + params.toString();
    }
</script>
@endsection

