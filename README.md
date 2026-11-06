# Quản lý sinh viên (PHP thuần + Bootstrap 5 + MySQL)

## Cài đặt
1. Clone vào thư mục của XAMPP và tạo file cấu hình:
   ```powershell
   cd C:\xampp\htdocs
   git clone https://github.com/huyph2005/QLSV-BT3.git qlsv
   cd qlsv
   copy config.example.php config.php
   ```
2. Sửa `config.php`: `DB_PORT` (file mẫu để `3307`, MySQL của XAMPP mặc định là `3306`), `DB_USER`, `DB_PASS`.
3. Bật Apache và MySQL, rồi import `database.sql` bằng phpMyAdmin (tạo CSDL `qlsv_db` + dữ liệu mẫu).
   **Lưu ý:** file này xóa CSDL `qlsv_db` cũ trước khi tạo lại, nên dữ liệu cũ trong đó sẽ mất.
4. Mở http://localhost/qlsv và đăng nhập bằng tài khoản mẫu bên dưới.
5. (Tùy chọn) Gửi mail: điền `SMTP_USER`, `SMTP_PASS` trong `config.php`.

`config.php` không được đưa lên git vì chứa mật khẩu, nên mỗi máy tự tạo từ `config.example.php`.

## Tài khoản mẫu
| Tài khoản | Mật khẩu | Ghi chú |
|---|---|---|
| admin | admin123 | Quản trị |
| Mọi sinh viên: tên đăng nhập là mã SV, ví dụ B23DCCN001 | 123456 | Bắt buộc đổi mật khẩu lần đầu |

## Chức năng
- **Admin**: tổng quan; thêm, sửa, xóa lớp, môn học, sinh viên; tìm kiếm sinh viên bằng AJAX;
  nhập điểm theo lớp; quản lý tài khoản; gửi bảng điểm qua email.
- **Sinh viên**: xem hồ sơ, bảng điểm (hệ 10, chữ, hệ 4), GPA tích lũy; cập nhật số điện thoại, địa chỉ; đổi mật khẩu.
- Font: Be Vietnam Pro (sans-serif).
- `diem_tong` là cột tự sinh: 10% chuyên cần + 30% giữa kỳ + 60% cuối kỳ.

## Cấu trúc
Mỗi module một file. File nào cũng có 3 khối: xử lý form, lấy dữ liệu, in giao diện.
```
config.example.php    file cấu hình mẫu, sao chép thành config.php
config.php            cấu hình CSDL, SMTP (không đưa lên git)
includes/init.php     session, kết nối CSDL, hàm q(), e(), flash(), quy đổi điểm, kiểm tra đăng nhập
includes/header.php, footer.php   menu và phần chân trang
includes/mailer.php   gửi mail SMTP
login, logout, doi_mat_khau, index
lop, monhoc, taikhoan  thêm, sửa, xóa ngay trên 1 trang
sinhvien              danh sách, form, xóa và AJAX (?ajax=1)
sinhvien_xem          hồ sơ + bảng điểm
diem                  nhập điểm theo lớp
email                 gửi bảng điểm + nhật ký
```
