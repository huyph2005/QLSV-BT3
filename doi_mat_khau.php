<?php
require_once 'includes/init.php';
require_login();

$u = $_SESSION['user'];
$loi = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tk = q_one("SELECT password FROM taikhoan WHERE id = ?", [$u['id']]);

    if (!password_verify($_POST['old'], $tk['password'])) {
        $loi = 'Mật khẩu hiện tại không đúng.';
    } elseif ($_POST['new'] !== $_POST['re']) {
        $loi = 'Nhập lại mật khẩu không khớp.';
    } else {
        q("UPDATE taikhoan SET password = ?, doi_mat_khau = 0 WHERE id = ?", [password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]);
        $_SESSION['user']['doi_mat_khau'] = 0;
        flash('Đổi mật khẩu thành công.');
        redirect('index.php');
    }
}

$title = 'Đổi mật khẩu';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-body fw-semibold"><i class="bi bi-shield-lock me-1"></i> Đổi mật khẩu</div>
            <div class="card-body">
                <?php if ($u['role'] == 'sinhvien' && $u['doi_mat_khau']): ?>
                    <div class="alert alert-warning py-2 small">Tài khoản mới được cấp mật khẩu. Vui lòng đổi mật khẩu để tiếp tục.</div>
                <?php endif; ?>
                <?php if ($loi): ?>
                    <div class="alert alert-danger py-2 small"><?= $loi ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" class="form-control" name="old" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" name="new" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhập lại mật khẩu mới</label>
                        <input type="password" class="form-control" name="re" minlength="6" required>
                    </div>
                    <button class="btn btn-primary w-100">Lưu mật khẩu</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
