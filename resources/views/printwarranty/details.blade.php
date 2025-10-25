@extends('layout.layout')

@section('content')
<div class="container-fluid mt-2 mb-2">
    <div class="row g-4">
        <div class="col-12 col-md-12">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white position-relative">
                    <img src="{{ asset('icons/arrow.png') }}" alt="Quay lại" onclick="window.history.back()" title="Quay lại"
                        style="height: 15px; filter: brightness(0) invert(1); position: absolute; left: 15px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                    <h5 class="mb-0 ms-5">Thông tin phiếu nhập</h5>
                </div>
                <div class="card-body">
                    <p><strong>Tên sản phẩm:</strong> {{ $item->product }}</p>
                    <div class="d-flex justify-content-between">
                        <p><strong>Số lượng:</strong> {{ $item->quantity }}</p>
                        <a href="{{ route('warrantycard.temdowload', $item->id) }}" class="btn btn-primary" id="downloadBtn">Dowload file</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid" id="previewContainer"></div>
<script>
    $(document).ready(function() {
        const temUrl = @json(route('warrantycard.tem', ['id' => $item->id]));
        const itemId = @json($item->id);
        const $previewContainer = $('#previewContainer');
        OpenWaitBox();
        $.get(temUrl)
            .done(function(data) {
                const iframe = `<iframe src="${data.url}" style="width: 100%; height: 1000px;" frameborder="0"></iframe>`;
                $previewContainer.html(iframe);
                CloseWaitBox();
            })
            .fail(function(xhr, status, error) {
                CloseWaitBox();
                $previewContainer.html(`<div style="color:red;">Không thể tải PDF: ${error}</div>`);
            });
    });

    $('#downloadBtn').on('click', function(e) {
        e.preventDefault();
        const url = @json(route('warrantycard.temdowload', ['id' => $item->id]));
        // Hiện hộp thoại đang tải
        Swal.fire({
            title: 'Đang tải file...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Lỗi tải file');
                // 🔽 Lấy tên file từ header Content-Disposition
                const disposition = response.headers.get('Content-Disposition');
                let filename = "tem-bao-hanh.pdf"; // tên mặc định
                if (disposition && disposition.indexOf('filename=') !== -1) {
                    const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (match && match[1]) {
                        filename = decodeURIComponent(match[1].replace(/['"]/g, ''));
                    }
                }

                return response.blob().then(blob => ({
                    blob,
                    filename
                }));
            })
            .then(({ blob, filename }) => {
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(downloadUrl);
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Tải file thành công!',
                    showConfirmButton: false,
                    timer: 1500
                });
            })
            .catch(error => {
                console.error('Lỗi khi tải file:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi khi tải file!',
                    text: error.message
                });
            });
    });
</script>
@endsection