# Security notes

CloudCV Builder v1.2 đã có các biện pháp bảo mật cơ bản:

- Hash mật khẩu bằng `password_hash()` và kiểm tra bằng `password_verify()`.
- Regenerate session ID sau khi đăng nhập và sau khi đổi mật khẩu.
- Cookie session `HttpOnly`, `SameSite=Lax` và `Secure` khi request sử dụng HTTPS.
- CSRF token cho các route POST, bao gồm autosave và thao tác chia sẻ.
- PDO prepared statements.
- Escape HTML khi hiển thị dữ liệu.
- Lọc URL trước khi đưa vào liên kết.
- Kiểm tra chủ sở hữu CV trên các route xem, sửa, autosave, sao chép, xóa, in, backup và quản lý chia sẻ.
- Rate limit đăng nhập lưu trong database theo email hash và IP; tối đa 5 lần sai trong 15 phút.
- Activity log không lưu mật khẩu hoặc nội dung CV.
- Import JSON giới hạn 512 KB, kiểm tra phần mở rộng, schema và lọc dữ liệu trước khi lưu.
- Link chia sẻ dùng token sinh bởi `random_bytes(32)`. Database chỉ lưu SHA-256 hash của token.
- Route công khai chỉ truy cập được khi token hợp lệ, chưa bị thu hồi và chưa hết hạn.
- Trang chia sẻ công khai thêm chỉ thị `noindex`, `nofollow`, `noarchive` cho công cụ tìm kiếm.
- Không có chức năng upload file trong phiên bản hiện tại.

## Lưu ý về link chia sẻ

Link chia sẻ là một capability URL: bất kỳ ai có link đều có thể xem nội dung CV. Người dùng chỉ nên gửi link cho người nhận phù hợp và thu hồi link khi không còn cần thiết. Không đăng link lên mạng xã hội nếu CV chứa số điện thoại, email hoặc thông tin cá nhân không muốn công khai rộng rãi.

## Trước khi dùng như một dịch vụ lớn

- Bổ sung email verification và password reset.
- Thêm test tự động cho authorization và share token.
- Thiết lập backup database định kỳ.
- Bổ sung Content Security Policy sau khi rà soát toàn bộ inline script.
- Chuyển sang session store bền vững nếu scale nhiều instance.
- Thực hiện kiểm thử bảo mật định kỳ.
