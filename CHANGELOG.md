# Changelog

## v1.3.0 Account Recovery Release

- Thêm trạng thái xác minh email cho tài khoản.
- Tự đánh dấu tài khoản tồn tại trước migration là đã xác minh để không phá vỡ luồng sử dụng hiện tại.
- Thêm gửi lại email xác minh với rate limit.
- Thêm quên mật khẩu và đặt lại mật khẩu bằng token một lần, hết hạn sau 60 phút.
- Thêm `Mailer` với chế độ `log` để kiểm thử local và chế độ `resend` để gửi email thật trên Render.
- Thêm hộp thư kiểm thử local tại `/dev/mailbox`.
- Thêm cập nhật họ tên hồ sơ tài khoản.
- Thêm tải toàn bộ dữ liệu cá nhân thành JSON.
- Thêm xóa vĩnh viễn tài khoản sau khi nhập lại mật khẩu và chuỗi xác nhận `XOA`.
- Thêm bảng `email_verification_tokens`, `password_reset_tokens` và `mail_request_attempts`.
- Thêm cột `users.email_verified_at` và `users.session_version`.
- Tự vô hiệu hóa phiên đăng nhập cũ sau khi đổi hoặc đặt lại mật khẩu.
- Bổ sung cURL extension trong Docker image để gọi Resend API.

## v1.2.0 UX Release

- Thêm autosave cho trang chỉnh sửa CV với debounce phía trình duyệt.
- Thêm trạng thái lưu: chưa lưu, đang lưu, đã lưu và lỗi lưu tự động.
- Chỉ cảnh báo khi rời trang nếu vẫn còn thay đổi chưa được lưu.
- Khi bấm mở bản in, editor chờ autosave hoàn tất để giảm nguy cơ xem bản cũ.
- Thêm kéo thả thứ tự các dòng trong từng nhóm nội dung.
- Thêm nút lên/xuống để đổi thứ tự trên thiết bị không thuận tiện kéo thả.
- Thêm ẩn/hiện từng dòng mà không xóa dữ liệu.
- Thêm link chia sẻ CV công khai bằng token ngẫu nhiên.
- Chỉ lưu SHA-256 hash của token chia sẻ trong database.
- Cho phép đặt thời hạn link: 7, 30, 90 ngày hoặc không hết hạn.
- Link mới tự động thu hồi link cũ của cùng CV.
- Thêm thu hồi link thủ công, thống kê lượt xem và thời điểm xem gần nhất.
- Thêm bảng `resume_shares` bằng migration tương thích ngược.
- Ghi activity log khi tạo hoặc thu hồi link chia sẻ.

## v1.1.0 Safe Release

- Thêm xuất CV thành file JSON backup.
- Thêm nhập CV từ JSON để khôi phục hoặc sao chép dữ liệu.
- Thêm trang tài khoản và đổi mật khẩu.
- Thêm bảng `activity_logs` và giao diện xem hoạt động gần đây.
- Thêm rate limit đăng nhập lưu trong database theo email hash và địa chỉ IP.
- Ghi log khi đăng nhập, đăng xuất, tạo, sửa, sao chép, xóa, nhập và xuất CV.
- Ghi log khi người dùng thử truy cập CV không thuộc sở hữu của mình.
- Bổ sung HSTS khi chạy production qua HTTPS.
- Thêm `public/router.php` để chạy PHP development server đúng với các route đẹp.

## v1.0.0

- Deploy thành công lên Render.
- Đăng ký, đăng nhập và đăng xuất.
- Tạo, sửa, sao chép và xóa CV.
- Hai mẫu CV.
- Live preview.
- Xuất PDF bằng trình duyệt.
- PostgreSQL database.
- Docker deployment.
