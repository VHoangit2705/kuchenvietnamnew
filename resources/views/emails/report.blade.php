<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê bảo hành</title>
    <style>
        {!! file_get_contents(public_path('css/report_warranty.css')) !!}
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <h1>Báo cáo thống kê bảo hành
                @if (!empty($periodTitle))
                    {{ $periodTitle }}
                @else
                    {{ $reportType }}
                @endif
            </h1>
        </div>

        <div class="content">
            <p>Kính gửi các cấp quản lý và bộ phận liên quan tại các chi nhánh.</p>

            <p>Hệ thống đã tự động tạo báo cáo thống kê trường hợp bảo hành {{ $reportType }} từ ngày
                <strong>{{ $fromDate }}</strong> đến ngày <strong>{{ $toDate }}</strong>.</p>

            <div class="info">
                <p><strong>Thông tin báo cáo:</strong></p>
                <ul>
                    <li>Loại báo cáo:
                        @if (!empty($periodTitle))
                            Báo cáo {{ $periodTitle }}
                        @else
                            Báo cáo {{ $reportType }}
                        @endif
                    </li>
                    <li>Từ ngày: {{ $fromDate }}</li>
                    <li>Đến ngày: {{ $toDate }}</li>
                    @if (!empty($zoneLabel))
                        <li>Chi nhánh: {{ $zoneLabel }}</li>
                    @endif
                </ul>
            </div>

            <p>Báo cáo PDF đã được đính kèm trong email này. Vui lòng kiểm tra file đính kèm để xem chi tiết.</p>
            <p>Trân trọng thông báo.<br>


            <div class="footer">
                    {{ config('mail.from.name') }}</p>
                <p style="font-size: 11px; color: #999;">
                    Email này được gửi tự động từ hệ thống. Vui lòng không trả lời email này.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
