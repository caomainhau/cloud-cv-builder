# CloudCV Builder v1.3 Account Recovery Release

CloudCV Builder là website tạo CV nhiều người dùng, chạy local bằng Docker và deploy lên Render bằng Blueprint. Repository dùng PHP MVC nhẹ, PostgreSQL trên cloud và SQLite khi chạy PHP trực tiếp để học tập.

## Chức năng chính

- Đăng ký, đăng nhập, đăng xuất và đổi mật khẩu.
- Xác minh email tùy chọn cho tài khoản đăng ký mới.
- Quên mật khẩu và đặt lại mật khẩu bằng token một lần, hết hạn sau 60 phút.
- Gửi email thật bằng Resend API hoặc ghi email giả lập vào `storage/mail.log` khi kiểm thử local.
- Rate limit đăng nhập theo email hash và IP.
- Tự vô hiệu hóa phiên đăng nhập cũ sau khi đổi hoặc đặt lại mật khẩu bằng `session_version`.
- Rate limit yêu cầu xác minh email và quên mật khẩu.
- Cập nhật họ tên hồ sơ, tải toàn bộ dữ liệu cá nhân JSON và xóa tài khoản.
- Tạo, sửa, sao chép và xóa nhiều CV.
- Thông tin cá nhân, giới thiệu, học vấn, kinh nghiệm, dự án, kỹ năng, chứng chỉ, ngoại ngữ và liên kết.
- Hai mẫu: `ATS Simple` và `Modern Minimal`.
- Live preview, autosave và CSS in A4.
- Xuất PDF bằng trình duyệt.
- Xuất và nhập JSON backup cho từng CV.
- Activity log cho các hành động quan trọng.
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
    |                 \
    | PDO              \ HTTPS API
    v                    v
Render Postgres       Resend Email API
```

## Chạy local bằng Docker Desktop

```powershell
docker compose up --build
```

Mở:

```text
http://localhost:8080
```

`docker-compose.yml` bật sẵn xác minh email và chế độ log local:

```text
MAIL_MODE=log
REQUIRE_EMAIL_VERIFICATION=true
```

Email giả lập nằm tại:

```text
http://localhost:8080/dev/mailbox
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

Muốn kiểm thử xác minh email, sửa `.env`:

```text
MAIL_MODE=log
REQUIRE_EMAIL_VERIFICATION=true
```

SQLite database local được lưu tại:

```text
storage/cloudcv.sqlite
```

Không đưa file này lên Render.

## Deploy lên Render

Source code có:

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

Render mặc định giữ:

```text
MAIL_MODE=disabled
REQUIRE_EMAIL_VERIFICATION=false
```

Nhờ đó, deploy v1.3 không khóa tài khoản đang sử dụng. Để gửi email thật và bật xác minh bắt buộc, đọc:

```text
RESEND_SETUP.md
```

## Nâng cấp từ v1.2

Đọc:

```text
HUONG_DAN_NANG_CAP_V1.3.md
TEST_CHECKLIST_V1.3.md
```

Migration v1.3 chỉ thêm cột và bảng mới, không xóa dữ liệu hiện có.

## Database

```text
users
  |-- resumes
  |     `-- resume_shares
  |-- activity_logs
  |-- email_verification_tokens
  `-- password_reset_tokens

login_attempts
mail_request_attempts
```

Các nhóm nội dung CV được lưu trong `resumes.sections_json`. Mỗi dòng có thể chứa `_visible` để ẩn hoặc hiện mà không xóa dữ liệu.

## File quan trọng

```text
public/index.php                  Router chính
public/assets/editor.js           Live preview, autosave, reorder, ẩn/hiện
src/Auth.php                      Đăng nhập, xác minh email, reset password
src/AccountTokenRepository.php    Token một lần cho email và mật khẩu
src/Mailer.php                    Email log local hoặc Resend API
src/ActionThrottle.php            Rate limit yêu cầu gửi email
src/AccountRepository.php         Hồ sơ, export dữ liệu và xóa tài khoản
src/ResumeRepository.php          CRUD CV và kiểm tra quyền sở hữu
src/ResumeShareRepository.php     Link chia sẻ công khai
src/Migrator.php                  Migration PostgreSQL và SQLite
SECURITY.md                       Ghi chú bảo mật
```

## Lưu ý riêng tư

Link chia sẻ cho phép người có link xem CV mà không cần đăng nhập. Hãy thu hồi link khi không còn sử dụng và tránh công khai link rộng rãi nếu CV chứa thông tin cá nhân.

File export toàn bộ dữ liệu cá nhân cũng chứa nội dung CV và activity log. Chỉ lưu file ở nơi an toàn.
