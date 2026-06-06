# Hướng dẫn nâng cấp CloudCV Builder v1.1 lên v1.2 UX Release

Bản nâng cấp giữ nguyên tài khoản, CV và lịch sử hoạt động hiện có. Migration chỉ tạo thêm bảng `resume_shares`.

## 1. Sao lưu database trước khi nâng cấp

Trong Render Dashboard, mở PostgreSQL database và lấy **External Database URL**. Không đăng URL này lên GitHub hoặc gửi ảnh chụp chứa password.

Chạy trên PowerShell đã cài PostgreSQL client:

```powershell
pg_dump "<EXTERNAL_DATABASE_URL>" `
  --format=custom `
  --file="cloudcv-before-v1.2.dump"
```

## 2. Chép source mới vào repository hiện tại

Giải nén file ZIP v1.2. Chép nội dung bên trong thư mục `cloud-cv-builder-v1.2` vào repository local hiện tại.

Không xóa thư mục `.git`. Không ghi đè file `.env` local nếu bạn đã cấu hình riêng.

## 3. Kiểm tra local bằng Docker

```powershell
docker compose up --build
```

Mở:

```text
http://localhost:8080
```

Migration tự chạy khi container khởi động. Kiểm tra các chức năng trong file `TEST_CHECKLIST_V1.2.md`.

Dừng môi trường local:

```powershell
docker compose down
```

## 4. Commit và deploy lên Render

```powershell
git add .
git commit -m "Upgrade CloudCV Builder to v1.2 UX Release"
git tag -a v1.2.0 -m "CloudCV Builder v1.2 UX Release"
git push origin main
git push origin v1.2.0
```

Render sẽ tự build lại Web Service. Migration tạo bảng `resume_shares` mà không xóa dữ liệu cũ.

## 5. Kiểm tra cloud sau deploy

Mở:

```text
https://<TEN-DICH-VU>.onrender.com/health
```

Kết quả đúng:

```text
ok
```

Sau đó chạy checklist trong `TEST_CHECKLIST_V1.2.md`.

## 6. APP_URL tùy chọn

Link chia sẻ mặc định được tạo từ host của request hiện tại. Bạn có thể cấu hình `APP_URL` trong Render Dashboard để cố định domain chính, đặc biệt khi sử dụng custom domain:

```text
APP_URL=https://cv.example.com
```
