@extends('layout.layout')

@section('content')
<style>
    .preview-container {
        max-width: 100%;
        overflow-x: auto;
    }
    .preview-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        font-family: 'Times New Roman', serif;
    }
    .preview-table th,
    .preview-table td {
        border: 1px solid #ddd;
        padding: 8px;
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
    .account-number {
        font-family: monospace;
        white-space: pre;
    }
</style>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-file-excel me-2"></i>Xem trước báo cáo Excel
            </h4>
            <div>
                <a href="{{ route('collaborator.export', ['start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" 
                   class="btn btn-light btn-sm">
                    <i class="fas fa-download me-1"></i>Tải xuống Excel
                </a>
                <button type="button" class="btn btn-light btn-sm" onclick="window.close()">
                    <i class="fas fa-times me-1"></i>Đóng
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Sheet Tabs -->
            <div class="sheet-tab">
                <button class="btn btn-sm active" onclick="showSheet(1)">CTV CHI TIẾT</button>
                <button class="btn btn-sm" onclick="showSheet(2)">CTV TỔNG HỢP</button>
                <button class="btn btn-sm" onclick="showSheet(3)">ĐẠI LÝ CHI TIẾT</button>
                <button class="btn btn-sm" onclick="showSheet(4)">ĐẠI LÝ TỔNG HỢP</button>
            </div>

            <!-- Sheet 1: CTV CHI TIẾT -->
            <div id="sheet1" class="sheet-content active">
                <div class="preview-header">BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH</div>
                <div class="preview-date">Từ ngày: {{ $fromDateFormatted }} - đến ngày: {{ $toDateFormatted }}</div>
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>CỘNG TÁC VIÊN</th>
                                <th>SỐ ĐIỆN THOẠI</th>
                                <th>SẢN PHẨM</th>
                                <th class="text-right">CHI PHÍ</th>
                                <th>NGAY DONE</th>
                                <th>STK CTV</th>
                                <th>NGÂN HÀNG - CHI NHÁNH</th>
                                <th>MĐH</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sheet1Data as $row)
                            <tr>
                                <td>{{ $row['stt'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="account-number">{{ $row['phone'] }}</td>
                                <td>{{ $row['product'] }}</td>
                                <td class="text-right">{{ number_format($row['cost'], 0, ',', '.') }}</td>
                                <td>{{ $row['done_date'] }}</td>
                                <td class="account-number">{{ $row['account'] }}</td>
                                <td>{{ $row['bank'] }}</td>
                                <td>{{ $row['order_code'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">Không có dữ liệu</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sheet 2: CTV TỔNG HỢP -->
            <div id="sheet2" class="sheet-content">
                <div class="preview-header">BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH</div>
                <div class="preview-date">Từ ngày: {{ $fromDateFormatted }} - đến ngày: {{ $toDateFormatted }}</div>
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>HỌ VÀ TÊN</th>
                                <th>SỐ ĐIỆN THOẠI</th>
                                <th>ĐỊA CHỈ</th>
                                <th>SỐ TÀI KHOẢN CTV</th>
                                <th>NGÂN HÀNG - CHI NHÁNH</th>
                                <th>SỐ CA</th>
                                <th class="text-right">SỐ TIỀN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sheet2Data as $row)
                            <tr>
                                <td>{{ $row['stt'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="account-number">{{ $row['phone'] }}</td>
                                <td>{{ $row['address'] }}</td>
                                <td class="account-number">{{ $row['account'] }}</td>
                                <td>{{ $row['bank'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td class="text-right">{{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">Không có dữ liệu</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sheet 3: ĐẠI LÝ CHI TIẾT -->
            <div id="sheet3" class="sheet-content">
                <div class="preview-header">BẢNG CHI TIẾT TIỀN LẮP ĐẶT ĐẠI LÝ</div>
                <div class="preview-date">Từ ngày: {{ $fromDateFormatted }} - đến ngày: {{ $toDateFormatted }}</div>
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>TÊN ĐẠI LÝ</th>
                                <th>SỐ ĐIỆN THOẠI</th>
                                <th>NGÀY DONE</th>
                                <th>THIẾT BỊ</th>
                                <th class="text-right">CP HOÀN LẠI</th>
                                <th>STK ĐẠI LÝ</th>
                                <th>NGÂN HÀNG - CHI NHÁNH</th>
                                <th>MÃ ĐƠN HÀNG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sheet3Data as $row)
                            <tr>
                                <td>{{ $row['stt'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="account-number">{{ $row['phone'] }}</td>
                                <td>{{ $row['done_date'] }}</td>
                                <td>{{ $row['product'] }}</td>
                                <td class="text-right">{{ number_format($row['cost'], 0, ',', '.') }}</td>
                                <td class="account-number">{{ $row['account'] }}</td>
                                <td>{{ $row['bank'] }}</td>
                                <td>{{ $row['order_code'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">Không có dữ liệu</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sheet 4: ĐẠI LÝ TỔNG HỢP -->
            <div id="sheet4" class="sheet-content">
                <div class="preview-header">BẢNG TỔNG HỢP TRẢ TIỀN ĐẠI LÝ</div>
                <div class="preview-date">Từ ngày: {{ $fromDateFormatted }} - đến ngày: {{ $toDateFormatted }}</div>
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>HỌ VÀ TÊN</th>
                                <th>SĐT</th>
                                <th>SỐ TK CÁ NHÂN</th>
                                <th>NGÂN HÀNG - CHI NHÁNH</th>
                                <th>SỐ CA</th>
                                <th class="text-right">SỐ TIỀN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sheet4Data as $row)
                            <tr>
                                <td>{{ $row['stt'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="account-number">{{ $row['phone'] }}</td>
                                <td class="account-number">{{ $row['account'] }}</td>
                                <td>{{ $row['bank'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td class="text-right">{{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">Không có dữ liệu</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showSheet(sheetNumber) {
    // Hide all sheets
    for (let i = 1; i <= 4; i++) {
        document.getElementById('sheet' + i).classList.remove('active');
        document.querySelectorAll('.sheet-tab button')[i - 1].classList.remove('active');
    }
    
    // Show selected sheet
    document.getElementById('sheet' + sheetNumber).classList.add('active');
    document.querySelectorAll('.sheet-tab button')[sheetNumber - 1].classList.add('active');
}
</script>
@endsection

