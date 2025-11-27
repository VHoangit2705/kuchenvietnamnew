<ul class="nav nav-tabs flex-nowrap" id="warrantyTabs">
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'warranty' ? 'active' : '' }}" data-tab="warranty" href="#">
            Thống kê ca bảo hành <span class="text-danger">({{ $counts['warranty'] ?? 0 }})</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab == 'work_process' ? 'active' : '' }}" data-tab="work_process" href="#">
            Thống kê quá trình làm việc <span class="text-danger">({{ $counts['work_process'] ?? 0 }})</span>
        </a>
    </li>
</ul>