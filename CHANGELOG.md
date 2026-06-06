# Changelog

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
