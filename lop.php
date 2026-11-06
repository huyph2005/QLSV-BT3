<?php
require_once 'includes/init.php';
require_admin();

// ===== XỬ LÝ FORM: thêm / sửa / xóa =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $ma_lop = trim($_POST['ma_lop']);

    try {
        if ($action == 'them') {
            q("INSERT INTO lop (ma_lop, ten_lop, nganh, nam_nhap_hoc) VALUES (?, ?, ?, ?)",
              [$ma_lop, trim($_POST['ten_lop']), trim($_POST['nganh']), $_POST['nam_nhap_hoc']]);
            flash("Đã thêm lớp $ma_lop.");
        } elseif ($action == 'sua') {
            q("UPDATE lop SET ten_lop = ?, nganh = ?, nam_nhap_hoc = ? WHERE ma_lop = ?",
              [trim($_POST['ten_lop']), trim($_POST['nganh']), $_POST['nam_nhap_hoc'], $ma_lop]);
            flash("Đã cập nhật lớp $ma_lop.");
        } elseif ($action == 'xoa') {
            // Khóa ngoại RESTRICT: lớp còn sinh viên sẽ báo lỗi 1451
            q("DELETE FROM lop WHERE ma_lop = ?", [$ma_lop]);
            flash("Đã xóa lớp $ma_lop.");
        }
    } catch (mysqli_sql_exception $ex) {
        flash(thong_bao_loi($ex), 'danger');
    }
    redirect('lop.php');
}

// ===== LẤY DỮ LIỆU ĐỂ HIỂN THỊ =====
// Đang sửa lớp nào? (lop.php?sua=D23CQCN01)
$sua = null;
if (isset($_GET['sua'])) {
    $sua = q_one("SELECT * FROM lop WHERE ma_lop = ?", [$_GET['sua']]);
}

$ds_lop = q_all("SELECT l.*, COUNT(s.ma_sv) AS si_so
                 FROM lop l LEFT JOIN sinhvien s ON s.ma_lop = l.ma_lop
                 GROUP BY l.ma_lop, l.ten_lop, l.nganh, l.nam_nhap_hoc
                 ORDER BY l.ma_lop");

$title = 'Lớp';
include 'includes/header.php';
?>

<h1 class="h4 mb-3">Danh sách lớp</h1>

<div class="row g-3">
    <!-- Form thêm / sửa -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><?= $sua ? 'Sửa lớp ' . e($sua['ma_lop']) : 'Thêm lớp mới' ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?= $sua ? 'sua' : 'them' ?>">
                    <div class="mb-3">
                        <label class="form-label">Mã lớp</label>
                        <input name="ma_lop" class="form-control" maxlength="10" required value="<?= e($sua['ma_lop'] ?? '') ?>" <?= $sua ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tên lớp</label>
                        <input name="ten_lop" class="form-control" maxlength="100" required value="<?= e($sua['ten_lop'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ngành</label>
                        <input name="nganh" class="form-control" maxlength="100" required value="<?= e($sua['nganh'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Năm nhập học</label>
                        <input type="number" name="nam_nhap_hoc" class="form-control" required value="<?= e($sua['nam_nhap_hoc'] ?? date('Y')) ?>">
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu</button>
                    <?php if ($sua): ?><a href="lop.php" class="btn btn-outline-secondary">Hủy</a><?php endif; ?>
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
                        <tr><th>Mã lớp</th><th>Tên lớp</th><th>Ngành</th><th class="text-center">Năm NH</th><th class="text-center">Sĩ số</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ds_lop as $l): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($l['ma_lop']) ?></td>
                            <td><?= e($l['ten_lop']) ?></td>
                            <td><?= e($l['nganh']) ?></td>
                            <td class="text-center"><?= $l['nam_nhap_hoc'] ?></td>
                            <td class="text-center"><a href="sinhvien.php?lop=<?= urlencode($l['ma_lop']) ?>" class="badge text-bg-secondary text-decoration-none"><?= $l['si_so'] ?> SV</a></td>
                            <td class="text-end text-nowrap">
                                <a href="lop.php?sua=<?= urlencode($l['ma_lop']) ?>" class="btn btn-sm btn-outline-primary" title="Sửa"><i class="bi bi-pencil"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Xóa lớp này?')">
                                    <input type="hidden" name="action" value="xoa">
                                    <input type="hidden" name="ma_lop" value="<?= e($l['ma_lop']) ?>">
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
