<?php
require_once 'includes/init.php';
require_admin();

// ===== XỬ LÝ FORM: thêm / sửa / xóa =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $ma_mh = trim($_POST['ma_mh']);

    try {
        if ($action == 'them') {
            q("INSERT INTO mon_hoc (ma_mh, ten_mh, so_tin_chi) VALUES (?, ?, ?)", [$ma_mh, trim($_POST['ten_mh']), $_POST['so_tin_chi']]);
            flash("Đã thêm môn $ma_mh.");
        } elseif ($action == 'sua') {
            q("UPDATE mon_hoc SET ten_mh = ?, so_tin_chi = ? WHERE ma_mh = ?", [trim($_POST['ten_mh']), $_POST['so_tin_chi'], $ma_mh]);
            flash("Đã cập nhật môn $ma_mh.");
        } elseif ($action == 'xoa') {
            // Khóa ngoại RESTRICT: môn đã có điểm sẽ báo lỗi 1451
            q("DELETE FROM mon_hoc WHERE ma_mh = ?", [$ma_mh]);
            flash("Đã xóa môn $ma_mh.");
        }
    } catch (mysqli_sql_exception $ex) {
        flash(thong_bao_loi($ex), 'danger');
    }
    redirect('monhoc.php');
}

// ===== LẤY DỮ LIỆU ĐỂ HIỂN THỊ =====
// Đang sửa môn nào? (monhoc.php?sua=INT1313)
$sua = null;
if (isset($_GET['sua'])) {
    $sua = q_one("SELECT * FROM mon_hoc WHERE ma_mh = ?", [$_GET['sua']]);
}

$ds_mon = q_all("SELECT m.*, COUNT(d.id) AS so_luot
                 FROM mon_hoc m LEFT JOIN diem d ON d.ma_mh = m.ma_mh
                 GROUP BY m.ma_mh, m.ten_mh, m.so_tin_chi
                 ORDER BY m.ma_mh");

$title = 'Môn học';
include 'includes/header.php';
?>

<h1 class="h4 mb-3">Danh sách môn học</h1>

<div class="row g-3">
    <!-- Form thêm / sửa -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><?= $sua ? 'Sửa môn ' . e($sua['ma_mh']) : 'Thêm môn mới' ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?= $sua ? 'sua' : 'them' ?>">
                    <div class="mb-3">
                        <label class="form-label">Mã môn</label>
                        <input name="ma_mh" class="form-control" maxlength="10" required value="<?= e($sua['ma_mh'] ?? '') ?>" <?= $sua ? 'readonly' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tên môn học</label>
                        <input name="ten_mh" class="form-control" maxlength="100" required value="<?= e($sua['ten_mh'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số tín chỉ</label>
                        <input type="number" name="so_tin_chi" class="form-control" min="1" max="10" required value="<?= e($sua['so_tin_chi'] ?? 3) ?>">
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu</button>
                    <?php if ($sua): ?><a href="monhoc.php" class="btn btn-outline-secondary">Hủy</a><?php endif; ?>
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
                        <tr><th>Mã môn</th><th>Tên môn học</th><th class="text-center">Số TC</th><th class="text-center">Lượt học</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ds_mon as $m): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($m['ma_mh']) ?></td>
                            <td><?= e($m['ten_mh']) ?></td>
                            <td class="text-center"><?= $m['so_tin_chi'] ?></td>
                            <td class="text-center"><?= $m['so_luot'] ?></td>
                            <td class="text-end text-nowrap">
                                <a href="monhoc.php?sua=<?= urlencode($m['ma_mh']) ?>" class="btn btn-sm btn-outline-primary" title="Sửa"><i class="bi bi-pencil"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Xóa môn học này?')">
                                    <input type="hidden" name="action" value="xoa">
                                    <input type="hidden" name="ma_mh" value="<?= e($m['ma_mh']) ?>">
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
