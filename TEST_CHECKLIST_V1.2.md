# Checklist kiểm thử CloudCV Builder v1.2

## A. Dữ liệu cũ

- [ ] Đăng nhập bằng tài khoản đã tạo ở v1.1.
- [ ] Mở CV cũ và kiểm tra nội dung vẫn còn.
- [ ] Mở trang `/account` và kiểm tra activity log vẫn hiển thị.

## B. Autosave

- [ ] Mở editor và thay đổi họ tên hoặc giới thiệu.
- [ ] Chờ khoảng 1–2 giây và kiểm tra trạng thái chuyển thành `Đã lưu tự động`.
- [ ] Refresh trang và kiểm tra nội dung mới vẫn còn.
- [ ] Thử mất mạng hoặc dừng server local trong lúc nhập để kiểm tra thông báo lỗi lưu tự động.
- [ ] Khi còn dữ liệu chưa lưu, đóng tab và kiểm tra trình duyệt cảnh báo.

## C. Sắp xếp và ẩn nội dung

- [ ] Thêm ít nhất ba dự án.
- [ ] Kéo dự án thứ ba lên đầu và kiểm tra live preview thay đổi thứ tự.
- [ ] Dùng nút `↑` và `↓` để đổi thứ tự.
- [ ] Bấm `Ẩn` một dự án và kiểm tra dữ liệu vẫn còn trong editor nhưng biến mất khỏi preview.
- [ ] Bấm `Hiện` để khôi phục.
- [ ] Mở bản in và kiểm tra thứ tự, trạng thái ẩn/hiện đúng như preview.

## D. Link chia sẻ

- [ ] Từ dashboard, bấm `Chia sẻ` trên một CV.
- [ ] Tạo link 7 ngày và copy link ngay trên trang.
- [ ] Mở link trong cửa sổ ẩn danh, không đăng nhập.
- [ ] Kiểm tra chỉ hiển thị CV và nút in; không có nút chỉnh sửa.
- [ ] Quay lại trang quản lý chia sẻ và kiểm tra số lượt xem tăng.
- [ ] Tạo link mới; kiểm tra link cũ không còn hoạt động.
- [ ] Thu hồi link mới; kiểm tra link mới cũng không còn hoạt động.

## E. Phân quyền

- [ ] Tạo tài khoản B.
- [ ] Đăng nhập tài khoản B và thử đổi ID trên URL `/resume/edit?id=<ID_CUA_A>`.
- [ ] Thử URL `/resume/share?id=<ID_CUA_A>`.
- [ ] Thử autosave bằng ID CV của tài khoản A.
- [ ] Kết quả đúng: dữ liệu tài khoản A không hiển thị và không bị sửa.

## F. Backup JSON

- [ ] Xuất JSON một CV có dòng đang ẩn.
- [ ] Nhập lại JSON.
- [ ] Kiểm tra nội dung và trạng thái ẩn/hiện được giữ lại.
