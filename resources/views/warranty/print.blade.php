<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Phiếu Tiếp Nhận Bảo Hành</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"
        integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
        integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF"
        crossorigin="anonymous"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <!-- JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @page {
            size: A4;
            margin-top: 5mm;
            margin-bottom: 0;
            margin-left: 5mm;
            margin-right: 5mm;
        }

        body {
            /* font-family: "Times New Roman", serif; */
            font-family: 'DejaVu Sans', serif;
            font-size: 12px;
            padding: 0;
        }

        .title {
            text-align: center;
            font-weight: bold;
        }

        .subtitle {
            text-align: center;
            font-style: italic;
            font-size: 10px;
        }

        .bordered {
            border: 1px solid black;
        }

        .chuky td {
            text-align: center;
            vertical-align: middle;
        }

        .table_product {
            margin-top: 15px;
            border-collapse: collapse;
        }

        .table_product th,
        .table_product td {

            vertical-align: middle;
            border: 1px solid black;
        }

        .signature {
            margin-top: 50px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <table width="100%" style="margin-bottom: 10px;">
            <tr>
                <td style="width: 15%; text-align: center;">
                    @if(session('brand') == 'hurom')
                        <img src="{{ public_path('imgs/hurom.webp') }}" alt="Logo Hurom" style="width: 70px; height: auto;">
                    @else
                        <img src="{{ public_path('imgs/logokuchen.png') }}" alt="Logo Kuchen"
                            style="width: 50px; height: auto;">
                    @endif
                </td>
                <td style="width: 85%; text-align: left;">
                    <strong style="text-transform: uppercase;">{{ $name }} VIỆT NAM - CƠ SỞ BẢO HÀNH THÀNH PHỐ
                        {{ $city }}</strong><br>
                    Đ/C: {{ $address }}<br>
                    Hotline: {{ $hotline }} - Website: {{ $website }}
                </td>
            </tr>
        </table>

        <div class="title">PHIẾU TIẾP NHẬN BẢO HÀNH</div>
        @php
            use Carbon\Carbon;
            $now = Carbon::now('Asia/Ho_Chi_Minh');
        @endphp
        <div class="subtitle">In lúc {{ $now->format('H:i') }} ngày {{ $now->day }} tháng {{ $now->month }} năm
            {{ $now->year }}
        </div>

        <table width="100%">
            <tr>
                <td width="50%"><strong>Mã số phiếu:</strong> {{ $data->id }} </td>
                <td><strong>Serial:</strong> {{ strtoupper($data->serial_number) }}</td>
            </tr>
            <tr>
                <td><strong>Họ và tên kh:</strong> {{ $data->full_name }}</td>
                <td><strong>Ngày xuất kho:</strong> {{ \Carbon\Carbon::parse($data->shipment_date)->format('d/m/Y')}}
                    ({{$strWar}})
                </td>
            </tr>
            <tr>
                <td><strong>Số điện thoại: </strong> {{ $data->phone_number }} </td>
                @if($ctv!=null)<td><strong>Cộng tác viên: </strong> {{ $ctv['tenctv'] }} </td>@endif
            </tr>
            <tr>
                @if ($ctv != null)
                <td rowspan="2" valign="top"><strong>Địa chỉ KH:</strong> {{ $data->address }}</td>
                    <td><strong>Số điện thoại CTV:</strong> {{ $ctv['sdt'] }}</td>
                @else
                    <td colspan="2"><strong>Địa chỉ KH:</strong> {{ $data->address }}</td>
                @endif
            </tr>
            @if ($ctv != null)
                <tr>
                    <td><strong>Địa chỉ CTV:</strong> {{ $ctv['diachi'] }}</td>
                </tr>
            @endif
            <tr>
                <td colspan="2"><strong>Sản phẩm:</strong> {{ $data->product }}</td>
            </tr>
        </table>

        <table width="100%" class="table_product" style="border-collapse: collapse; border: 1px solid black;">
            <thead>
                <tr>
                    <th style="width: 12px;">STT</th>
                    <th style="width: 250px;">Lỗi</th>
                    <th>Linh kiện thay thế</th>
                    <th>SL</th>
                    <th>Đơn giá</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>{{ $item->error_type }}</td>
                        <td>{{ $item->replacement }}</td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: center;">{{ number_format($item->unit_price, 0) }}</td>
                    </tr>
                @endforeach
                @for ($i = 1; $i <= 6; $i++)
                    <tr>
                        <td style="text-align: center;">{{ $items->count() + $i }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: center;"><strong>TỔNG TIỀN</strong></td>
                    <td style="text-align: center;"><strong>{{ number_format($total, 0) }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <div><strong>Ghi chú:</strong></div>

        <table class="chuky" width="100%">
            <tr>
                <td><strong>Khách hàng</strong><br><i>(Ký, ghi rõ họ tên)</i></td>
                <td><strong>Nhân viên kỹ thuật</strong><br><i>(Ký, ghi rõ họ tên)</i></td>
            </tr>
            <tr>
                <td></td>
                <td><strong>{{ $data->staff_received }}</strong></td>
            </tr>
        </table>
    </div>

</body>

</html>