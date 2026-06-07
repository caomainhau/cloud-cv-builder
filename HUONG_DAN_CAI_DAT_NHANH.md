# Cài nhanh CloudCV Builder v1.3

## Chạy local bằng Docker Desktop

Tại thư mục dự án:

```powershell
docker compose up --build
```

Mở website:

```text
http://localhost:8080
```

Mở hộp thư kiểm thử local:

```text
http://localhost:8080/dev/mailbox
```

Docker Compose đã bật xác minh email local. Khi đăng ký tài khoản mới, mở hộp thư kiểm thử và bấm link xác minh.

Dừng website nhưng giữ dữ liệu:

```powershell
docker compose down
```

Xóa database local để thử lại từ đầu:

```powershell
docker compose down -v
```

## Deploy lên Render

1. Push source lên GitHub.
2. Trong Render Dashboard chọn **New** → **Blueprint**.
3. Kết nối repository.
4. Deploy Blueprint.
5. Kiểm tra:

```text
https://<TEN-DICH-VU>.onrender.com/health
```

Kết quả đúng:

```text
ok
```

Blueprint mặc định chưa bắt buộc xác minh email để tránh khóa người dùng khi chưa có cấu hình gửi mail thật.

Để bật email thật, đọc:

```text
RESEND_SETUP.md
```

## Sau khi nâng cấp từ v1.2

Đọc:

```text
HUONG_DAN_NANG_CAP_V1.3.md
TEST_CHECKLIST_V1.3.md
```
