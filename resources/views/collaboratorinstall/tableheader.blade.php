<ul class="nav nav-tabs flex-nowrap" id="collaborator_tab">
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'dieuphoidonhang' ? 'active' : '' }}" data-tab="dieuphoidonhang" href="#">
            ĐƠN HÀNG <span class="text-danger">({{ $counts['dieuphoidonhang'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'dieuphoidonhangle' ? 'active' : '' }}" data-tab="dieuphoidonhangle" href="#">
            ĐƠN HÀNG LẺ <span class="text-danger">({{ $counts['dieuphoidonhangle'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'dieuphoibaohanh' ? 'active' : '' }}" data-tab="dieuphoibaohanh" href="#">
            CA BẢO HÀNH <span class="text-danger">({{ $counts['dieuphoibaohanh'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'dadieuphoi' ? 'active' : '' }}" data-tab="dadieuphoi" href="#">
            ĐÃ ĐIỀU PHỐI <span class="text-danger">({{ $counts['dadieuphoi'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'dahoanthanh' ? 'active' : '' }}" data-tab="dahoanthanh" href="#">
            ĐÃ HOÀN THÀNH <span class="text-danger">({{ $counts['dahoanthanh'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'dathanhtoan' ? 'active' : '' }}" data-tab="dathanhtoan" href="#">
            ĐÃ THANH TOÁN <span class="text-danger">({{ $counts['dathanhtoan'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'dailylapdat' ? 'active' : '' }}" data-tab="dailylapdat" href="#">
            ĐẠI LÝ LẮP ĐẶT <span class="text-danger">({{ $counts['dailylapdat'] ?? 0 }})</span>
        </a>
    </li>
</ul>