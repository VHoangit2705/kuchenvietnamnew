<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả duyệt nội dung sản phẩm</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            color: white;
        }
        .header.approved {
            background-color: #198754;
        }
        .header.rejected {
            background-color: #dc3545;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .info-box {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
            color: white;
        }
        .status-badge.approved {
            background-color: #198754;
        }
        .status-badge.rejected {
            background-color: #dc3545;
        }
        .reject-reason {
            background-color: #fff3f3;
            border: 1px solid #dc3545;
            padding: 15px;
            margin-top: 15px;
            border-radius: 4px;
            color: #b02a37;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header {{ $review->status === 'approved' ? 'approved' : 'rejected' }}">
        <h1>{{ $review->status === 'approved' ? '✅ Nội Dung Đã Được Duyệt' : '❌ Nội Dung Bị Từ Chối' }}</h1>
    </div>
    
    <div class="content">
        <p>Xin chào Phòng Đào tạo,</p>
        
        <p>Phòng Kỹ thuật đã hoàn tất việc kiểm duyệt nội dung sản phẩm do bạn cung cấp.</p>
        
        <div class="info-box">
            <h3>Thông tin sản phẩm:</h3>
            <p><strong>Tên sản phẩm:</strong> {{ $product->product_name }}</p>
            <p><strong>Model:</strong> {{ $product->model ?? 'N/A' }}</p>
            <p><strong>ID sản phẩm:</strong> #{{ $product->id }}</p>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            @if($review->status === 'approved')
                <span class="status-badge approved">ĐÃ DUYỆT THÀNH CÔNG</span>
                <p>Nội dung sản phẩm đã sẵn sàng và được chuyển sang bước tiếp theo.</p>
            @else
                <span class="status-badge rejected">KHÔNG ĐƯỢC CHẤP NHẬN</span>
                <div class="reject-reason">
                    <strong>Lý do từ chối:</strong><br>
                    {{ $review->reject_reason }}
                </div>
                <p style="margin-top: 15px;">Vui lòng kiểm tra lại nội dung và cập nhật theo yêu cầu để nạp lại.</p>
            @endif
        </div>

        <p>Bạn có thể đăng nhập vào hệ thống để xem chi tiết và thực hiện các bước tiếp theo.</p>
    </div>
    
    <div class="footer">
        <p>Email này được gửi tự động từ hệ thống Kuchen Vietnam.</p>
        <p>Vui lòng không trả lời email này.</p>
    </div>
</body>
</html>
