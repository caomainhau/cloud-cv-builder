# CloudCV Builder v1.2 UX Release

CloudCV Builder là website tạo CV nhiều người dùng, chạy local bằng Docker và deploy lên Render bằng Blueprint. Repository dùng PHP MVC nhẹ, PostgreSQL trên cloud và SQLite khi chạy PHP trực tiếp để học tập.

## Chức năng chính

- Đăng ký, đăng nhập, đăng xuất và đổi mật khẩu.
- Rate limit đăng nhập theo email hash và IP.
- Tạo, sửa, sao chép và xóa nhiều CV.
- Thông tin cá nhân, giới thiệu, học vấn, kinh nghiệm, dự án, kỹ năng, chứng chỉ, ngoại ngữ và liên kết.
- Hai mẫu: `ATS Simple` và `Modern Minimal`.
- Live preview và CSS in A4.
- Xuất PDF bằng trình duyệt.
- Xuất và nhập JSON backup.
- Activity log cho các hành động quan trọng.
- Autosave khi đang chỉnh sửa.
- Cảnh báo khi rời trang nếu còn thay đổi chưa lưu.
- Kéo thả hoặc dùng nút lên/xuống để sắp xếp nội dung.
- Ẩn hoặc hiện từng dòng mà không xóa dữ liệu.
- Tạo link chia sẻ CV công khai có token ngẫu nhiên, thời hạn và chức năng thu hồi.

## Kiến trúc

```text
Trình duyệt
    |
    | HTTPS
    v
Render Web Service
    |
    | Docker: PHP 8.3 + Apache
    v
Ứng dụng PHP MVC nhẹ
    |
    | PDO prepared statements
    v
Render Postgres
```

## Chạy local bằng Docker Desktop

```powershell
docker compose up --build
```

Mở:

```text
http://localhost:8080
```

Dừng container nhưng giữ dữ liệu:

```powershell
docker compose down
```

Xóa cả volume database local để thử lại từ đầu:

```powershell
docker compose down -v
```

## Chạy local trực tiếp bằng PHP và SQLite

Yêu cầu PHP 8.2 trở lên với extension `pdo_sqlite` và `mbstring`.

```powershell
Copy-Item .env.example .env
php scripts/migrate.php
php -S localhost:8080 -t public public/router.php
```

SQLite database local được lưu tại:

```text
storage/cloudcv.sqlite
```

Không đưa file này lên Render.

## Deploy lên Render

Source code đã có:

```text
render.yaml
Dockerfile
```

Quy trình:

1. Push source lên GitHub.
2. Trong Render Dashboard, chọn **New** → **Blueprint**.
3. Kết nối repository.
4. Deploy Blueprint.
5. Kiểm tra endpoint `/health` trả về `ok`.

Render tạo Web Service `cloud-cv-builder` và PostgreSQL database `cloud-cv-db`.

## Nâng cấp từ v1.1

Đọc:

```text
HUONG_DAN_NANG_CAP_V1.2.md
TEST_CHECKLIST_V1.2.md
```

Migration v1.2 chỉ thêm bảng `resume_shares`, không xóa dữ liệu hiện có.

## Database

```text
users
  |-- resumes
  |     `-- resume_shares
  |-- activity_logs
  `-- login_attempts
```

Các nhóm nội dung CV được lưu trong `resumes.sections_json`. Từ v1.2, mỗi dòng có thể chứa `_visible` để ẩn hoặc hiện mà không xóa dữ liệu.

## File quan trọng

```text
public/index.php                  Router chính
public/assets/editor.js           Live preview, autosave, reorder, ẩn/hiện
src/ResumeRepository.php          CRUD CV và kiểm tra quyền sở hữu
src/ResumeShareRepository.php     Link chia sẻ công khai
src/Migrator.php                  Migration PostgreSQL và SQLite
views/resume/share.php            Quản lý link chia sẻ
views/resume/public-preview.php   Bản CV công khai
SECURITY.md                       Ghi chú bảo mật
```

## Lưu ý riêng tư

Link chia sẻ cho phép người có link xem CV mà không cần đăng nhập. Hãy thu hồi link khi không còn sử dụng và tránh công khai link rộng rãi nếu CV chứa thông tin cá nhân.
