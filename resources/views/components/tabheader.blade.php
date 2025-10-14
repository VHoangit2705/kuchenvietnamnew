<ul class="nav nav-tabs flex-nowrap" id="warrantyTabs">
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'danhsach' ? 'active' : '' }}" data-tab="danhsach" href="#">
            Danh sách phiếu <span class="text-danger">({{ $counts['danhsach'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'dangsua' ? 'active' : '' }}" data-tab="dangsua" href="#">
            Đang sửa chữa <span class="text-danger">({{ $counts['dangsua'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'chophanhoi' ? 'active' : '' }}" data-tab="chophanhoi" href="#">
            Chờ khách hàng phản hồi <span class="text-danger">({{ $counts['chophanhoi'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'quahan' ? 'active' : '' }}" data-tab="quahan" href="#">
            Quá hạn sửa chữa <span class="text-danger">({{ $counts['quahan'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'hoantat' ? 'active' : '' }}" data-tab="hoantat" href="#">
            Đã hoàn tất <span class="text-danger">({{ $counts['hoantat'] ?? 0 }})</span>
        </a>
    </li>
</ul>