<?php
require_once 'includes/init.php';
require_admin();

// ===== XỬ LÝ FORM: thêm / đặt lại mật khẩu / xóa =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $hash = password_hash(DEFAULT_PASSWORD, PASSWORD_DEFAULT);

    try {
        if ($action == 'them') {
            // Tài khoản admin không gắn với sinh viên nào
            $ma_sv = $_POST['role'] == 'admin' ? null : null_neu_rong($_POST['ma_sv']);
            q("INSERT INTO taikhoan (username, password, role, ma_sv, doi_mat_khau) VALUES (?, ?, ?, ?, 1)",
              [trim($_POST['username']), $hash, $_POST['role'], $ma_sv]);
            flash('Đã tạo tài khoản, mật khẩu: ' . DEFAULT_PASSWORD);
        } elseif ($action == 'reset') {
            q("UPDATE taikhoan SET password = ?, doi_mat_khau = 1 WHERE id = ?", [$hash, $_POST['id']]);
            flash('Đã đặt lại mật khẩu thành ' . DEFAULT_PASSWORD);
        } elseif ($action == 'xoa' && $_POST['id'] == $_SESSION['user']['id']) {
            flash('Không thể xóa tài khoản đang đăng nhập.', 'warning');
        } elseif ($action == 'xoa') {
            q("DELETE FROM taikhoan WHERE id = ?", [$_POST['id']]);
            flash('Đã xóa tài khoản.');
        }
    } catch (mysqli_sql_exception $ex) {
        flash(thong_bao_loi($ex), 'danger');
    }
    redirect('taikhoan.php');
}

// ===== LẤY DỮ LIỆU =====
$ds_tk = q_all("SELECT t.*, s.ho_ten FROM taikhoan t LEFT JOIN sinhvien s ON s.ma_sv = t.ma_sv ORDER BY t.role, t.username");

// Sinh viên chưa có tài khoản, để chọn khi tạo tài khoản sinh viên
$sv_chua_co_tk = q_all("SELECT s.ma_sv, s.ho_ten FROM sinhvien s
                        LEFT JOIN taikhoan t ON t.ma_sv = s.ma_sv
                        WHERE t.id IS NULL ORDER BY s.ma_sv");

$title = 'Tài khoản';
include 'includes/header.php';
?>

<h1 class="h4 mb-3">Tài khoản <span class="badge text-bg-secondary fs-6 align-middle"><?= count($ds_tk) ?></span></h1>

<div class="row g-3">
    <!-- Form thêm -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold">Thêm tài khoản</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="them">
                    <div class="mb-3">
                        <label class="form-label">Tên đăng nhập</label>
                        <input name="username" class="form-control" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vai trò</label>
                        <select name="role" class="form-select">
                            <option value="admin">Quản trị</option>
                            <option value="sinhvien">Sinh viên</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sinh viên (nếu vai trò là Sinh viên)</label>
                        <select name="ma_sv" class="form-select">
                            <option value="">-- Không chọn --</option>
                            <?php foreach ($sv_chua_co_tk as $s): ?>
                                <option value="<?= e($s['ma_sv']) ?>"><?= e($s['ma_sv'] . ' – ' . $s['ho_ten']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Thêm</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Tên đăng nhập</th><th>Vai trò</th><th>Sinh viên</th><th>Trạng thái</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ds_tk as $t): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($t['username']) ?></td>
                            <td>
                                <?php if ($t['role'] == 'admin'): ?>
                                    <span class="badge text-bg-danger">Quản trị</span>
                                <?php else: ?>
                                    <span class="badge text-bg-primary">Sinh viên</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($t['ho_ten']) ?></td>
                            <td>
                                <?php if ($t['doi_mat_khau'] && $t['role'] != 'admin'): ?>
                                    <span class="badge text-bg-warning">Chờ đổi mật khẩu</span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">Hoạt động</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <form method="post" class="d-inline" onsubmit="return confirm('Đặt lại mật khẩu về mặc định?')">
                                    <input type="hidden" name="action" value="reset">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button class="btn btn-sm btn-outline-warning" title="Đặt lại mật khẩu"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('Xóa tài khoản này?')">
                                    <input type="hidden" name="action" value="xoa">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
