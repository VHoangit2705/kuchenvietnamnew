@extends('layout.layout')

@section('content')
    <div class="bg-light d-flex justify-content-center align-items-center" style="height: calc(100vh - 60px - 60px);">
        <div class="d-flex flex-wrap gap-4 justify-content-center align-items-center text-center">
            <a href="{{ route('warranty.kuchen') }}" class="warranty-card text-decoration-none text-dark">
                <img src="{{ asset('imgs/logokuchen.png') }}" alt="Bảo hành KUCHEN" style="width: 100px;">
                <h5 class="fw-bold mt-2">Bảo hành KUCHEN</h5>
            </a>
            <a href="{{ route('warranty.hurom') }}" class="warranty-card text-decoration-none text-dark">
                <img src="{{ asset('imgs/hurom.webp') }}" alt="Bảo hành HUROM" style="width: 210px;">
                <h5 class="fw-bold mt-2">Bảo hành HUROM</h5>
            </a>
        </div>
    </div>
    {{-- <button type="button" class="btn btn-primary" id="CapNhatHanBaoHanh"> Cập nhật</button> --}}

    <style>
        .warranty-card {
            width: 400px;
            height: 200px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            transition: transform 0.3s;
            cursor: pointer;
        }

        .warranty-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .warranty-card img {
            /* max-width: 80px;
                max-height: 80px; */
            margin-bottom: 10px;
        }

        /* Đảm bảo tự động xuống dòng và luôn căn giữa khi màn hình nhỏ */
        @media (max-width: 768px) {
            .warranty-card {
                width: 80%;
                max-width: 300px;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 480px) {
            .warranty-card {
                width: 90%;
                max-width: 250px;
                height: auto;
            }
        }
    </style>
    <script>
        // $('#CapNhatHanBaoHanh').on('click', function () {
        //     debugger;
        //     $.ajax({
        //         url: '{{ route("capnhatkichhoatbaohanh") }}',
        //         type: 'GET',
        //         dataType: 'json',
        //         beforeSend: function () {
        //             Swal.fire({
        //                 title: 'Đang xử lý...',
        //                 allowOutsideClick: false,
        //                 didOpen: () => {
        //                     Swal.showLoading();
        //                 }
        //             });
        //         },
        //         success: function (response) {
        //             Swal.close();
        //             if (response.success) {
        //                 Swal.fire({
        //                     icon: 'success',
        //                     title: 'Cập nhật thành công!',
        //                     text: response.message,
        //                     timer: 1500
        //                 });
        //             } else {
        //                 Swal.fire({
        //                     icon: 'warning',
        //                     title: 'Có lỗi xảy ra!',
        //                     text: response.message || 'Vui lòng thử lại sau.',
        //                 });
        //             }
        //         },
        //         error: function (xhr) {
        //             Swal.close();
        //             Swal.fire({
        //                 icon: 'error',
        //                 title: 'Lỗi!',
        //                 text: xhr.responseJSON?.message || 'Có lỗi xảy ra, vui lòng thử lại.',
        //             });
        //         }
        //     });
        // });
    </script>
@endsection