<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{ asset('css/printwarranty/tem.css') }}">

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