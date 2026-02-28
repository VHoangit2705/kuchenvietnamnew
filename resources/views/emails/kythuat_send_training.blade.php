<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo gửi tài liệu từ Phòng Kỹ thuật</title>
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
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
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
            background-color: #198754;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
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
    <div class="header">
        <h1>📋 Thông Báo Gửi Tài Liệu Kỹ Thuật</h1>
    </div>
    
    <div class="content">
        <p>Xin chào,</p>
        
        <p>Phòng Kỹ thuật đã hoàn tất việc <strong>nạp số seri và tài liệu kỹ thuật</strong> cho sản phẩm và gửi đến <strong>Phòng Đào tạo</strong> để tiếp tục quy trình.</p>
        
        <div class="info-box">
            <h3>Thông tin sản phẩm:</h3>
            <p><strong>Tên sản phẩm:</strong> {{ $product->product_name }}</p>
            <p><strong>Model:</strong> {{ $product->model ?? 'N/A' }}</p>
            @if($product->category)
                <p><strong>Danh mục:</strong> {{ $product->category->name ?? 'N/A' }}</p>
            @endif
            <p><strong>ID sản phẩm:</strong> #{{ $product->id }}</p>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            <span class="status-badge">✅ Đã chuyển sang Phòng Đào tạo</span>
        </div>

        <p><strong>Bước tiếp theo:</strong> Phòng Đào tạo vui lòng kiểm tra và nạp thông tin hướng dẫn sử dụng (HDSD) cho sản phẩm trên hệ thống.</p>
        
        <p>Vui lòng đăng nhập vào hệ thống để xem chi tiết.</p>
    </div>
    
    <div class="footer">
        <p>Email này được gửi tự động từ hệ thống Kuchen Vietnam.</p>
        <p>Vui lòng không trả lời email này.</p>
    </div>
</body>
</html>
