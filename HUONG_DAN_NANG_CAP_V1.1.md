# Hướng dẫn nâng cấp CloudCV Builder lên v1.1 Safe Release

## 1. Sao lưu trước khi nâng cấp

Sao lưu Render Postgres bằng `pg_dump` trước khi push source mới.

```powershell
pg_dump "<EXTERNAL_DATABASE_URL>" --format=custom --file="cloudcv-before-v1.1.dump"
```

Không đưa URL database vào GitHub hoặc ảnh chụp màn hình công khai.

## 2. Thay source code

Giải nén bản v1.1, sau đó chép các file vào repository GitHub local hiện tại. Không chép file `.env` cũ lên GitHub.

```powershell
git add .
git commit -m "Upgrade CloudCV Builder to v1.1 Safe Release"
git push origin main
```

Render sẽ tự build lại Web Service. Khi container khởi động, migration tự tạo thêm:

```text
activity_logs
login_attempts
```

Migration không xóa dữ liệu trong `users` và `resumes`.

## 3. Kiểm tra sau deploy

Mở lần lượt:

```text
https://<TEN-DICH-VU>.onrender.com/health
https://<TEN-DICH-VU>.onrender.com/dashboard
https://<TEN-DICH-VU>.onrender.com/account
```

Kết quả `/health` phải là:

```text
ok
```

Kiểm tra thêm:

1. Đăng nhập bằng tài khoản cũ.
2. Mở một CV cũ để xác nhận dữ liệu vẫn còn.
3. Bấm `Xuất JSON` tại dashboard.
4. Nhập lại file JSON vừa tải và xác nhận hệ thống tạo một CV mới.
5. Mở `Tài khoản`, đổi mật khẩu và đăng nhập lại.
6. Xem phần hoạt động gần đây.

## 4. Chạy local bằng Docker

```powershell
docker compose up --build
```

Truy cập:

```text
http://localhost:8080
```

## 5. Chạy local trực tiếp bằng PHP

Cần PHP có `pdo_sqlite` và `mbstring`.

```powershell
Copy-Item .env.example .env
php scripts/migrate.php
php -S localhost:8080 -t public public/router.php
```

## 6. Quay lại bản cũ khi cần

Nếu deploy mới gặp sự cố, dùng tag `v1.0.0` trong repository để khôi phục source cũ. Database v1.1 chỉ thêm bảng mới nên hai bảng dữ liệu cũ vẫn giữ nguyên.
