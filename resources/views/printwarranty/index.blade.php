@extends('layout.layout')

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4 mb-1">
                <input type="number" id="sophieu" name="sophieu" class="form-control" 
                placeholder="Nhập số phiếu" value="{{ request('sophieu') }}">
            </div>
            <div class="col-md-4 mb-1 position-relative"> <input type="text" id="tensp" name="tensp" class="form-control" 
                placeholder="Nhập tên sản phẩm" value="{{ request('productSearch') }}"
                autocomplete="off"> <div id="tensp-suggestions" class="list-group position-absolute w-100 d-none" style="z-index: 1000;">
            </div>
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
            setupModalValidation(); // Thêm hàm khởi tạo validation cho modal
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

            //Gợi ý sản phẩm cho ô tìm kiếm chính
            const mainProductList = @json(collect($products)->pluck('product_name'));

            $('#tensp').on('input', function() {
                validateTensp(); 

                const keyword = $(this).val().toLowerCase().trim();
                const $suggestionsBox = $('#tensp-suggestions');
                $suggestionsBox.empty();

                if (!keyword) {
                    $suggestionsBox.addClass('d-none');
                    return; // Dừng lại nếu ó ó từ khóa
                }

                const matchedProducts = mainProductList.filter(productName =>
                    productName.toLowerCase().includes(keyword)
                );

                if (matchedProducts.length > 0) {
                    matchedProducts.slice(0, 10).forEach(productName => {
                        $suggestionsBox.append(
                            `<button type="button" class="list-group-item list-group-item-action">${productName}</button>`
                        );
                    });
                    $suggestionsBox.removeClass('d-none');
                } else {
                    $suggestionsBox.addClass('d-none');
                }
            });

            // Khi người dùng chọn sản phẩm gợi ý
            $(document).on('mousedown', '#tensp-suggestions button', function() {
                $('#tensp').val($(this).text()); // Điền text vào ô input
                $('#tensp-suggestions').addClass('d-none'); // Ẩn box gợi ý
                
                // Kích hoạt lại validation để xóa lỗi (nếu có)
                validateTensp(); 
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
                
                // Chạy validation lần cuối trước khi submit
                if (!validateModalForm()) return;

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
        
        function validateSerialRanges(serialInput) {
            const cleanedInput = serialInput.toUpperCase().replace(/\n/g, ',').trim();
            const parts = cleanedInput.split(',').map(s => s.trim()).filter(s => s);
    
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
                }
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

        // Validation modal
        let modalValidationErrors = {};

        function showModalError($field, message) {
            const fieldId = $field.attr('id');
            if (!fieldId) return;
            
            hideModalError($field);

            const $errorDiv = $field.siblings('.error');
            $errorDiv.text(message);
            
            modalValidationErrors[fieldId] = true;
            updateModalSubmitButtonsState();
        }

        function hideModalError($field) {
            const fieldId = $field.attr('id');
            if (!fieldId) return;

            const $errorDiv = $field.siblings('.error');
            $errorDiv.text('');

            delete modalValidationErrors[fieldId];
            updateModalSubmitButtonsState();
        }

        function updateModalSubmitButtonsState() {
            const hasErrors = Object.keys(modalValidationErrors).length > 0;
            $('.submit-btn').prop('disabled', hasErrors);
        }

        function validateModalProduct() {
            const $input = $('#product');
            const value = $input.val().trim();
            const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(\,+/)]+$/;

            if (!value) {
                showModalError($input, "Tên sản phẩm không được để trống.");
                return false;
            }
            if (!validRegex.test(value)) {
                showModalError($input, "Tên sản phẩm chứa ký tự không hợp lệ.");
                return false;
            }
            if (value.length > 100) {
                showModalError($input, "Tên sản phẩm không được vượt quá 100 ký tự.");
                return false;
            }
            hideModalError($input);
            return true;
        }

        function validateModalQuantity() {
            const $input = $('#quantity');
            const value = $input.val().trim();
            if (!value) {
                showModalError($input, "Số lượng không được để trống.");
                return false;
            }
            if (!/^\d+$/.test(value)) {
                showModalError($input, "Số lượng phải là số.");
                return false;
            }
            if (parseInt(value) <= 0) {
                showModalError($input, "Số lượng phải lớn hơn 0.");
                return false;
            }
            if (value.length > 10) {
                showModalError($input, "Số lượng không được vượt quá 10 chữ số.");
                return false;
            }
            hideModalError($input);
            return true;
        }

        function validateModalSerialRange() {
            const $input = $('#serial_range');
            const value = $input.val().trim();
            const validRegex = /^[A-Za-z0-9,\-]+$/;
            if (!value) {
                showModalError($input, "Dải serial không được để trống.");
                return false;
            }
            if (value.length > 50) {
                showModalError($input, "Dải serial không được vượt quá 50 ký tự.");
                return false;
            }
            if (!validRegex.test(value.replace(/\s/g, ''))) {
                showModalError($input, "Chỉ cho phép nhập chữ, số, dấu phẩy (,) và gạch ngang (-).");
                return false;
            }
            const rangeError = validateSerialRanges(value);
            if (rangeError) {
                showModalError($input, rangeError);
                return false;
            }
            hideModalError($input);
            return true;
        }

        function validateModalForm() {
            // Luôn chạy validate sản phẩm
            validateModalProduct();

            if ($('#auto_serial').is(':checked')) {
                // Chỉ validate số lượng
                validateModalQuantity();
                // Xóa lỗi của các trường khác nếu có
                hideModalError($('#serial_range'));
                hideModalError($('#serial_file'));
            }
            if ($('#import_serial').is(':checked')) {
                // Chỉ validate dải serial
                validateModalSerialRange();
                // Xóa lỗi của các trường khác nếu có
                hideModalError($('#quantity'));
                hideModalError($('#serial_file'));
            }
            if ($('#import_excel').is(':checked')) {
                // Chỉ validate file
                if (!$('#serial_file')[0].files[0]) {
                    showModalError($('#serial_file'), 'Vui lòng chọn file Excel.');
                } else {
                    hideModalError($('#serial_file'));
                }
                // Xóa lỗi của các trường khác nếu có
                hideModalError($('#quantity'));
                hideModalError($('#serial_range'));
            }
            // Trả về kết quả dựa trên cờ lỗi
            return Object.keys(modalValidationErrors).length === 0;
        }

        function setupModalValidation() {
            // Khi thay đổi lựa chọn radio, xóa lỗi của các trường không liên quan
            $('input[name="serial_option"]').on('change', function() {
                hideModalError($('#quantity'));
                hideModalError($('#serial_range'));
                hideModalError($('#serial_file'));
            });

            $('#product').on('input change', validateModalProduct);
            $('#quantity').on('input', validateModalQuantity);
            $('#serial_range').on('input', validateModalSerialRange);
        }
        
        function ProductInput() {
            $('#product').on('input', function() {
                let keyword = $(this).val().toLowerCase().trim();
                let $suggestionsBox = $('#product_suggestions');
                $suggestionsBox.empty();
    
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
            validateModalProduct(); // Validate lại khi chọn từ gợi ý
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
        $('#exportActiveWarranty').on('click', function(e) {
            e.preventDefault();

            const COOLDOWN_PERIOD_MS = 1 * 60 * 1000; // 1 phút
            const LAST_EXPORT_KEY = 'lastExportTimestamp_warranty';

            const lastExportTime = localStorage.getItem(LAST_EXPORT_KEY);
            const currentTime = Date.now();

            if (lastExportTime) {
                const timeDiff = currentTime - parseInt(lastExportTime, 10);
                if (timeDiff < COOLDOWN_PERIOD_MS) {
                    const timeLeftSeconds = Math.ceil((COOLDOWN_PERIOD_MS - timeDiff) / 1000);
                    const minutes = Math.floor(timeLeftSeconds / 60);
                    const seconds = timeLeftSeconds % 60;

                    Swal.fire({
                        icon: 'error',
                        title: 'Thao tác quá nhanh!',
                        text: `Vui lòng đợi ${minutes} phút ${seconds} giây nữa trước khi xuất lại.`
                    });
                    return;
                }
            }

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
                    if (contentType && contentType.includes("application/json")) {
                        return response.json().then(json => {
                            Swal.fire({
                                icon: 'error',
                                text: json.message
                            });
                        });
                    } else {
                        // Chỉ lưu timestamp khi tải file thành công
                        localStorage.setItem(LAST_EXPORT_KEY, currentTime.toString());
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = "Báo cáo kích hoạt bảo hành.xlsx";
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        });
                        window.URL.revokeObjectURL(url);
                    }
                })
                .catch(error => {
                    CloseWaitBox();
                    Swal.fire({
                        icon: 'error',
                        text: 'Lỗi server.'
                    });
                    console.error(error);
                });
        });

        // 1. Cờ theo dõi trạng thái lỗi của form
        let validationErrors = {};

        // 2. Hàm hiển thị lỗi
        function showError($field, message) {
            let fieldId = $field.attr('id');
            if (!fieldId) return;

            hideError($field); // Xóa lỗi cũ trước khi hiển thị lỗi mới

            // Thêm class is-invalid của Bootstrap và hiển thị thông báo
            $field.addClass('is-invalid');
            $field.closest('.col-md-4').append(`<div class="invalid-feedback d-block" data-error-for="${fieldId}">${message}</div>`);

            validationErrors[fieldId] = true; // Gắn cờ lỗi
            updateButtonState();
        }

        // 3. Hàm ẩn lỗi
        function hideError($field) {
            let fieldId = $field.attr('id');
            if (!fieldId) return;

            $field.removeClass('is-invalid');
            $field.closest('.col-md-4').find(`.invalid-feedback[data-error-for="${fieldId}"]`).remove();

            delete validationErrors[fieldId]; // Bỏ cờ lỗi
            updateButtonState();
        }

        // 4. Hàm cập nhật trạng thái nút "Tìm kiếm"
        function updateButtonState() {
            let hasErrors = Object.keys(validationErrors).length > 0;
            $('#searchCard').prop('disabled', hasErrors);
        }

        // 5. Các hàm validation cho từng trường

        // Validate số phiếu: chỉ số, max 10 ký tự
        function validateSophieu() {
            const $input = $('#sophieu');
            const value = $input.val().trim();
            hideError($input); // Luôn xóa lỗi cũ khi validate lại
            if (value && !/^\d+$/.test(value)) {
                showError($input, "Số phiếu chỉ được nhập số.");
            } else if (value && value.length > 10) {
                showError($input, "Số phiếu không vượt quá 10 ký tự.");
            }
        }

        // Validate tên sản phẩm: chữ, số, và các ký tự ()-
        function validateTensp() {
            const $input = $('#tensp');
            const value = $input.val().trim();
            const validRegex = /^[a-zA-Z0-9\sàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụüÜủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\-\(,+/)]+$/;
            hideError($input);
            if (value && !validRegex.test(value)) {
                showError($input, "Tên sản phẩm chỉ nhập chữ và số và các ký tự (,+/)");
            }else if (value.length > 100) {
                showError($input, "Tên sản phẩm không vượt quá 100 ký tự.");
            }
        }

        // Validate ngày: ngày sau không được nhỏ hơn ngày trước
        function validateDates() {
            const $fromDate = $('#tungay');
            const $toDate = $('#denngay');
            const fromDate = $fromDate.val();
            const toDate = $toDate.val();

            // Xóa lỗi cũ của cả 2 trường date
            hideError($fromDate);
            hideError($toDate);

            if (fromDate && toDate && fromDate > toDate) {
                showError($toDate, "'Đến ngày' phải lớn hơn hoặc bằng 'Từ ngày'.");
            }
        }

        // 6. Gắn sự kiện và thực thi
        $(document).ready(function() {
            // Gắn sự kiện validation cho các trường
            $('#sophieu').on('input', validateSophieu);
            $('#tungay, #denngay').on('change', validateDates);

            // Chạy validation một lần khi tải trang để kiểm tra các giá trị có sẵn
            validateSophieu();
            validateTensp();
            validateDates();

            // Xử lý khi nhấn nút tìm kiếm
            $('#searchCard').on('click', function(e) {
                // Kiểm tra lại một lần nữa trước khi gửi
                validateSophieu();
                validateTensp();
                validateDates();

                if (Object.keys(validationErrors).length > 0) {
                    e.preventDefault(); // Ngăn chặn hành động mặc định nếu có lỗi
                }
            });
        });
    </script>
@endsection