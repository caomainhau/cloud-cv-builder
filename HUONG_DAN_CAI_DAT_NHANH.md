# Hướng dẫn cài nhanh CloudCV Builder MVP

## A. Chạy thử trên máy Windows bằng Docker Desktop

1. Giải nén source code.
2. Mở thư mục vừa giải nén.
3. Nhấn chuột phải tại vùng trống và chọn **Open in Terminal** hoặc mở PowerShell rồi chuyển đến thư mục dự án.
4. Chạy:

```powershell
docker compose up --build
```

5. Khi log hiển thị `Database migration completed.`, mở trình duyệt:

```text
http://localhost:8080
```

6. Đăng ký tài khoản, tạo CV và thử lưu dữ liệu.

Dừng website:

```powershell
docker compose down
```

## B. Đưa website lên GitHub

1. Tạo repository trống trên GitHub, không khởi tạo thêm README hoặc `.gitignore`.
2. Mở PowerShell tại thư mục dự án.
3. Chạy lần lượt:

```powershell
git init
git add .
git commit -m "Initial CloudCV Builder MVP"
git branch -M main
git remote add origin <DIA_CHI_REPOSITORY_GITHUB>
git push -u origin main
```

## C. Deploy lên Render

1. Đăng nhập Render.
2. Chọn **New** → **Blueprint**.
3. Kết nối GitHub và chọn repository vừa tạo.
4. Render tự đọc file `render.yaml`.
5. Kiểm tra có hai tài nguyên:
   - Web Service: `cloud-cv-builder`
   - Postgres: `cloud-cv-db`
6. Chọn deploy.
7. Khi trạng thái chuyển sang live, mở URL do Render cấp.
8. Kiểm tra thêm đường dẫn `/health`; kết quả đúng là `ok`.

## D. Lưu CV thành PDF

1. Mở một CV.
2. Bấm **Lưu CV**.
3. Bấm **Mở bản in**.
4. Bấm **In hoặc lưu PDF**.
5. Trong Chrome hoặc Edge, chọn máy in **Save as PDF**.

## E. Lưu ý với Render Free

- Website có thể sleep sau một thời gian không có người truy cập.
- Lần mở đầu tiên sau khi sleep có thể chậm hơn bình thường.
- Không lưu ảnh hoặc database SQLite bên trong Web Service.
- CV trên cloud được lưu trong Render Postgres.
- Free Render Postgres chỉ phù hợp để học và demo; cần chú ý thời hạn database và chủ động backup dữ liệu cần giữ.
