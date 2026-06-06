# Security notes

CloudCV Builder MVP đã có các biện pháp cơ bản:

- Hash mật khẩu bằng `password_hash()` và kiểm tra bằng `password_verify()`.
- Regenerate session ID sau khi đăng nhập.
- Cookie session `HttpOnly`, `SameSite=Lax` và `Secure` khi request sử dụng HTTPS.
- CSRF token cho các route POST.
- PDO prepared statements.
- Escape HTML khi hiển thị dữ liệu.
- Chỉ cho phép đọc, sửa, sao chép và xóa CV thuộc tài khoản đang đăng nhập.
- Lọc URL trước khi đưa vào liên kết.
- Không có chức năng upload file trong MVP.

Trước khi dùng như một dịch vụ thật:

- Bổ sung email verification và password reset.
- Bổ sung logging và rate limit ở reverse proxy hoặc application level.
- Thiết lập backup database.
- Thực hiện kiểm thử bảo mật và dependency review định kỳ.
- Chuyển sang một session store bền vững nếu scale nhiều instance.

## Bổ sung trong v1.1

- Rate limit đăng nhập được lưu trong database theo email hash và IP; tối đa 5 lần sai trong 15 phút.
- Activity log ghi lại các thao tác quan trọng nhưng không lưu mật khẩu hoặc nội dung CV.
- Export JSON yêu cầu đăng nhập và quyền sở hữu CV.
- Import JSON giới hạn 512 KB, chỉ nhận `.json`, kiểm tra schema và lọc lại dữ liệu trước khi lưu.
- HSTS được bật khi chạy production qua HTTPS.
