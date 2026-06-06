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
