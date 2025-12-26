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
                <a href="#" id="btnDownloadExcelPreview"
                   class="btn btn-light btn-sm">
                    <i class="fas fa-download me-1"></i>Tải xuống Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Sheet Tabs -->
            <div class="sheet-tab">
                <button class="btn btn-sm active" onclick="showSheet(1)">CTV CHI TIẾT</button>
                <button class="btn btn-sm" onclick="showSheet(2)">CTV TỔNG HỢP</button>
                <button class="btn btn-sm" onclick="showSheet(3)">ĐẠI LÝ CHI TIẾT</button>
                <button class="btn btn-sm" onclick="showSheet(4)">ĐẠI LÝ TỔNG HỢP</button>
                <button class="btn btn-sm" onclick="showSheet(5)">DANH SÁCH ĐẠI LÝ</button>
            </div>

            <!-- Sheet 1: CTV CHI TIẾT -->
            <div id="sheet1" class="sheet-content active">
                @include('collaboratorinstall.partials.report_header', [
                    'title' => 'BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH',
                    'fromDate' => $fromDateFormatted,
                    'toDate' => $toDateFormatted,
                ])
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>CỘNG TÁC VIÊN</th>
                                <th>SỐ ĐIỆN THOẠI</th>
                                <th>SẢN PHẨM</th>
                                <th class="text-right">CHI PHÍ</th>
                                <th>NGÀY HOÀN THÀNH</th>
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
                                <td>{{ $row['model'] ?? $row['product'] }}</td>
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
                            @if(count($sheet1Data) > 0)
                            <tr style="font-weight: bold; background-color: #e9ecef;">
                                <td colspan="4" style="text-align: right;">TỔNG CỘNG</td>
                                <td class="text-right">{{ number_format($sheet1Total ?? 0, 0, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td colspan="10" style="font-style: italic; font-weight: bold; padding: 10px;">
                                    Bằng chữ: {{ $sheet5AmountInWords ?? 'không đồng' }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="8"></td>
                                <td colspan="2" style="text-align: right; padding: 10px;">
                                    Nghệ An, Ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                @include('collaboratorinstall.partials.report_footer', ['hasData' => count($sheet1Data) > 0])
            </div>

            <!-- Sheet 2: CTV TỔNG HỢP -->
            <div id="sheet2" class="sheet-content">
                @include('collaboratorinstall.partials.report_header', [
                    'title' => 'BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH',
                    'fromDate' => $fromDateFormatted,
                    'toDate' => $toDateFormatted,
                ])
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>HỌ VÀ TÊN</th>
                                <th>SỐ ĐIỆN THOẠI</th>
                                <th>ĐỊA CHỈ</th>
                                <th>CHỦ TÀI KHOẢN</th>
                                <th>STK CTV</th>
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
                                <td class="account-number">{{ $row['bank_account'] ?? 'n/a' }}</td>
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
                            @if(count($sheet2Data) > 0)
                            <tr style="font-weight: bold; background-color: #e9ecef;">
                                <td colspan="7" style="text-align: right;">TỔNG CỘNG</td>
                                <td class="text-right">{{ number_format($sheet2Total ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="8" style="font-style: italic; font-weight: bold; padding: 10px;">
                                    Bằng chữ: {{ $sheet2AmountInWords ?? 'không đồng' }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6"></td>
                                <td colspan="2" style="text-align: right; padding: 10px;">
                                    Nghệ An, Ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                @include('collaboratorinstall.partials.report_footer', ['hasData' => count($sheet2Data) > 0])
            </div>

            <!-- Sheet 3: ĐẠI LÝ CHI TIẾT -->
            <div id="sheet3" class="sheet-content">
                @include('collaboratorinstall.partials.report_header', [
                    'title' => 'BẢNG CHI TIẾT TIỀN LẮP ĐẶT ĐẠI LÝ',
                    'fromDate' => $fromDateFormatted,
                    'toDate' => $toDateFormatted,
                ])
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>TÊN ĐẠI LÝ</th>
                                <th>SĐT</th>
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
                                <td>{{ $row['model'] ?? $row['product'] }}</td>
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
                            @if(count($sheet3Data) > 0)
                            <tr style="font-weight: bold; background-color: #e9ecef;">
                                <td colspan="5" style="text-align: right;">TỔNG CỘNG</td>
                                <td class="text-right">{{ number_format($sheet3Total ?? 0, 0, ',', '.') }}</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr>
                                <td colspan="9" style="font-style: italic; font-weight: bold; padding: 10px;">
                                    Bằng chữ: {{ $sheet3AmountInWords ?? 'không đồng' }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7"></td>
                                <td colspan="2" style="text-align: right; padding: 10px;">
                                    Nghệ An, Ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                @include('collaboratorinstall.partials.report_footer', ['hasData' => count($sheet3Data) > 0])
            </div>

            <!-- Sheet 4: ĐẠI LÝ TỔNG HỢP -->
            <div id="sheet4" class="sheet-content">
                @include('collaboratorinstall.partials.report_header', [
                    'title' => 'BẢNG TỔNG HỢP TRẢ TIỀN ĐẠI LÝ',
                    'fromDate' => $fromDateFormatted,
                    'toDate' => $toDateFormatted,
                ])
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>HỌ VÀ TÊN</th>
                                <th>SĐT</th>
                                <th>CHỦ TÀI KHOẢN</th>
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
                                <td class="account-number">{{ $row['account_holder'] ?? $row['account'] ?? '' }}</td>
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
                            @if(count($sheet4Data) > 0)
                            <tr style="font-weight: bold; background-color: #e9ecef;">
                                <td colspan="6" style="text-align: right;">TỔNG CỘNG</td>
                                <td class="text-right">{{ number_format($sheet4Total ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="7" style="font-style: italic; font-weight: bold; padding: 10px;">
                                    Bằng chữ: {{ $sheet4AmountInWords ?? 'không đồng' }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5"></td>
                                <td colspan="2" style="text-align: right; padding: 10px;">
                                    Nghệ An, Ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                @include('collaboratorinstall.partials.report_footer', ['hasData' => count($sheet4Data) > 0])
            </div>

            <!-- Sheet 5: ĐẠI LÝ TỔNG HỢP -->
            <div id="sheet5" class="sheet-content">
                @include('collaboratorinstall.partials.report_header', [
                    'title' => 'BẢNG TỔNG HỢP DANH SÁCH ĐẠI LÝ',
                    'fromDate' => $fromDateFormatted,
                    'toDate' => $toDateFormatted,
                ])
                <div class="preview-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>HỌ VÀ TÊN</th>
                                <th>SỐ ĐIỆN THOẠI</th>
                                <th>CCCD</th>
                                <th>NGÀY CẤP</th>
                                <th>SỐ TK CÁ NHÂN</th>
                                <th>NGÂN HÀNG - CHI NHÁNH</th>
                                <th class="text-right">SỐ TIỀN</th>
                                <th>ĐỊA CHỈ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sheet5Data as $row)
                            <tr>
                                <td>{{ $row['stt'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="account-number">{{ $row['phone'] }}</td>
                                <td class="account-number">{{ $row['cccd'] }}</td>
                                <td>{{ $row['cccd_date'] }}</td>
                                <td class="account-number">{{ $row['account'] }}</td>
                                <td>{{ $row['bank'] }}</td>
                                <td class="text-right">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                <td>{{ $row['address'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">Không có dữ liệu</td>
                            </tr>
                            @endforelse
                            @if(count($sheet5Data) > 0)
                            <tr style="font-weight: bold; background-color: #e9ecef;">
                                <td colspan="8" style="text-align: right;">TỔNG CỘNG</td>
                                <td class="text-right">{{ number_format($sheet5Total ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="9" style="font-style: italic; font-weight: bold; padding: 10px;">
                                    Bằng chữ: {{ $sheet5AmountInWords ?? 'không đồng' }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7"></td>
                                <td colspan="2" style="text-align: right; padding: 10px;">
                                    Nghệ An, Ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                @include('collaboratorinstall.partials.report_footer', ['hasData' => count($sheet5Data) > 0])
            </div>
        </div>
    </div>
</div>

<script>
function showSheet(sheetNumber) {
    // Hide all sheets
    for (let i = 1; i <= 5; i++) {
        const sheet = document.getElementById('sheet' + i);
        if (sheet) {
            sheet.classList.remove('active');
        }
        const button = document.querySelectorAll('.sheet-tab button')[i - 1];
        if (button) {
            button.classList.remove('active');
        }
    }
    
    // Show selected sheet
    const selectedSheet = document.getElementById('sheet' + sheetNumber);
    if (selectedSheet) {
        selectedSheet.classList.add('active');
    }
    const selectedButton = document.querySelectorAll('.sheet-tab button')[sheetNumber - 1];
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

// Download with cooldown inside preview (works when embedded or standalone)
(function() {
    const COOLDOWN_KEY = 'export_excel_cooldown_until';
    const DOWNLOAD_SECONDS = 10;
    const $btn = document.getElementById('btnDownloadExcelPreview');
    if (!$btn) return;

    function setDisabledUI(remainingMs) {
        const s = Math.ceil(remainingMs / 1000);
        $btn.classList.add('disabled');
        $btn.setAttribute('aria-disabled', 'true');
        if (!$btn.dataset.originalText) $btn.dataset.originalText = $btn.innerHTML;
        $btn.innerHTML = '<i class="fas fa-hourglass-half me-1"></i>Chờ ' + s + 's';
    }
    function clearDisabledUI() {
        $btn.classList.remove('disabled');
        $btn.removeAttribute('aria-disabled');
        if ($btn.dataset.originalText) $btn.innerHTML = $btn.dataset.originalText;
    }
    function startCooldown(seconds) {
        const until = Date.now() + seconds * 1000;
        localStorage.setItem(COOLDOWN_KEY, String(until));
        let t = setInterval(function() {
            const remain = until - Date.now();
            if (remain <= 0) {
                clearInterval(t);
                localStorage.removeItem(COOLDOWN_KEY);
                clearDisabledUI();
                return;
            }
            setDisabledUI(remain);
        }, 1000);
        setDisabledUI(seconds * 1000);
    }
    function ensureCooldownUI() {
        const until = parseInt(localStorage.getItem(COOLDOWN_KEY) || '0', 10);
        if (until > Date.now()) {
            const timer = setInterval(function() {
                const remain = until - Date.now();
                if (remain <= 0) {
                    clearInterval(timer);
                    localStorage.removeItem(COOLDOWN_KEY);
                    clearDisabledUI();
                } else {
                    setDisabledUI(remain);
                }
            }, 1000);
            setDisabledUI(until - Date.now());
        }
    }

    ensureCooldownUI();

    $btn.addEventListener('click', function(e) {
        e.preventDefault();
        const until = parseInt(localStorage.getItem(COOLDOWN_KEY) || '0', 10);
        if (until > Date.now()) {
            if (window.Swal) {
                Swal.fire({ icon: 'info', text: 'Bạn vừa tải báo cáo. Vui lòng thử lại sau khi hết thời gian chờ.' });
            }
            return;
        }
        // Build URL and fetch to capture JSON throttle from server
        const params = new URLSearchParams({
            start_date: '{{ request('start_date') }}',
            end_date: '{{ request('end_date') }}'
        });
        if (window.Swal) {
            Swal.fire({
                title: 'Đang xuất file...',
                text: 'Vui lòng chờ trong giây lát',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }
        startCooldown(DOWNLOAD_SECONDS);
        fetch(`{{ route('collaborator.export') }}?${params.toString()}`)
            .then(response => {
                if (window.Swal) Swal.close();
                const ct = response.headers.get('Content-Type') || '';
                if (ct.includes('application/json')) {
                    return response.json().then(json => {
                        if (window.Swal) Swal.fire({ icon: 'error', text: json.message || 'Lỗi máy chủ' });
                    });
                }
                return response.blob().then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'THỐNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                });
            })
            .catch(() => {
                if (window.Swal) Swal.close();
                if (window.Swal) Swal.fire({ icon: 'error', text: 'Lỗi server.' });
            });
    });
})();
</script>
@endsection

