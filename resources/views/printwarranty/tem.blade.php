<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A3 portrait;
            margin: 10mm;
        }

        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ public_path('fonts/DejaVuSans.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
        }

        .tem-table {
            width: 100%;
            border-collapse: collapse;
            padding: 0;
            margin: 0;
            border: 1px solid black;
        }

        .tem-cell {
            height: 2.4cm;
            width: 16.66%;
            box-sizing: border-box;
            /* vertical-align: center; */
        }

        .tem-inner {
            width: 100%;
            height: auto;
        }

        .logo-left img {
            width: 1.8cm;
            height: 1.8cm;
            display: block;
        }

        .logo-small img {
            width: 0.5cm;
            height: 0.5cm;
        }

        .tem-title {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: -6px;
            text-align: center;
        }

        .text {
            font-size: 8px;
        }

        .inner-table {
            border-collapse: collapse;
            border-spacing: 0;
            width: 100%;
        }
    </style>
</head>

<body>
    <table class="tem-table">
        <tr>
            @php
            $temList = [];
            foreach ($serials as $serial) {
                $temList[] = $serial;
                $temList[] = $serial;
            }
            @endphp

            @foreach ($temList as $index => $serial)
            @php
            $innerIndex = $index % 2;
            @endphp
            <td class="tem-cell">
                <div class="tem-inner">
                    <table class="inner-table">
                        <tr>
                            <td class="text" colspan="3" style="color: {{ $innerIndex == 0 ? 'red' : 'black' }};">
                                <div class="tem-title">TEM BẢO HÀNH (Hotline: {{ $hotline }})</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="logo-left" style="width: 1.8cm;" rowspan="3">
                                <img src="data:image/png;base64,{{ $serial->qrCodeBase64 }}" alt="QR">
                            </td>
                            <td class="text" rowspan="3" style="color: {{ $innerIndex == 0 ? 'red' : 'black' }};">
                                {{ $serial->product_name }}<br>
                                <strong>SN:</strong> {{ $serial->sn }}
                            </td>
                            <td class="logo-small" style="width: 0.5cm;" rowspan="3">
                                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                            </td>
                            <td style="width: 0.1cm;"></td>
                        </tr>
                    </table>
                </div>
            </td>
            @if (($index + 1) % 6 == 0)
        </tr>
        <tr>
            @endif
            @endforeach
        </tr>
    </table>
</body>

</html>