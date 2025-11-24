<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê bảo hành</title>
</head>

<body>
    <div class="header">
        <h1>Báo cáo thống kê bảo hành {{ $reportType }}</h1>
    </div>

    <div class="content">
        <p>Kính gửi Quý Trưởng phòng,</p>

        <p>Hệ thống đã tự động tạo báo cáo thống kê trường hợp bảo hành {{ $reportType }} từ ngày
            <strong>{{ $fromDate }}</strong> đến ngày <strong>{{ $toDate }}</strong>.</p>

        <div class="info">
            <p><strong>Thông tin báo cáo:</strong></p>
            <ul>
                <li>Loại báo cáo: Báo cáo {{ $reportType }}</li>
                <li>Từ ngày: {{ $fromDate }}</li>
                <li>Đến ngày: {{ $toDate }}</li>
            </ul>
        </div>

        <p>Báo cáo PDF đã được đính kèm trong email này. Vui lòng kiểm tra file đính kèm để xem chi tiết.</p>


        <div class="footer">
            <p>Trân trọng,<br>
                Hệ thống tự động<br>
                {{ config('mail.from.name') }}</p>
            <p style="font-size: 11px; color: #999;">
                Email này được gửi tự động từ hệ thống. Vui lòng không trả lời email này.
            </p>
        </div>
    </div>
</body>

</html>
