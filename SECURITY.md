# Security notes

CloudCV Builder v1.3 có các biện pháp bảo mật cơ bản:

- Hash mật khẩu bằng `password_hash()` và kiểm tra bằng `password_verify()`.
- Regenerate session ID sau khi đăng nhập và sau khi đổi mật khẩu.
- Tăng `session_version` khi đổi hoặc đặt lại mật khẩu để phiên đăng nhập cũ tự mất hiệu lực.
- Cookie session `HttpOnly`, `SameSite=Lax` và `Secure` khi request sử dụng HTTPS.
- CSRF token cho các route POST, bao gồm autosave, tài khoản và thao tác chia sẻ.
- PDO prepared statements.
- Escape HTML khi hiển thị dữ liệu.
- Lọc URL trước khi đưa vào liên kết.
- Kiểm tra chủ sở hữu CV trên các route xem, sửa, autosave, sao chép, xóa, in, backup và quản lý chia sẻ.
- Rate limit đăng nhập lưu trong database theo email hash và IP; tối đa 5 lần sai trong 15 phút.
- Rate limit yêu cầu email xác minh và đặt lại mật khẩu theo subject hash và IP.
- Activity log không lưu mật khẩu, raw token hoặc nội dung CV.
- Import JSON giới hạn 512 KB, kiểm tra phần mở rộng, schema và lọc dữ liệu trước khi lưu.
- Link chia sẻ dùng token sinh bởi `random_bytes(32)`. Database chỉ lưu SHA-256 hash của token.
- Token xác minh email và reset mật khẩu cũng sinh bởi `random_bytes(32)`, chỉ lưu SHA-256 hash, dùng một lần và có thời hạn.
- Route reset password và verify email thêm `Referrer-Policy: no-referrer` để giảm rò rỉ token qua referrer.
- Route công khai chỉ truy cập được khi token hợp lệ, chưa bị thu hồi và chưa hết hạn.
- Trang chia sẻ công khai thêm chỉ thị `noindex`, `nofollow`, `noarchive` cho công cụ tìm kiếm.
- API key Resend được đọc từ environment variable, không đặt trong source code.
- Không có chức năng upload file trong phiên bản hiện tại.

## Cấu hình email production

Không bật:

```text
REQUIRE_EMAIL_VERIFICATION=true
```

trước khi đã cấu hình và kiểm thử:

```text
MAIL_MODE=resend
RESEND_API_KEY
MAIL_FROM
APP_URL
```

Không commit `.env`, API key hoặc database URL vào GitHub.

## Lưu ý về link chia sẻ

Link chia sẻ là một capability URL: bất kỳ ai có link đều có thể xem nội dung CV. Người dùng chỉ nên gửi link cho người nhận phù hợp và thu hồi link khi không còn cần thiết. Không đăng link lên mạng xã hội nếu CV chứa số điện thoại, email hoặc thông tin cá nhân không muốn công khai rộng rãi.

## Lưu ý về xóa tài khoản

Xóa tài khoản loại bỏ user, CV, link chia sẻ, token và activity log gắn với user. Tính năng này không thay thế việc xóa dữ liệu khỏi file backup database đã tạo trước đó. Cần quản lý vòng đời backup riêng nếu triển khai thành dịch vụ thực tế.

## Trước khi dùng như một dịch vụ lớn

- Thêm test tự động cho authorization, token và account deletion.
- Thiết lập backup database định kỳ và chính sách retention.
- Bổ sung Content Security Policy sau khi chuyển inline script sang file riêng hoặc dùng nonce.
- Chuyển sang session store bền vững nếu scale nhiều instance.
- Bổ sung email change flow có xác minh lại địa chỉ mới.
- Thực hiện kiểm thử bảo mật định kỳ.
