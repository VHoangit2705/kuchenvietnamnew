Logic xử lý check đơn trùng và các logic check của flow hoạt động hệ thống

Logic check đơn trùng

Tạo số thứ tự cho mã đơn hàng (generateOrderCodeWithSequence)

Mục đích
Xử lý trường hợp cùng một đại lý gửi nhiều yêu cầu với cùng mã đơn hàng gốc, tự động tạo số thứ tự để phân biệt các yêu cầu.

Đầu vào
- originalOrderCode: Mã đơn hàng gốc (đã bỏ số thứ tự nếu có)
- agencyId: ID của đại lý

Đầu ra
- Mã đơn hàng cuối cùng: Mã gốc nếu chưa có request nào, hoặc mã gốc + số thứ tự (ví dụ: "VNI123 (2)") nếu đã có request trước đó

Logic hoạt động
1. Kiểm tra agencyId: Nếu không có agencyId, trả về mã gốc ngay lập tức
2. Tìm các request đã tồn tại: Tìm tất cả RequestAgency của cùng đại lý với mã đơn hàng gốc (bao gồm cả mã gốc và các mã có số thứ tự)
3. Xác định số thứ tự:
   - Nếu chưa có request nào: Trả về mã gốc
   - Nếu đã có request với mã gốc: Request đầu tiên được tính là sequence = 1
   - Nếu có request với số thứ tự (ví dụ: "VNI123 (2)"): Lấy số thứ tự lớn nhất
4. Tạo mã mới: Nếu maxSequence > 0, tạo mã mới với số thứ tự = maxSequence + 1

Ví dụ
- Đại lý A gửi yêu cầu với mã "VNI123" lần đầu: Giữ nguyên "VNI123"
- Đại lý A gửi yêu cầu với mã "VNI123" lần 2: Tạo "VNI123 (2)"
- Đại lý A gửi yêu cầu với mã "VNI123" lần 3: Tạo "VNI123 (3)"

Kiểm tra đại lý khác đã dùng mã đơn hàng (isLatestRequestWithOtherAgency)

Mục đích
Kiểm tra xem có đại lý khác đã sử dụng mã đơn hàng gốc này chưa, và đánh dấu yêu cầu mới nhất trong số các yêu cầu có cùng mã đơn hàng gốc.

Đầu vào
- orderCode: Mã đơn hàng (có thể có số thứ tự)
- currentAgencyId: ID của đại lý hiện tại
- currentRequestId: ID của request hiện tại
- currentCreatedAt: Thời gian tạo request hiện tại

Đầu ra
- true: Nếu request này là request mới nhất trong số các request có cùng mã đơn hàng gốc và có đại lý khác đã dùng mã này
- false: Nếu không có đại lý khác dùng mã này hoặc request này không phải là mới nhất

Logic hoạt động
1. Lấy mã đơn hàng gốc: Bỏ số thứ tự nếu có (ví dụ: "VNI123 (2)" -> "VNI123")
2. Tìm request của đại lý khác: Tìm tất cả RequestAgency có mã đơn hàng gốc (chỉ mã gốc, không có số thứ tự) và agency_id khác với đại lý hiện tại
3. Kiểm tra điều kiện:
   - Nếu không có đại lý khác dùng mã này: Trả về false
   - Nếu có đại lý khác dùng mã này: Tìm request mới nhất trong số tất cả request có cùng mã đơn hàng gốc
4. Đánh dấu: Chỉ đánh dấu nếu request hiện tại là request mới nhất

Lưu ý
- Chỉ đánh dấu mã đơn hàng gốc (không có số thứ tự)
- Chỉ đánh dấu request mới nhất, không đánh dấu các request cũ hơn
- Mục đích: Cảnh báo nhân viên về việc có đại lý khác đã sử dụng mã đơn hàng này

Các logic check trong flow hoạt động

Validation khi tạo yêu cầu mới (store)

Kiểm tra dữ liệu đầu vào
- order_code: Bắt buộc, string, tối đa 100 ký tự
- product_name: Bắt buộc, string, tối đa 255 ký tự
- customer_name: Bắt buộc, string, tối đa 255 ký tự
- customer_phone: Bắt buộc, string, tối đa 20 ký tự
- installation_address: Bắt buộc, string
- notes: Tùy chọn, string
- agency_name: Tùy chọn, string, tối đa 255 ký tự
- agency_phone: Tùy chọn, string, tối đa 20 ký tự

Logic xử lý sau validation
1. Tìm agency_id: Nếu có agency_phone, tìm Agency theo số điện thoại
2. Xử lý mã đơn hàng: Lấy mã gốc (bỏ số thứ tự) và tạo số thứ tự nếu cần
3. Tạo RequestAgency: Với status mặc định = STATUS_CHUA_XAC_NHAN_AGENCY

Kiểm tra trạng thái khi xác nhận đại lý (confirmAgency)

Kiểm tra điều kiện
- RequestAgency phải có status = STATUS_CHUA_XAC_NHAN_AGENCY
- Nếu không đúng trạng thái: Trả về lỗi "Yêu cầu này không ở trạng thái Chưa xác nhận đại lý"

Validation
- received_by: Tùy chọn, string, tối đa 255 ký tự
- notes: Tùy chọn, string

Logic cập nhật
1. Lưu trạng thái cũ: Để gửi thông báo
2. Cập nhật trạng thái: status = STATUS_DA_XAC_NHAN_AGENCY
3. Cập nhật thời gian: received_at = now()
4. Cập nhật người xác nhận: received_by = từ input hoặc user hiện tại
5. Cập nhật ghi chú: Nếu có notes, append với format "[Xác nhận đại lý] {notes}"
6. Gửi thông báo: Gửi thông báo cho đại lý về việc thay đổi trạng thái

Kiểm tra đại lý lần đầu (manageAgencies)

Điều kiện lọc
1. Chỉ lấy request có status = STATUS_CHUA_XAC_NHAN_AGENCY
2. Chỉ lấy request có agency_id không null
3. Sắp xếp theo thời gian tạo (cũ nhất trước) để lấy request đầu tiên

Logic nhóm theo đại lý
1. Nhóm các request theo agency_id
2. Chỉ lấy request đầu tiên (cũ nhất) của mỗi đại lý
3. Loại bỏ các đại lý đã có ít nhất một request được xác nhận (status = STATUS_DA_XAC_NHAN_AGENCY)

Kết quả
- Danh sách chỉ chứa các đại lý gửi yêu cầu lần đầu và chưa được xác nhận
- Mỗi đại lý chỉ xuất hiện một lần với request đầu tiên của họ

Đồng bộ trạng thái từ InstallationOrder (syncStatusFromInstallationOrder)

Điều kiện đồng bộ
1. Chỉ đồng bộ các RequestAgency có order_code không null
2. Chỉ đồng bộ các RequestAgency có status trong: STATUS_DA_XAC_NHAN_AGENCY, STATUS_DA_DIEU_PHOI, STATUS_HOAN_THANH, STATUS_DA_THANH_TOAN
3. KHÔNG đồng bộ STATUS_CHUA_XAC_NHAN_AGENCY để người dùng có thể thao tác xác thực đại lý

Logic tìm InstallationOrder
1. Tìm InstallationOrder theo order_code
2. Chỉ đồng bộ với đại lý lắp đặt (collaborator_id không null)
3. Chỉ đồng bộ khi status_install >= 1

Mapping trạng thái
- status_install = 1 -> STATUS_DA_DIEU_PHOI
- status_install = 2 -> STATUS_HOAN_THANH
- status_install = 3 -> STATUS_DA_THANH_TOAN

Logic cập nhật
1. Chỉ cập nhật nếu trạng thái mới khác trạng thái hiện tại
2. Nếu chưa có assigned_to, set assigned_to = user hiện tại
3. Gửi thông báo cho đại lý khi trạng thái thay đổi

Kiểm tra khi cập nhật trạng thái (updateStatus)

Validation
- status: Bắt buộc, phải là một trong các giá trị hợp lệ từ RequestAgency::getStatuses()

Logic xử lý đặc biệt
1. Nếu chuyển sang STATUS_DA_XAC_NHAN_AGENCY và chưa có received_at:
   - Tự động set received_at = now()
   - Tự động set received_by = user hiện tại từ session
2. Nếu có collaborator_id trong request: Cập nhật collaborator_id
3. Gửi thông báo: Chỉ gửi nếu trạng thái thực sự thay đổi

Flow hoạt động tổng quan

Tạo yêu cầu mới
1. Validate dữ liệu đầu vào
2. Tìm agency_id từ agency_phone (nếu có)
3. Xử lý mã đơn hàng: Tạo số thứ tự nếu cùng đại lý và cùng mã gốc
4. Tạo RequestAgency với status = STATUS_CHUA_XAC_NHAN_AGENCY
5. Redirect về danh sách

Xác nhận đại lý
1. Kiểm tra trạng thái hiện tại phải là STATUS_CHUA_XAC_NHAN_AGENCY
2. Validate received_by và notes
3. Cập nhật trạng thái, received_at, received_by
4. Gửi thông báo cho đại lý
5. Redirect về danh sách quản lý xác nhận

Đồng bộ trạng thái
1. Chạy tự động trước khi hiển thị danh sách
2. Tìm các RequestAgency đã được xác nhận
3. Tìm InstallationOrder tương ứng theo order_code
4. Đồng bộ trạng thái từ status_install sang status
5. Gửi thông báo nếu trạng thái thay đổi

Hiển thị danh sách
1. Đồng bộ trạng thái từ InstallationOrder
2. Lọc dữ liệu theo các điều kiện tìm kiếm
3. Loại trừ các request có status = STATUS_CHUA_XAC_NHAN_AGENCY
4. Đánh dấu các request có đại lý khác đã dùng mã đơn hàng
5. Đếm số lượng theo từng trạng thái
6. Phân trang và hiển thị
