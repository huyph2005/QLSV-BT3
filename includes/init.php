<?php
// File này được nạp ở đầu MỌI trang: mở session, kết nối CSDL, khai báo hàm dùng chung
require_once __DIR__ . '/../config.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();

// Lỗi SQL sẽ ném ra mysqli_sql_exception -> dùng try/catch để báo lỗi dễ hiểu
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

// ---------- Làm việc với CSDL ----------

// Chạy 1 câu SQL có dấu ?. Ví dụ: q("DELETE FROM lop WHERE ma_lop = ?", [$ma_lop]);
function q($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// Lấy nhiều dòng
function q_all($sql, $params = [])
{
    return q($sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Lấy 1 dòng, không có thì trả về null
function q_one($sql, $params = [])
{
    return q($sql, $params)->get_result()->fetch_assoc();
}

// Đổi mã lỗi MySQL thành câu thông báo dễ hiểu
function thong_bao_loi($ex)
{
    switch ($ex->getCode()) {
        case 1062: return 'Dữ liệu bị trùng (mã, email hoặc tên đăng nhập đã tồn tại).';
        case 1451: return 'Không thể xóa vì đang có dữ liệu khác liên quan.';
        case 3819:
        case 4025: return 'Giá trị không hợp lệ, điểm phải từ 0 đến 10.';
        default:   return 'Lỗi CSDL: ' . $ex->getMessage();
    }
}

// ---------- Tiện ích ----------

// In dữ liệu ra HTML an toàn
function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

// Lưu thông báo vào session, trang kế tiếp sẽ in ra (xem header.php)
function flash($msg, $type = 'success')
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

// Ô để trống thì lưu NULL
function null_neu_rong($s)
{
    $s = trim((string) $s);
    return $s === '' ? null : $s;
}

function fmt_date($d)
{
    return $d ? date('d/m/Y', strtotime($d)) : '';
}

// ---------- Quy đổi điểm và tính GPA ----------

// Bảng quy đổi: [điểm hệ 10 tối thiểu, điểm chữ, điểm hệ 4, màu Bootstrap]
const BANG_DIEM = [
    [9.0, 'A+', 4.0, 'success'],
    [8.5, 'A',  3.7, 'success'],
    [8.0, 'B+', 3.5, 'primary'],
    [7.0, 'B',  3.0, 'primary'],
    [6.5, 'C+', 2.5, 'info'],
    [5.5, 'C',  2.0, 'info'],
    [5.0, 'D+', 1.5, 'warning'],
    [4.0, 'D',  1.0, 'warning'],
    [0,   'F',  0.0, 'danger'],
];

// Tra bảng trên. Ví dụ: quy_doi(8.4) trả về [8.0, 'B+', 3.5, 'primary']
function quy_doi($d)
{
    foreach (BANG_DIEM as $muc) {
        if ($d >= $muc[0]) return $muc;
    }
}

const O_TRONG = '<span class="text-body-tertiary">–</span>';

function fmt_diem($d)
{
    return $d === null ? O_TRONG : number_format((float) $d, 2);
}

function fmt_he_4($d)
{
    return $d === null ? O_TRONG : number_format(quy_doi($d)[2], 1);
}

function diem_chu($d)
{
    return $d === null ? '' : quy_doi($d)[1];
}

function badge_diem_chu($d)
{
    return $d === null ? '' : '<span class="badge text-bg-' . quy_doi($d)[3] . '">' . diem_chu($d) . '</span>';
}

function badge_diem_so($d)
{
    return $d === null ? O_TRONG : '<span class="badge text-bg-' . quy_doi($d)[3] . '">' . fmt_diem($d) . '</span>';
}

// GPA hệ 4 = tổng (điểm hệ 4 x tín chỉ) / tổng tín chỉ. Môn thiếu điểm không tính
function tinh_gpa($ds_diem)
{
    $tong_diem = 0;
    $tong_tc = 0;
    foreach ($ds_diem as $d) {
        if ($d['diem_tong'] === null) continue;
        $tong_diem += quy_doi($d['diem_tong'])[2] * $d['so_tin_chi'];
        $tong_tc += $d['so_tin_chi'];
    }
    return $tong_tc ? round($tong_diem / $tong_tc, 2) : null;
}

function xep_loai_gpa($gpa)
{
    if ($gpa === null) return '';
    if ($gpa >= 3.6) return 'Xuất sắc';
    if ($gpa >= 3.2) return 'Giỏi';
    if ($gpa >= 2.5) return 'Khá';
    if ($gpa >= 2.0) return 'Trung bình';
    return 'Yếu';
}

// ---------- Đăng nhập, phân quyền ----------

function require_login()
{
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
    // Sinh viên mới được cấp tài khoản phải đổi mật khẩu trước. Admin thì không
    $u = $_SESSION['user'];
    if ($u['role'] == 'sinhvien' && $u['doi_mat_khau'] && basename($_SERVER['PHP_SELF']) != 'doi_mat_khau.php') {
        redirect('doi_mat_khau.php');
    }
}

function require_admin()
{
    require_login();
    if ($_SESSION['user']['role'] != 'admin') {
        redirect('sinhvien_xem.php');
    }
}
