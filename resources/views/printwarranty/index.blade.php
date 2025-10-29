 @extends('layout.layout')

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4 mb-1">
                <input type="number" id="sophieu" name="sophieu" class="form-control" 
                placeholder="Nhập số phiếu" value="{{ request('sophieu') }}">
            </div>
            <div class="col-md-4 mb-1">
                <input type="text" id="tensp" name="tensp" class="form-control" 
                placeholder="Nhập tên sản phẩm" value="{{ request('productSearch') }}">
            </div>
            <div class="col-md-4 mb-1">
                <div class="d-flex align-items-center flex-grow-1">
                    <input type="date" id="tungay" name="tungay" class="form-control"
                        value="{{ $startOfMonth}}">
                    <label for="toDate" class="mb-0 me-2 ms-1">đến</label>
                    <input type="date" id="denngay" name="denngay" class="form-control"
                        value="{{ $toDay}}">
                </div>
            </div>
            <div class="col-md-4 mb-1">
                <button id="searchCard" class="btn btn-primary w-100">Tìm kiếm</button>
            </div>
        </div>
    </div>
    
    <div class="container-fluid d-flex flex-column justify-content-start">
        <div class="mb-1 d-flex justify-content-between">
            <a href="#" class="btn btn-primary" id="openform">Thêm mới</a>
            <a href="#" class="btn btn-success" id="exportActiveWarranty">Thống kê số lượng kích hoạt</a>
        </div>
        <div class="table-container" style="overflow-x: auto;">
            <div id="tableContent">
                @include('printwarranty.tablebody', ['lstWarrantyCard' => $lstWarrantyCard])
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-center mt-3">
        @if ($lstWarrantyCard->total() > 50)
        <nav aria-label="Page navigation">
            <ul class="pagination">
                {{-- Nút Trang đầu --}}
                @if ($lstWarrantyCard->currentPage() > 1)
                <li class="page-item">
                    <a class="page-link" href="{{ $lstWarrantyCard->url(1) }}">Trang đầu</a>
                </li>
                @endif
    
                {{-- Nút Trang trước --}}
                @if ($lstWarrantyCard->onFirstPage())
                <li class="page-item disabled"><span class="page-link">«</span></li>
                @else
                <li class="page-item"><a class="page-link" href="{{ $lstWarrantyCard->previousPageUrl() }}">«</a></li>
                @endif
    
                {{-- Số trang --}}
                @for ($i = max(1, $lstWarrantyCard->currentPage() - 1); $i <= min($lstWarrantyCard->currentPage() + 1, $lstWarrantyCard->lastPage()); $i++)
                    <li class="page-item {{ $i == $lstWarrantyCard->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $lstWarrantyCard->url($i) }}">{{ $i }}</a>
                    </li>
                @endfor
    
                {{-- Nút Trang tiếp --}}
                @if ($lstWarrantyCard->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $lstWarrantyCard->nextPageUrl() }}">»</a></li>
                @else
                <li class="page-item disabled"><span class="page-link">»</span></li>
                @endif
                {{-- Nút Trang cuối --}}
                @if ($lstWarrantyCard->currentPage() < $lstWarrantyCard->lastPage())
                    <li class="page-item">
                        <a class="page-link" href="{{ $lstWarrantyCard->url($lstWarrantyCard->lastPage()) }}">Trang cuối</a>
                    </li>
                @endif
            </ul>
        </nav>
        @endif
    </div>
    
    <!-- Modal thêm phiếu -->
    <div class="modal fade" id="warrantyModal" tabindex="-1" aria-labelledby="warrantyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warrantyModalLabel">Thêm Phiếu Bảo Hành</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 position-relative">
                        <input type="text" class="form-control" hidden id="product_id" name="product_id" value="">
                        <label for="product" class="form-label">Tên sản phẩm</label>
                        <input type="text" class="form-control" id="product" name="product">
                        <div id="product_suggestions" class="list-group position-absolute w-100 d-none"></div>
                        <div class="error product_error text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3" id="radTypeSerial">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="serial_option" id="auto_serial" value="0" checked>
                            <label class="form-check-label" for="auto_serial">In Tem Bảo Hành</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="serial_option" id="import_serial" value="1">
                            <label class="form-check-label" for="import_serial">Nhập Mã Serial Nhà Máy</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="serial_option" id="import_excel" value="2">
                            <label class="form-check-label" for="import_excel">Import Excel</label>
                        </div>
                    </div>
                    <div class="mb-3" id="quantityInput">
                        <label for="quantity" class="form-label">Số lượng</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1">
                        <div class="error quantity_error text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3" id="serialRangeInput">
                        <label for="serial_range" class="form-label">Dải serial</label>
                        <textarea class="form-control text-uppercase" id="serial_range" name="serial_range" rows="6" placeholder="ví dụ: ES202505200001-ES202505200005, ES202505200010"></textarea>
                        <div class="error serial_range_error text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3" id="fileExcel">
                        <label class="form-label"><strong>File mẫu: <a href="{{ asset('storage/app/public/filemau.xlsx') }}" class="text-success" download>filemau.xlsx</a></strong></label>
                        <input type="file" class="form-control" id="serial_file" name="serial_file" accept=".xls,.csv,.xlsx" />
                        <div class="error serial_file_error text-danger small mt-1"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success submit-btn" data-action="add">Lưu & Thêm</button>
                    <button type="button" class="btn btn-success submit-btn" data-action="close">Lưu & Đóng</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const productList = {!! json_encode($products) !!};
        $(document).ready(function() {
            OpenFormCreate();
            ProductInput();
            SubmitForm();
            Search();
            ShowHideComponents();
            checkFile();
            // xóa khoảng trắng
            const $serialRange = $('#serial_range');
            $serialRange.on('keydown', function (e) {
                if (e.key === ' ' || e.keyCode === 32) {
                    e.preventDefault();
                }
            });
            $serialRange.on('paste', function (e) {
                e.preventDefault();
                const text = (e.originalEvent || e).clipboardData.getData('text');
                const cleanText = text.replace(/\s+/g, '');
                document.execCommand('insertText', false, cleanText);
            });
        });
        
        function checkFile() {
            $("#serial_file").on("change", function() {
                let file = this.files[0];
                let $errorDiv = $(".serial_file_error");
    
                if (!file) {
                    $errorDiv.text("");
                    return;
                }
    
                let allowedExtensions = [".xls", ".xlsx"];
                let fileName = file.name.toLowerCase();
                let isValid = allowedExtensions.some(ext => fileName.endsWith(ext));
    
                if (!isValid) {
                    $errorDiv.text("File không hợp lệ! Vui lòng chọn file .xls, .xlsx");
                    $(this).val("");
                } else {
                    $errorDiv.text("");
                }
            });
        }
        
        function ShowHideComponents(){
            // const brand = "{{ session('brand') }}";
            // if (brand === 'hurom'){
            //     $('#radTypeSerial').addClass('d-none');
            //     $('#quantityInput').addClass('d-none');
            // }
            // else{
            //     function toggleSerialFields() {
            //         if ($('#auto_serial').is(':checked')) {
            //             $('#quantityInput').show();
            //             $('#serialRangeInput').hide();
            //         } else {
            //             $('#quantityInput').hide();
            //             $('#serialRangeInput').show();
            //         }
            //     }
            //     toggleSerialFields();
            //     $('input[name="serial_option"]').on('change', toggleSerialFields);
            // }
            function toggleSerialFields() {
                if ($('#auto_serial').is(':checked')) {
                    $('#quantityInput').show();
                    $('#serialRangeInput').hide();
                    $('#fileExcel').hide();
                } else if ($('#import_serial').is(':checked')) {
                    $('#quantityInput').hide();
                    $('#serialRangeInput').show();
                    $('#fileExcel').hide();
                } else {
                    $('#fileExcel').show();
                    $('#quantityInput').hide();
                    $('#serialRangeInput').hide();
                }
            }
            toggleSerialFields();
            $('input[name="serial_option"]').on('change', toggleSerialFields);
        }
        
        function SubmitForm() {
            $('.submit-btn').on('click', function(e) {
                e.preventDefault();
                $('.error').text('');
    
                if (!validateForm()) return;
                OpenWaitBox();
                let actionType = $(this).data('action'); // 'add' hoặc 'close'
                let product = $('#product').val();
                let product_id = $('#product_id').val();
                let quantity = $('#quantity').val();
                let serial_range = ($('#serial_range').val() ?? '').toUpperCase().replace(/\n/g, ',').trim();
                let serial_option = $('input[name="serial_option"]:checked').val();
                let serial_file = $('#serial_file')[0].files[0];
                let formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('product', product);
                formData.append('product_id', product_id);
                formData.append('quantity', quantity);
                formData.append('serial_range', serial_range);
                formData.append('serial_file', serial_file);
                formData.append('serial_option', serial_option);
                
                $.ajax({
                    url: '{{ route("warrantycard.create") }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        CloseWaitBox();
                        if(response.success){
                            Swal.fire({
                                icon: 'success',
                                title: 'Thêm thành công',
                                timer: '3000',
                                showConfirmButton: true,
                                confirmButtonText: 'OK'
                            }).then(()=>{
                                $.get("{{ route('warrantycard.partial') }}", function(html) {
                                    $('#tableContent').html(html);
                                });
                            
                                if (actionType === 'add') {
                                    $('#product').val('').focus();
                                    $('#quantity').val('');
                                    $('#serial_range').val('');
                                } else if (actionType === 'close') {
                                    $('#warrantyModal').modal('hide');
                                }
                            });
                        }
                        else{
                            Swal.fire({
                                icon: 'warning',
                                title: 'Lỗi trùng số seri',
                                text: response.message,
                                timer: '3000',
                                showConfirmButton: true,
                                confirmButtonText: 'Đã hiểu'
                            });
                        }
                    },
                    error: function(xhr) {
                        CloseWaitBox();
                        alert('Lỗi khi lưu. Vui lòng kiểm tra lại.');
                        console.log(xhr.responseText);
                    }
                });
            });
        }
        
        function validateForm() {
            const brand = "{{ session('brand') }}";
            let productInput = $('#product').val().trim().toLowerCase();
            let productId = $('#product_id').val();
            let quantityInput = parseInt($('#quantity').val());
    
            $('.error').text('');

            if (!productInput) {
                $('.product_error').text('Sản phẩm không được để trống.')
                $('#product').focus();
                return false;
            }

            if (!productId) {
                $('.product_error').text('Vui lòng chọn sản phẩm từ danh sách gợi ý.')
                $('#product').focus();
                return false;
            }

            let isValidProduct = productList.some(p =>
                p.product_name.trim().replace(/\r?\n|\r/g, '').toLowerCase() === productInput.replace(/\r?\n|\r/g, '') &&
                p.id == productId
            );
    
            if (!isValidProduct) {
                $('.product_error').text('Sản phẩm không hợp lệ. Vui lòng chọn lại từ danh sách gợi ý.')
                $('#product').focus();
                return false;
            }
            
            if ($('#auto_serial').is(':checked')) {
                let quantityInput = parseInt($('#quantity').val());
                if (!quantityInput || quantityInput <= 0) {
                    $('.quantity_error').text('Số lượng phải lơn hơn 0.');
                    $('#quantity').focus();
                    return false;
                }
            }
            if ($('#import_serial').is(':checked')) {
                let serial_range = $('#serial_range').val().trim();
                let error = validateSerialRanges(serial_range);
                let isValid = /^[A-Za-z0-9,\-\s]+$/.test(serial_range);
                if (!isValid) {
                    $('.serial_range_error').text('Chỉ được nhập chữ, số, dấu phẩy (,) và dấu gạch ngang (-).');
                    $('#serial_range').focus();
                    return false;
                }
    
                if (error) {
                    $('.serial_range_error').text(error);
                    $('#serial_range').focus();
                    return false;
                }
            }
            if ($('#import_excel').is(':checked')) {
                let file = $('#serial_file')[0].files[0];
                if (!file) {
                    $('.serial_file_error').text('File không được để trống.');
                    return false;
                }
            }

            // if(brand == 'kuchen'){
            //     if ($('#auto_serial').is(':checked')) {
            //         let quantityInput = parseInt($('#quantity').val());
            //         if (!quantityInput || quantityInput <= 0) {
            //             $('.quantity_error').text('Số lượng phải lơn hơn 0.');
            //             $('#quantity').focus();
            //             return false;
            //         }
            //     }
            //     else{
            //         let serial_range = $('#serial_range').val().trim();
            //         let error = validateSerialRanges(serial_range);
            //         let isValid = /^[A-Za-z0-9,\-\s]+$/.test(serial_range);
            //         if (!isValid) {
            //             $('.serial_range_error').text('Chỉ được nhập chữ, số, dấu phẩy (,) và dấu gạch ngang (-).');
            //             $('#serial_range').focus();
            //             return false;
            //         }

            //         if (error) {
            //             $('.serial_range_error').text(error);
            //             $('#serial_range').focus();
            //             return false;
            //         }
            //     }
            // }

            // if(brand == 'hurom'){
            //     let serial_range = $('#serial_range').val().trim();
            //     let error = validateSerialRanges(serial_range);
            //     let isValid = /^[A-Za-z0-9,\-\s]+$/.test(serial_range);
            //     if (!isValid) {
            //         $('.serial_range_error').text('Chỉ được nhập chữ, số, dấu phẩy (,) và dấu gạch ngang (-).');
            //         $('#serial_range').focus();
            //         return false;
            //     }
    
            //     if (error) {
            //         $('.serial_range_error').text(error);
            //         $('#serial_range').focus();
            //         return false;
            //     }
            // }
    
            return true;
        }
        
        function validateSerialRanges(serialInput) {
            const cleanedInput = serialInput.toUpperCase().replace(/\n/g, ',').trim();
            const parts = cleanedInput.split(',').map(s => s.trim()).filter(s => s);
            const allSerials = [];
            const duplicates = [];
    
            for (let range of parts) {
                if (range.includes('-')) {
                    const [start, end] = range.split('-').map(s => s.trim());
    
                    if (start.length !== end.length) {
                        return `Dải "${range}" không hợp lệ`;
                    }

                    const matchStart = start.match(/^([A-Z]*)(\d+)$/);
                    const matchEnd = end.match(/^([A-Z]*)(\d+)$/);
    
                    if (!matchStart || !matchEnd) {
                        return `Dải "${range}" không hợp lệ`;
                    }
    
                    const prefixStart = matchStart[1];
                    const numberStart = matchStart[2];
                    const prefixEnd = matchEnd[1];
                    const numberEnd = matchEnd[2];
    
                    if (prefixStart !== prefixEnd && prefixEnd !== '') {
                        return `Dải "${range}" không hợp lệ`;
                    }
    
                    if (parseInt(numberEnd) < parseInt(numberStart)) {
                        return `Dải "${range}" không hợp lệ - số kết thúc nhỏ hơn số bắt đầu`;
                    }
                    
                    // Tạo tất cả các số sê-ri trong phạm vi và kiểm tra các bản sao
                    const length = numberStart.length;
                    for (let i = parseInt(numberStart); i <= parseInt(numberEnd); i++) {
                        const serial = prefixStart + i.toString().padStart(length, '0');
                        if (allSerials.includes(serial)) {
                            duplicates.push(serial);
                        } else {
                            allSerials.push(serial);
                        }
                    }
                } else {
                    // Serial đơn
                    if (allSerials.includes(range)) {
                        duplicates.push(range);
                    } else {
                        allSerials.push(range);
                    }
                }
            }
            
            // Kiểm tra các serial trùng lặp
            if (duplicates.length > 0) {
                return `Serial trùng lặp: ${duplicates.join(', ')}`;
            }
            
            return null;
        }
        
        function OpenFormCreate() {
            $('#openform').on('click', function(e) {
                e.preventDefault();
                $('#product_id').val('');
                $('#product').val('');
                $('#quantity').val('');
                $('#serial_range').val('');
                $('.error').text('');
                var myModal = new bootstrap.Modal(document.getElementById('warrantyModal'));
                myModal.show();
            });
        }
        
        function ProductInput() {
            $('#product').on('input', function() {
                let keyword = $(this).val().toLowerCase().trim();
                let $suggestionsBox = $('#product_suggestions');
                $suggestionsBox.empty();
                
                // Clear product_id when user types
                $('#product_id').val('');
    
                if (!keyword) {
                    $suggestionsBox.addClass('d-none');
                    return;
                }
    
                let matchedProducts = productList.filter(p =>
                    p.product_name.toLowerCase().includes(keyword)
                );
    
                if (matchedProducts.length > 0) {
                    matchedProducts.slice(0, 10).forEach(p => {
                        $suggestionsBox.append(
                            `<button type="button" class="list-group-item list-group-item-action" data-id="${p.id}">${p.product_name}</button>`
                        );
                    });
                    $suggestionsBox.removeClass('d-none');
                } else {
                    $suggestionsBox.addClass('d-none');
                }
            });
        }
            // Khi người dùng chọn sản phẩm gợi ý
            $(document).on('mousedown', '#product_suggestions button', function() {
            $('#product').val($(this).text());
            $('#product_id').val($(this).data('id'));
            $('#product_suggestions').addClass('d-none');
        });
            // Ẩn gợi ý khi click ra ngoài
            $(document).on('click', function(e) {
            if (!$(e.target).closest('#product, #product_suggestions').length) {
                $('#product_suggestions').addClass('d-none');
            }
        });
        
        function Search(){
            $('#searchCard').on('click', function(){
                const sophieu = $('#sophieu').val();
                const tensp = $('#tensp').val();
                const tungay = $('#tungay').val();
                const denngay = $('#denngay').val();
                $.get("{{ route('warrantycard.search') }}",{ sophieu: sophieu, tensp: tensp, tungay: tungay, denngay: denngay }, function(html) {
                    $('#tableContent').html(html);
                });
            });
        }
            
        $('#exportActiveWarranty').on('click', function (e) {
            e.preventDefault();
            const tungay = $('#tungay').val();
            const denngay = $('#denngay').val();
            const queryParams = new URLSearchParams({
                fromDate: tungay,
                toDate: denngay
            });
            OpenWaitBox();
            fetch(`{{ route('baocaokichhoatbaohanh') }}?${queryParams.toString()}`)
            .then(response => {
                CloseWaitBox();
                const contentType = response.headers.get("Content-Type");
                if (contentType.includes("application/json")) {
                    hasError = true;
                    return response.json().then(json => {
                        Swal.fire({
                            icon: 'error',
                            text: json.message
                        });
                    });
                } else {
                    return response.blob().then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = "Báo cáo kích hoạt bảo hành.xlsx";
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });
                }
            })
            .catch(error => {
                hasError = true;
                CloseWaitBox();
                Swal.fire({
                    icon: 'error',
                    text: 'Lỗi server.'
                });
                console.error(error);
            })
        });
    </script>
@endsection