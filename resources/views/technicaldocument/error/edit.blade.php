@extends('layout.layout')

@section('content')
    <div class="container-fluid py-5 bg-light">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold mb-0 text-primary">Sửa Mã Lỗi</h5>
                        @if($error->productModel)
                            <small class="text-muted">Sản phẩm: {{ $error->productModel->product->product_name ?? '' }} - Xuất
                                xứ:
                                {{ $error->productModel->xuat_xu }}</small>
                        @endif
                    </div>
                    <div class="card-body p-4">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <form action="{{ route('warranty.document.errors.update', $error->id) }}" method="post">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mã lỗi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="error_code"
                                    value="{{ old('error_code', $error->error_code) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tên lỗi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="error_name"
                                    value="{{ old('error_name', $error->error_name) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mức độ nghiêm trọng</label>
                                <select class="form-select" name="severity">
                                    <option value="normal" {{ old('severity', $error->severity) == 'normal' ? 'selected' : '' }}>Bình thường</option>
                                    <option value="common" {{ old('severity', $error->severity) == 'common' ? 'selected' : '' }}>Phổ biến / Thường gặp</option>
                                    <option value="critical" {{ old('severity', $error->severity) == 'critical' ? 'selected' : '' }}>Nghiêm trọng</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cách xử lý</label>
                                <textarea class="form-control" name="description"
                                    rows="4">{{ old('description', $error->description) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('warranty.document.errors.index', ['model_id' => $error->model_id]) }}"
                                    class="btn btn-light border">Hủy bỏ</a>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Cập
                                    nhật</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection