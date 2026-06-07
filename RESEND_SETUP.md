# Cấu hình email thật bằng Resend trên Render

CloudCV Builder v1.3 dùng hai chế độ email:

```text
MAIL_MODE=log       Chỉ ghi email vào storage/mail.log để kiểm thử local
MAIL_MODE=resend    Gửi email thật qua Resend API
```

## 1. Kiểm thử local trước

`docker-compose.yml` đã bật:

```text
MAIL_MODE=log
REQUIRE_EMAIL_VERIFICATION=true
```

Chạy:

```powershell
docker compose up --build
```

Đăng ký tài khoản mới, sau đó mở:

```text
http://localhost:8080/dev/mailbox
```

Bấm link xác minh trong email giả lập. Thử thêm luồng **Quên mật khẩu**.

## 2. Chuẩn bị Resend

1. Tạo tài khoản Resend.
2. Tạo API key.
3. Xác minh domain hoặc subdomain dùng để gửi email, ví dụ:

```text
mail.example.com
```

4. Chọn địa chỉ gửi, ví dụ:

```text
CloudCV <no-reply@mail.example.com>
```

Không commit API key vào GitHub.

## 3. Thêm environment variables trên Render

Mở:

```text
Render Dashboard
→ Web Service cloud-cv-builder
→ Environment
```

Thêm hoặc sửa:

```text
APP_URL=https://<TEN-DICH-VU>.onrender.com
MAIL_MODE=resend
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxx
MAIL_FROM=CloudCV <no-reply@mail.example.com>
REQUIRE_EMAIL_VERIFICATION=true
```

Thứ tự an toàn:

1. Thêm `APP_URL`, `RESEND_API_KEY`, `MAIL_FROM`.
2. Đặt `MAIL_MODE=resend`.
3. Deploy lại và thử **Quên mật khẩu** bằng một tài khoản kiểm thử.
4. Chỉ sau khi email thật đến hộp thư, đặt `REQUIRE_EMAIL_VERIFICATION=true`.

## 4. Tài khoản cũ

Migration v1.3 tự đánh dấu các tài khoản đã tồn tại trước khi nâng cấp là đã xác minh. Chỉ tài khoản đăng ký sau khi bật `REQUIRE_EMAIL_VERIFICATION=true` mới cần mở email xác minh.

## 5. Khi email không đến

Kiểm tra:

```text
Render logs
MAIL_MODE=resend
RESEND_API_KEY
MAIL_FROM
APP_URL
Domain đã verified trên Resend
Hộp thư Spam/Junk
```

Không chụp hoặc gửi ảnh có API key.
