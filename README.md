# CloudCV Builder MVP

CloudCV Builder là website tạo CV nhiều người dùng, được thiết kế để chạy local bằng Docker và deploy lên Render bằng một Blueprint. Phiên bản MVP ưu tiên cài đặt đơn giản, không yêu cầu Composer và không dùng thư viện bên ngoài.

## Chức năng hiện có

- Đăng ký, đăng nhập và đăng xuất.
- Hash mật khẩu bằng `password_hash()`.
- Giới hạn 5 lần đăng nhập sai trong khoảng 5 phút trên mỗi session.
- Tạo nhiều CV cho các vị trí khác nhau.
- Chỉnh sửa thông tin cá nhân, giới thiệu, học vấn, kinh nghiệm, dự án, kỹ năng, chứng chỉ, ngoại ngữ và liên kết.
- Thêm hoặc xóa mục linh hoạt.
- Xem trước CV trực tiếp khi đang nhập.
- Hai mẫu: `ATS Simple` và `Modern Minimal`.
- Sao chép CV để tùy chỉnh cho vị trí khác.
- Mở bản in A4 và lưu PDF bằng trình duyệt.
- CSRF token cho các yêu cầu POST.
- Prepared statements với PDO.
- Kiểm tra chủ sở hữu trước khi đọc, sửa, sao chép hoặc xóa CV.
- Escape dữ liệu khi hiển thị nhằm giảm nguy cơ XSS.
- Migration tự động khi container khởi động.
- Hỗ trợ PostgreSQL trên cloud; hỗ trợ SQLite khi chạy trực tiếp bằng PHP để học tập.

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

Phiên bản này dùng PHP MVC nhẹ thay vì Laravel để repository có thể chạy ngay mà không cần cài Composer. Khi sản phẩm ổn định, có thể chuyển dần sang Laravel nếu cần bổ sung email verification, queue, admin panel hoặc API phức tạp.

---

# 1. Chạy local bằng Docker Desktop

Đây là cách được khuyến nghị vì môi trường local gần giống Render nhất.

## 1.1. Chuẩn bị

Cài đặt:

- Docker Desktop cho Windows.
- Git, nếu muốn đẩy source code lên GitHub.
- VS Code, nếu muốn chỉnh sửa code.

## 1.2. Chạy dự án

Mở PowerShell tại thư mục dự án và chạy:

```powershell
docker compose up --build
```

Docker sẽ:

1. Tạo PostgreSQL container.
2. Build PHP 8.3 + Apache container.
3. Chờ PostgreSQL sẵn sàng.
4. Tự động tạo bảng `users` và `resumes`.
5. Mở website tại cổng `8080`.

Truy cập:

```text
http://localhost:8080
```

## 1.3. Dừng dự án

```powershell
docker compose down
```

Dữ liệu PostgreSQL vẫn được giữ trong Docker volume.

Muốn xóa cả dữ liệu để thử lại từ đầu:

```powershell
docker compose down -v
```

## 1.4. Xem log

```powershell
docker compose logs -f web
```

---

# 2. Chạy local trực tiếp bằng PHP và SQLite

Cách này phù hợp để đọc code hoặc sửa giao diện nhanh. Cách Docker ở phần trên vẫn là lựa chọn ưu tiên.

## 2.1. Yêu cầu

- PHP 8.2 trở lên.
- Extension: `pdo_sqlite` và `mbstring`.

## 2.2. Khởi chạy

Tạo file `.env` từ file mẫu:

```powershell
Copy-Item .env.example .env
```

Chạy migration:

```powershell
php scripts/migrate.php
```

Chạy PHP development server:

```powershell
php -S localhost:8080 -t public
```

Truy cập:

```text
http://localhost:8080
```

SQLite database được lưu tại:

```text
storage/cloudcv.sqlite
```

Không đưa file SQLite này lên Render. Filesystem của Free Web Service không dùng để lưu dữ liệu lâu dài.

---

# 3. Deploy lên Render

## 3.1. Đẩy source code lên GitHub

Tạo một repository trống trên GitHub. Trong PowerShell, mở thư mục dự án và chạy:

```powershell
git init
git add .
git commit -m "Initial CloudCV Builder MVP"
git branch -M main
git remote add origin <DAN_DIA_CHI_REPOSITORY_GITHUB_VAO_DAY>
git push -u origin main
```

Lưu ý:

- Không commit file `.env`.
- Không đưa password hoặc database URL vào source code.
- File `render.yaml` đã cấu hình sẵn Web Service và PostgreSQL.

## 3.2. Tạo Blueprint trên Render

1. Đăng nhập Render.
2. Mở Dashboard.
3. Chọn **New** → **Blueprint**.
4. Kết nối tài khoản GitHub nếu Render chưa được cấp quyền.
5. Chọn repository vừa tạo.
6. Render đọc file `render.yaml` và hiển thị hai tài nguyên:
   - `cloud-cv-builder`: Web Service chạy Docker.
   - `cloud-cv-db`: Render Postgres.
7. Chọn deploy Blueprint.
8. Theo dõi log cho đến khi xuất hiện thông báo migration hoàn tất và Apache bắt đầu chạy.

Website sẽ có địa chỉ dạng:

```text
https://cloud-cv-builder-xxxx.onrender.com
```

## 3.3. Kiểm tra sau deploy

Mở endpoint health check:

```text
https://<TEN-DICH-VU>.onrender.com/health
```

Kết quả đúng:

```text
ok
```

Sau đó:

1. Mở trang chính.
2. Đăng ký một tài khoản.
3. Tạo một CV thử nghiệm.
4. Thêm dự án và kỹ năng.
5. Bấm **Lưu CV**.
6. Bấm **Mở bản in**.
7. Bấm **In hoặc lưu PDF** và chọn **Save as PDF** trong Chrome hoặc Edge.

---

# 4. File quan trọng

```text
cloud-cv-builder-mvp/
|-- Dockerfile                  # Build PHP + Apache container
|-- docker-compose.yml          # Chạy web và PostgreSQL trên máy local
|-- render.yaml                 # Blueprint tạo Web Service + Render Postgres
|-- public/
|   |-- index.php               # Router chính
|   |-- .htaccess               # Chuyển route về index.php
|   `-- assets/
|       |-- app.css             # Giao diện và CSS in A4
|       `-- editor.js           # Form động và live preview
|-- scripts/
|   `-- migrate.php             # Khởi tạo bảng database
|-- src/
|   |-- Auth.php                # Đăng ký, đăng nhập, session
|   |-- Csrf.php                # CSRF token
|   |-- Database.php            # Kết nối PostgreSQL hoặc SQLite
|   |-- Migrator.php            # Tạo bảng
|   |-- ResumeRepository.php    # CRUD CV và kiểm tra quyền sở hữu
|   |-- bootstrap.php           # Khởi động ứng dụng
|   `-- helpers.php             # Hàm dùng chung
|-- views/
|   |-- auth/                   # Giao diện tài khoản
|   |-- resume/                 # Editor và bản in
|   |-- dashboard.php
|   |-- home.php
|   `-- layout.php
`-- storage/                    # SQLite local nếu không dùng Docker
```

---

# 5. Database MVP

Phiên bản đầu tiên dùng hai bảng để dễ triển khai:

```text
users
  `-- resumes
```

Mỗi CV lưu các mục động trong trường JSON `sections_json`.

Đây là cách phù hợp với MVP vì:

- Cài đặt nhanh.
- Sao chép CV dễ dàng.
- Dễ thêm loại section mới.
- Giảm số lượng migration ban đầu.

Khi cần thống kê chi tiết hoặc quản trị phức tạp, có thể chuẩn hóa thành các bảng `projects`, `educations`, `experiences`, `skills` và `certificates` riêng biệt.

---

# 6. Giới hạn của phiên bản đầu tiên

- Chưa có quên mật khẩu qua email.
- Chưa có xác thực email.
- Chưa upload ảnh đại diện.
- Chưa có liên kết CV công khai.
- Chưa có admin panel.
- Chưa có AI gợi ý nội dung.
- Chưa xuất PDF tự động từ server; người dùng lưu PDF qua hộp thoại in của trình duyệt.
- Chưa có automated test suite.

Các giới hạn này là chủ ý để giữ phạm vi MVP nhỏ, dễ cài và dễ kiểm thử.

---

# 7. Lưu ý khi dùng Render Free

- Free Web Service có thể sleep khi không có request; người dùng có thể phải đăng nhập lại sau khi service restart hoặc sleep.
- Filesystem của web container không được dùng để lưu file lâu dài.
- Dữ liệu CV phải nằm trong Render Postgres.
- Free Render Postgres chỉ phù hợp cho học tập và demo; cần theo dõi thời hạn database và backup dữ liệu cần thiết.
- Không nhập thông tin quá nhạy cảm vào bản demo công khai.

---

# 8. Hướng nâng cấp tiếp theo

Ưu tiên theo thứ tự:

1. Thêm export và import JSON để người dùng tự backup.
2. Thêm liên kết chia sẻ CV với token ngẫu nhiên.
3. Thêm PostgreSQL backup định kỳ khi chuyển sang gói dùng lâu dài.
4. Thêm object storage cho ảnh đại diện.
5. Chuẩn hóa database khi cần thống kê nâng cao.
6. Chuyển sang Laravel hoặc bổ sung framework khi hệ thống lớn hơn.
7. Thêm email verification và chức năng quên mật khẩu.
8. Thêm AI gợi ý mô tả dự án sau cùng.

---

# 6. Nâng cấp v1.1 Safe Release

Phiên bản v1.1 bổ sung backup JSON, import JSON, đổi mật khẩu, activity log và rate limit đăng nhập lưu trong database. Migration chạy tự động khi container khởi động và chỉ tạo thêm bảng mới, không xóa dữ liệu `users` hoặc `resumes` hiện có.

Sau khi thay source code và push lên GitHub, Render sẽ tự deploy lại. Kiểm tra:

```text
/health
/account
/dashboard
```

Chạy local trực tiếp bằng PHP:

```powershell
php scripts/migrate.php
php -S localhost:8080 -t public public/router.php
```

Các bảng mới:

```text
activity_logs
login_attempts
```
