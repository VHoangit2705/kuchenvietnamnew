<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if(session()->has('brand') && session('brand') == 'kuchen')
        <link rel="shortcut icon" href="{{ asset('imgs/logokuchen.png') }}" type="image/x-icon">
    @elseif(session()->has('brand') && session('brand') == 'hurom')
        <link rel="shortcut icon" href="{{ asset('imgs/logohurom.png') }}" type="image/x-icon">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bảo hành {{ strtoupper(session('brand')) }}</title>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 14px;
            display: flex;
            flex-direction: column;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        .full-height {
            min-height: 100vh;
        }

        .content {
            flex: 1 0 auto;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            pointer-events: all;
        }
    </style>
</head>

<body class="bg-gray text-gray-800">
    <header class="bg-dark py-2 position-relative" style="height: 60px; z-index: 1050;">
        <div class="container d-flex justify-content-between align-items-center" style="height: 100%;">
            <!-- Logo (Hidden on Small Screens) -->
            <div class="header-logo d-flex align-items-center d-none d-lg-flex">
                <a href="{{ route('home') }}">
                    @if (session()->has('brand') && session('brand') == 'kuchen')
                        <img src="{{ asset('imgs/logokuchen.png') }}" alt="Logo" style="height: 50px;">
                    @elseif(session()->has('brand') && session('brand') == 'hurom')
                        <img src="{{ asset('imgs/hurom.webp') }}" alt="Logo" style="height: 30px;">
                    @endif
                </a>
            </div>

            <!-- Toggle Menu for Small Screens -->
            <button class="navbar-toggler d-lg-none text-white border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false"
                aria-label="Toggle navigation" style="z-index: 1060;">
                @if (session()->has('brand'))
                <img src="{{ asset('icons/menu.png') }}" alt="Menu"
                    style="height: 25px; filter: invert(100%) sepia(100%) saturate(2) hue-rotate(180deg);">
                    @endif
            </button>

            <!-- Full Menu (Hidden on small screens) -->
            <nav class="nav d-none d-lg-flex">
                @if (session()->has('brand'))
                @if (Auth::user()->hasPermission('Danh sách ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('warranty.' . session('brand')) }}">Danh sách ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Tiếp nhận ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('warranty.check') }}">Tiếp nhận ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Thống kê ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('baocao') }}">Thống kê ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('ctv.getlist') }}">Quản lý CTV</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('dieuphoi.index') }}">Điều phối CTV</a>
                @endif
                @if (Auth::user()->hasPermission('In tem bảo hành'))
                <a class="nav-link text-white" href="{{ route('warrantycard') }}">In tem bảo hành</a>
                @endif
                @endif
            </nav>
            <!-- Account Section -->
            <div class="d-flex align-items-center text-white">
                <span class="me-3 d-none d-lg-inline">{{ Auth::user()->full_name ?? 'Khách' }}</span>
                <button class="btn btn-outline-danger"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Đăng
                    Xuất</button>

                <!-- Form đăng xuất ẩn -->
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>

        <!-- Collapsible Menu for Small Screens (Overlay with z-index) -->
        <div class="collapse position-absolute w-100 bg-dark d-lg-none" id="navbarMenu"
            style="top: 48px; z-index: 1050;">
            <nav class="nav flex-column text-white p-3">
                @if (session()->has('brand'))
                <a class="nav-link text-white" href="{{ route('home')}}">Thoát khỏi {{ strtoupper(session('brand')) }}</a>
                @if (Auth::user()->hasPermission('Danh sách ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('warranty.' . session('brand')) }}">Danh sách ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Tiếp nhận ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('warranty.check') }}">Tiếp nhận ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Thống kê ca bảo hành'))
                <a class="nav-link text-white" href="{{ route('baocao') }}">Thống kê ca bảo hành</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('ctv.getlist') }}">Quản lý CTV</a>
                @endif
                @if (Auth::user()->hasPermission('Quản lý CTV'))
                <a class="nav-link text-white" href="{{ route('dieuphoi.index') }}">Điều phối CTV</a>
                @endif
                @if (Auth::user()->hasPermission('In tem bảo hành'))
                <a class="nav-link text-white" href="{{ route('warrantycard') }}">In tem bảo hành</a>
                @endif
                @endif
            </nav>
        </div>
    </header>
    <div class="content mb-3 pb-3">
        @yield('content')
    </div>
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <!-- <footer class="bg-dark py-2 position-relative" style="height: 60px; flex-shrink: 0;" >
    </footer> -->
    <script>
        function goBackOrReload() {
            if (document.referrer) {
                window.location.href = 'https://kuchenvietnam.vn/kuchen/trungtambaohanhs/baohanh/{{ session('brand') }}';
            }
        }

        function OpenWaitBox(){$('#loadingOverlay').removeClass('d-none');}
        function CloseWaitBox(){$('#loadingOverlay').addClass('d-none');}

        function Notification(icon, title, timeout, confirm) {
            Swal.fire({
                icon: icon,
                title: title,
                timer: timeout,
                showConfirmButton: confirm
            });
        }
        $(document).ready(function() {
            ThongBao();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            // Bắt lỗi AJAX toàn cục
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thông báo',
                        text: xhr.responseJSON?.message || 'Phiên đăng nhập đã hết hạn.',
                    }).then(() => {
                        window.location.href = "{{ route('login') }}"; 
                    });
                }
            });
        })

        function ThongBao() {
            const userBrand = @json(session('brand'));
            if (!userBrand) {
                return;
            }
            const timelimit = 4 * 60 * 60 * 1000;
            let lastTime = localStorage.getItem('lastThongBaoTime');
            let now = Date.now();

            if (lastTime && (now - lastTime) < timelimit) {
                return;
            }

            $.ajax({
                url: "{{ route('warranty.thongbao') }}",
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: "warning",
                            title: 'Cảnh Báo!',
                            text: response.message,
                            showCancelButton: true,
                            confirmButtonText: 'Xem ngay',
                            cancelButtonText: 'Xác nhận',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const tab = 'quahan';
                                const brand = "{{ session('brand') }}";
                                let baseUrl = "{{ route('warranty.kuchen') }}";
                                if (brand === 'hurom') {
                                    baseUrl = "{{ route('warranty.hurom') }}";
                                }
                                window.location.href = baseUrl + "?tab=" + tab + "&kythuatvien=" + encodeURIComponent( response.nhanvien);
                            }
                        });
                        localStorage.setItem('lastThongBaoTime', now);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Đã xảy ra lỗi:", error);
                }
            });
        }

        function updateQueryStringParameter(uri, key, value) {
            let re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            let separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + encodeURIComponent(value) + '$2');
            } else {
                return uri + separator + key + "=" + encodeURIComponent(value);
            }
        }

        // Gửi request mỗi 5 phút để giữ session sống
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                fetch('/keep-alive', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }
        }, 5 * 60 * 1000);
    </script>
    <script src="{{ asset('public/js/validateform.js') }}"></script>
</body>

</html>