<div id="tablecollaborator">
    <table class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>STT</th>
                <th style="min-width: 150px;">Họ tên</th>
                <th style="min-width: 100px;">Điện thoại</th>
                <th style="min-width: 120px;">Phường/Xã</th>
                <th style="min-width: 120px;">Quận/Huyện</th>
                <th style="min-width: 120px;">Tỉnh/TP</th>
                <th style="min-width: 200px;">Địa chỉ</th>
                <th style="width: 20px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lstCollaborator as $item)
            <tr>
                <td class="text-center">{{ $loop->iteration}}</td>
                <td>{{ $item->full_name }}</td>
                <td class="text-center">{{ $item->phone }}</td>
                <td>{{ $item->ward }}</td>
                <td>{{ $item->district }}</td>
                <td>{{ $item->province }}</td>
                <td>{{ $item->address }}</td>
                <td>
                    <button
                        class="btn btn-warning btn-sm choose-ctv"
                        data-id="{{ $item->id }}"
                        data-name="{{ $item->full_name }}"
                        data-phone="{{ $item->phone }}">
                        <strong>Chọn</strong>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center">Không có dữ liệu</td>
            </tr>
            @endforelse
        <tbody>
    </table>
</div>