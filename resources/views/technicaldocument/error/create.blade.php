@extends('layout.layout')

@section('content')
    <div class="container-fluid py-5 bg-light">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold mb-0 text-primary">Thêm Mã Lỗi Mới</h5>
                        @if($productModel)
                            <small class="text-muted">Sản phẩm: {{ $productModel->product->product_name ?? '' }} - Xuất xứ:
                                {{ $productModel->xuat_xu }}</small>
                        @endif
                    </div>
                    <div class="card-body p-4">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <form action="{{ route('warranty.document.errors.store') }}" method="post">
                            @csrf

                            @if($productModel)
                                <input type="hidden" name="product_id" value="{{ $productModel->product_id }}">
                                <input type="hidden" name="xuat_xu" value="{{ $productModel->xuat_xu }}">
                            @else
                                <div class="alert alert-warning">
                                    Vui lòng quay lại và chọn Sản phẩm - Xuất xứ trước khi thêm lỗi.
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mã lỗi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="error_code" value="{{ old('error_code') }}"
                                    placeholder="Ví dụ: E01, ERR-MOTOR..." required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tên lỗi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="error_name" value="{{ old('error_name') }}"
                                    placeholder="Ví dụ: Lỗi Motor quá tải..." required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mức độ nghiêm trọng</label>
                                <select class="form-select" name="severity">
                                    <option value="normal" {{ old('severity') == 'normal' ? 'selected' : '' }}>Bình thường
                                    </option>
                                    <option value="common" {{ old('severity') == 'common' ? 'selected' : '' }}>Phổ biến /
                                        Thường gặp</option>
                                    <option value="critical" {{ old('severity') == 'critical' ? 'selected' : '' }}>Nghiêm
                                        trọng</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cách xử lý</label>
                                <textarea class="form-control" name="description" rows="4"
                                    placeholder="Mô tả nguyên nhân, biểu hiện...">{{ old('description') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('warranty.document.errors.index', ['model_id' => request('model_id')]) }}"
                                    class="btn btn-light border">Hủy bỏ</a>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Lưu mã
                                    lỗi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection