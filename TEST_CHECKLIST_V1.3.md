# Checklist kiểm thử CloudCV Builder v1.3

## A. Kiểm tra không mất dữ liệu

- [ ] Đăng nhập bằng tài khoản cũ.
- [ ] Mở CV cũ và kiểm tra nội dung.
- [ ] Link chia sẻ v1.2 còn hoạt động.
- [ ] `/health` trả về `ok`.

## B. Xác minh email local

- [ ] Chạy Docker local với `MAIL_MODE=log` và `REQUIRE_EMAIL_VERIFICATION=true`.
- [ ] Đăng ký tài khoản mới.
- [ ] Tài khoản được chuyển đến `/verify-email`.
- [ ] Dashboard chưa mở trước khi xác minh.
- [ ] Mở `/dev/mailbox` và bấm link xác minh.
- [ ] Sau khi xác minh, dashboard mở bình thường.
- [ ] Link xác minh chỉ dùng được một lần.
- [ ] Thử gửi lại email quá nhiều lần và kiểm tra rate limit.

## C. Quên mật khẩu

- [ ] Đăng xuất.
- [ ] Mở `/forgot-password`.
- [ ] Nhập email có tồn tại.
- [ ] Mở `/dev/mailbox`, lấy link đặt lại mật khẩu.
- [ ] Đặt mật khẩu mới.
- [ ] Đăng nhập bằng mật khẩu mới thành công.
- [ ] Mật khẩu cũ không còn dùng được.
- [ ] Link reset cũ không thể dùng lần thứ hai.
- [ ] Nhập email không tồn tại vẫn nhận thông báo chung, không lộ thông tin tài khoản.

## D. Hồ sơ và dữ liệu cá nhân

- [ ] Đổi họ tên trong trang `/account`.
- [ ] Tải `cloudcv-personal-data-YYYY-MM-DD.json`.
- [ ] File JSON không chứa password hash hoặc raw token.
- [ ] JSON có hồ sơ, CV, link chia sẻ dạng metadata và activity log.

## E. Xóa tài khoản

- [ ] Tạo một tài khoản test riêng.
- [ ] Tải JSON backup trước khi xóa.
- [ ] Nhập sai mật khẩu: không xóa được.
- [ ] Không nhập `XOA`: không xóa được.
- [ ] Nhập đúng mật khẩu và `XOA`: xóa được.
- [ ] Đăng nhập lại tài khoản đã xóa: thất bại.
- [ ] Link chia sẻ của tài khoản đã xóa không còn mở được.

## F. Cloud với Resend

- [ ] Thêm `APP_URL`, `MAIL_MODE=resend`, `RESEND_API_KEY`, `MAIL_FROM` trên Render.
- [ ] Chưa bật bắt buộc xác minh ngay.
- [ ] Thử luồng quên mật khẩu bằng tài khoản test.
- [ ] Email đến đúng hộp thư và link dùng domain cloud chính xác.
- [ ] Bật `REQUIRE_EMAIL_VERIFICATION=true`.
- [ ] Đăng ký tài khoản cloud mới và xác minh thành công.
