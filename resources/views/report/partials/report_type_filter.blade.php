<div class="mb-3 d-flex justify-content-start" id="reportTypeFilterContainer">
    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
        <label for="reportType" class="form-label mb-0 fw-semibold text-nowrap" style="color: #495057;">
            Loại báo cáo:
        </label>
        
        <select id="reportType" name="reportType" class="form-select form-select-sm" style="width: auto; min-width: 140px;">
            <option value="weekly" {{ $reportType == '' || $reportType == 'weekly' ? 'selected' : '' }}>Theo Tuần</option>
            <option value="monthly" {{ $reportType == 'monthly' ? 'selected' : '' }}>Theo Tháng</option>
        </select>
        
        <button type="button" id="btnFilterReportType" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
            <img src="{{ asset('icons/filter.png') }}" alt="Filter Icon" style="width: 14px; height: 14px;">
            <span>Lọc</span>
        </button>
        
        @if(isset($weekNumber) && isset($monthNumber))
            <span class="badge text-dark ms-2" id="reportPeriodInfo" style="font-size: 0.875rem;">
                @if($reportType == 'weekly' || $reportType == '')
                    Tuần thứ {{ $weekNumber }} của tháng {{ $monthNumber }}
                @elseif($reportType == 'monthly')
                    @php
                        $fromDateFormatted = isset($fromDate) ? \Carbon\Carbon::parse($fromDate)->format('d/m/Y') : '';
                        $toDateFormatted = isset($toDate) ? \Carbon\Carbon::parse($toDate)->format('d/m/Y') : '';
                    @endphp
                    Tháng {{ $monthNumber }}@if($fromDateFormatted && $toDateFormatted) (từ {{ $fromDateFormatted }} đến {{ $toDateFormatted }})@endif
                @endif
            </span>
        @endif
    </div>
</div>

