{{-- Product Filter Component --}}
{{-- 
    Usage:
    <x-technicaldocument.product-filter 
        :categories="$categories" 
        variant="desktop-pill|desktop-floating|mobile|simple"
        idPrefix="index|create|doc"
    />
--}}

@props([
    'categories',
    'variant' => 'desktop-pill',
    'idPrefix' => '',
    'showAddOriginButton' => false,
    'selectedCategoryId' => '',
    'selectedProductId' => '',
    'selectedOrigin' => '',
    'enableFormSubmission' => false,  // New prop to enable form field names
])

@php
    // Base IDs for fallback and mobile
    $categoryBase = 'categorySelect';
    $productBase = 'productNameSelect';
    $originBase = 'originSelect';
    $searchBase = 'btnSearch';

    if ($idPrefix === 'm') {
        // Special case for mobile IDs used in index.js
        $ids = [
            'category' => "{$categoryBase}_m",
            'product' => "{$productBase}_m",
            'origin' => "{$originBase}_m",
            'searchBtn' => "{$searchBase}_m",
        ];
    } else {
        // Default naming convention (Prepend)
        $ids = [
            'category' => $idPrefix ? "{$idPrefix}Category" : $categoryBase,
            'product' => $idPrefix ? "{$idPrefix}Product" : $productBase,
            'origin' => $idPrefix ? "{$idPrefix}Origin" : $originBase,
            'searchBtn' => $idPrefix ? "btn{$idPrefix}Search" : $searchBase,
        ];
    }
    
    // Field names for form submission (legacy-style if needed)
    $names = $enableFormSubmission ? [
        'category' => 'category_id',
        'product' => 'product_id',
        'origin' => 'xuat_xu',
    ] : [];
@endphp

@if($variant === 'desktop-pill')
    {{-- Horizontal pill design for index page --}}
    <div class="card border-0 shadow-lg rounded-pill overflow-hidden">
        <div class="card-body p-1">
            <div class="row g-0 align-items-center">
                <div class="col-4 border-end">
                    <select class="form-select border-0 py-3 ps-4 fw-semibold" id="{{ $ids['category'] }}" style="border-radius: 30px 0 0 30px;">
                        <option selected disabled>Danh mục sản phẩm</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name_vi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 border-end">
                    <select class="form-select border-0 py-3 ps-3" id="{{ $ids['product'] }}" disabled>
                        <option selected disabled>Chọn sản phẩm...</option>
                    </select>
                </div>
                <div class="col-3 border-end">
                    <select class="form-select border-0 py-3 ps-3" id="{{ $ids['origin'] }}" disabled>
                        <option selected disabled>Xuất xứ...</option>
                    </select>
                </div>
                <div class="col-1 pe-1">
                    <button class="btn btn-primary w-100 rounded-pill py-3 h-100" type="button" id="{{ $ids['searchBtn'] }}" disabled>
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

@elseif($variant === 'mobile')
    {{-- Stacked vertical design for mobile --}}
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body p-3">
            <div class="d-grid gap-3">
                <select class="form-select py-3" id="{{ $ids['category'] }}">
                    <option selected disabled>Danh mục sản phẩm</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name_vi }}</option>
                    @endforeach
                </select>
                
                <select class="form-select py-3" id="{{ $ids['product'] }}" disabled>
                    <option selected disabled>Chọn sản phẩm...</option>
                </select>
                
                <select class="form-select py-3" id="{{ $ids['origin'] }}" disabled>
                    <option selected disabled>Xuất xứ...</option>
                </select>
                
                <button class="btn btn-primary w-100 py-3 rounded-pill" type="button" id="{{ $ids['searchBtn'] }}" disabled>
                    <i class="bi bi-search me-2"></i>Tìm kiếm
                </button>
            </div>
        </div>
    </div>

@elseif($variant === 'desktop-floating')
    {{-- Floating labels design for create page --}}
    <div class="row g-3">
        <div class="col-md-3">
            <div class="form-floating">
                <select class="form-select border-0 bg-light fw-bold text-primary" id="{{ $ids['category'] }}">
                    <option value="">Chọn danh mục</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name_vi }}</option>
                    @endforeach
                </select>
                <label for="{{ $ids['category'] }}">Danh mục sản phẩm <span class="text-danger">*</span></label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-floating">
                <select class="form-select border-0 bg-light" id="{{ $ids['product'] }}" disabled>
                    <option value="">Chọn sản phẩm</option>
                </select>
                <label for="{{ $ids['product'] }}">Sản phẩm <span class="text-danger">*</span></label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="{{ $showAddOriginButton ? 'input-group has-validation' : 'form-floating' }}">
                <div class="form-floating {{ $showAddOriginButton ? 'flex-grow-1' : '' }}">
                    <select class="form-select border-0 bg-light" id="{{ $ids['origin'] }}" disabled>
                        <option value="">Chọn xuất xứ</option>
                    </select>
                    <label for="{{ $ids['origin'] }}">Xuất xứ <span class="text-danger">*</span></label>
                </div>
                @if($showAddOriginButton)
                    <button class="btn btn-light border-0 text-primary" type="button" id="btnAddOrigin" data-bs-toggle="modal" data-bs-target="#modalAddOrigin" disabled title="Thêm mới">
                        <i class="bi bi-plus-circle-fill fs-5"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>

@elseif($variant === 'simple')
    {{-- Simple form design for document-create/documents-index page --}}
    <div class="row g-3">
        <div class="col-md-2">
            <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $ids['category'] }}" {{ $enableFormSubmission ? 'name=category_id' : '' }} required>
                <option value="">Chọn danh mục</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ $selectedCategoryId == $c->id ? 'selected' : '' }}>{{ $c->name_vi }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Sản phẩm <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $ids['product'] }}" {{ $enableFormSubmission ? 'name=product_id' : '' }} disabled required>
                <option value="">Chọn sản phẩm</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Xuất xứ <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $ids['origin'] }}" {{ $enableFormSubmission ? 'name=' . $names['origin'] : '' }} disabled required>
                <option value="">Xuất xứ</option>
            </select>
        </div>
    </div>
@endif
