<?php
require_once 'includes/init.php';

// Đã đăng nhập thì vào thẳng trang chủ
if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$loi = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tk = q_one("SELECT t.*, s.ho_ten FROM taikhoan t LEFT JOIN sinhvien s ON s.ma_sv = t.ma_sv WHERE t.username = ?",
                [trim($_POST['username'])]);

    // password_verify so mật khẩu nhập vào với chuỗi đã băm trong CSDL
    if ($tk && password_verify($_POST['password'], $tk['password'])) {
        unset($tk['password']);
        $_SESSION['user'] = $tk;
        redirect('index.php');
    }
    $loi = 'Sai tên đăng nhập hoặc mật khẩu.';
}

$title = 'Đăng nhập';
include 'includes/header.php';
?>

<style>
    /* Ảnh khuôn viên làm nền, phủ lớp xanh đậm để khung đăng nhập dễ đọc */
    .nen-dang-nhap {
        position: fixed;
        inset: -10px;
        z-index: -1;
        background: linear-gradient(rgba(10, 37, 84, .55), rgba(10, 37, 84, .7)),
                    url("assets/img/ptit-khuon-vien.jpg") center / cover no-repeat;
        filter: blur(2px);
    }
</style>
<div class="nen-dang-nhap"></div>

<div class="row justify-content-center align-items-center" style="min-height:70vh">
    <div class="col-sm-9 col-md-6 col-lg-4 col-xl-3">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <h1 class="h5 text-center mb-4">Đăng nhập hệ thống</h1>

                <?php if ($loi): ?>
                    <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= $loi ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Tài khoản</label>
                        <input class="form-control" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập</button>
                </form>
            </div>
        </div>
        <p class="text-center text-white small mt-3">Sinh viên đăng nhập bằng mã sinh viên.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
