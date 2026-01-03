<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title }}</title>
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
            background-color: #4CAF50;
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
            border-left: 4px solid #4CAF50;
            border-radius: 4px;
        }
        .status-change {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 15px 0;
            padding: 10px;
            background-color: #fff;
            border-radius: 4px;
        }
        .status-old {
            color: #999;
            text-decoration: line-through;
        }
        .status-new {
            color: #4CAF50;
            font-weight: bold;
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
        <h1>{{ $notification->title }}</h1>
    </div>
    
    <div class="content">
        <p>Xin chào <strong>{{ $agency->name ?? 'Đại lý' }}</strong>,</p>
        
        <p>{{ $notification->message }}</p>
        
        @if($requestAgency)
        <div class="info-box">
            <h3>Thông tin đơn hàng:</h3>
            <p><strong>Mã đơn hàng:</strong> {{ $requestAgency->order_code }}</p>
            <p><strong>Sản phẩm:</strong> {{ $requestAgency->product_name }}</p>
            <p><strong>Khách hàng:</strong> {{ $requestAgency->customer_name }}</p>
            <p><strong>Số điện thoại:</strong> {{ $requestAgency->customer_phone }}</p>
            <p><strong>Địa chỉ lắp đặt:</strong> {{ $requestAgency->installation_address }}</p>
        </div>
        @endif
        
        @if($notification->status_old && $notification->status_new)
        <div class="status-change">
            <span class="status-old">Trạng thái cũ: {{ $notification->status_old }}</span>
            <span>→</span>
            <span class="status-new">Trạng thái mới: {{ $notification->status_new }}</span>
        </div>
        @endif
        
        <p>Vui lòng kiểm tra hệ thống để xem chi tiết đơn hàng.</p>
    </div>
    
    <div class="footer">
        <p>Email này được gửi tự động từ hệ thống Kuchen Vietnam.</p>
        <p>Vui lòng không trả lời email này.</p>
    </div>
</body>
</html>

