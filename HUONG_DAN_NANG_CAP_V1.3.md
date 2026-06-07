# Hướng dẫn nâng cấp CloudCV Builder từ v1.2 lên v1.3

## Chức năng mới

- Xác minh email tùy chọn.
- Gửi lại email xác minh có rate limit.
- Quên mật khẩu và đặt lại mật khẩu bằng token một lần, hết hạn sau 60 phút.
- Cập nhật họ tên hồ sơ tài khoản.
- Tải toàn bộ dữ liệu cá nhân thành JSON.
- Xóa vĩnh viễn tài khoản và CV.
- Hộp thư kiểm thử local tại `/dev/mailbox`.
- Hỗ trợ gửi email thật bằng Resend API trên Render.

## 1. Backup database cloud

```powershell
pg_dump "<EXTERNAL_DATABASE_URL>" `
  --format=custom `
  --file="cloudcv-before-v1.3.dump"
```

## 2. Chép source mới

Chép nội dung thư mục `cloud-cv-builder-v1.3` vào repository hiện tại. Giữ nguyên `.git`. Không ghi đè `.env` local nếu bạn đang dùng cấu hình riêng.

## 3. Chạy local

```powershell
docker compose up --build
```

Mở:

```text
http://localhost:8080
http://localhost:8080/dev/mailbox
```

Docker Compose local đã đặt `REQUIRE_EMAIL_VERIFICATION=true` và `MAIL_MODE=log` để bạn kiểm thử đầy đủ mà không cần API key.

## 4. Push GitHub

```powershell
git add .
git commit -m "Upgrade CloudCV Builder to v1.3 Account Recovery Release"
git tag -a v1.3.0 -m "CloudCV Builder v1.3 Account Recovery Release"
git push origin main
git push origin v1.3.0
```

Render tự build và migrate database. Migration chỉ thêm cột hoặc bảng mới, không xóa CV hiện có.

## 5. Kiểm tra cloud trước khi bật email thật

Mở:

```text
https://<TEN-DICH-VU>.onrender.com/health
```

Kết quả đúng:

```text
ok
```

Giữ tạm:

```text
MAIL_MODE=disabled
REQUIRE_EMAIL_VERIFICATION=false
```

Các tài khoản cũ tiếp tục sử dụng bình thường.

## 6. Bật email thật

Đọc `RESEND_SETUP.md`. Chỉ bật `REQUIRE_EMAIL_VERIFICATION=true` sau khi luồng gửi email qua Resend đã được kiểm tra thành công.

## Database mới

Migration thêm:

```text
users.email_verified_at
users.session_version
email_verification_tokens
password_reset_tokens
mail_request_attempts
```
