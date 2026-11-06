<?php
require_once 'includes/init.php';
require_admin();

// Ba điều kiện để mở bảng nhập điểm: lớp + môn + học kỳ (nhận từ URL)
$ma_lop = $_GET['ma_lop'] ?? '';
$ma_mh = $_GET['ma_mh'] ?? '';
$hoc_ky = strtoupper(trim($_GET['hoc_ky'] ?? ''));

// ===== XỬ LÝ FORM: lưu điểm cả lớp =====
// Form gửi lên 3 mảng: cc[ma_sv], gk[ma_sv], ck[ma_sv]
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->begin_transaction();
        foreach ($_POST['cc'] as $ma_sv => $cc) {
            $cc = null_neu_rong($cc);
            $gk = null_neu_rong($_POST['gk'][$ma_sv]);
            $ck = null_neu_rong($_POST['ck'][$ma_sv]);

            if ($cc === null && $gk === null && $ck === null) {
                // Để trống cả 3 ô: xóa điểm (nếu có)
                q("DELETE FROM diem WHERE ma_sv = ? AND ma_mh = ? AND hoc_ky = ?", [$ma_sv, $ma_mh, $hoc_ky]);
            } else {
                // Chưa có thì thêm, đã có (trùng ma_sv + ma_mh + hoc_ky) thì cập nhật
                q("INSERT INTO diem (ma_sv, ma_mh, hoc_ky, diem_chuyen_can, diem_giua_ky, diem_cuoi_ky)
                   VALUES (?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE
                       diem_chuyen_can = VALUES(diem_chuyen_can),
                       diem_giua_ky = VALUES(diem_giua_ky),
                       diem_cuoi_ky = VALUES(diem_cuoi_ky)",
                  [$ma_sv, $ma_mh, $hoc_ky, $cc, $gk, $ck]);
            }
        }
        $conn->commit();
        flash('Đã lưu điểm.');
    } catch (mysqli_sql_exception $ex) {
        $conn->rollback();
        flash(thong_bao_loi($ex), 'danger');
    }
    redirect('diem.php?' . http_build_query(['ma_lop' => $ma_lop, 'ma_mh' => $ma_mh, 'hoc_ky' => $hoc_ky]));
}

// ===== LẤY DỮ LIỆU =====
$ds_lop = q_all("SELECT ma_lop, ten_lop FROM lop ORDER BY ma_lop");
$ds_mon = q_all("SELECT ma_mh, ten_mh FROM mon_hoc ORDER BY ten_mh");
$ds_hoc_ky = q_all("SELECT DISTINCT hoc_ky FROM diem ORDER BY SUBSTRING(hoc_ky, 5) DESC, hoc_ky DESC");

// Đủ 3 điều kiện thì lấy sinh viên của lớp kèm điểm môn đó (LEFT JOIN: chưa có điểm vẫn hiện)
$ds_sv = [];
if ($ma_lop && $ma_mh && $hoc_ky) {
    $ds_sv = q_all("SELECT s.ma_sv, s.ho_ten, d.diem_chuyen_can, d.diem_giua_ky, d.diem_cuoi_ky, d.diem_tong
                    FROM sinhvien s
                    LEFT JOIN diem d ON d.ma_sv = s.ma_sv AND d.ma_mh = ? AND d.hoc_ky = ?
                    WHERE s.ma_lop = ?
                    ORDER BY s.ma_sv", [$ma_mh, $hoc_ky, $ma_lop]);
}

$title = 'Điểm';
include 'includes/header.php';
?>

<h1 class="h4 mb-3">Nhập điểm theo lớp</h1>

<!-- Chọn lớp / môn / học kỳ -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Lớp</label>
                <select name="ma_lop" class="form-select" required>
                    <option value="">-- Chọn lớp --</option>
                    <?php foreach ($ds_lop as $l): ?>
                        <option value="<?= e($l['ma_lop']) ?>" <?= $l['ma_lop'] == $ma_lop ? 'selected' : '' ?>><?= e($l['ma_lop'] . ' – ' . $l['ten_lop']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Môn học</label>
                <select name="ma_mh" class="form-select" required>
                    <option value="">-- Chọn môn --</option>
                    <?php foreach ($ds_mon as $m): ?>
                        <option value="<?= e($m['ma_mh']) ?>" <?= $m['ma_mh'] == $ma_mh ? 'selected' : '' ?>><?= e($m['ten_mh']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Học kỳ</label>
                <input name="hoc_ky" class="form-control" list="ds_hoc_ky" required maxlength="10" placeholder="HK1-24-25"
                       pattern="HK[1-3]-\d{2}-\d{2}" title="Dạng HK1-24-25" value="<?= e($hoc_ky) ?>">
                <datalist id="ds_hoc_ky">
                    <?php foreach ($ds_hoc_ky as $h): ?>
                        <option value="<?= e($h['hoc_ky']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-arrow-right-circle"></i> Mở bảng</button>
            </div>
        </form>
    </div>
</div>

<?php if ($ds_sv): ?>
    <!-- Bảng nhập điểm: tên ô dạng cc[ma_sv] để PHP nhận được mảng -->
    <form method="post" class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Sinh viên</th>
                        <th style="width:120px">CC (10%)</th>
                        <th style="width:120px">GK (30%)</th>
                        <th style="width:120px">CK (60%)</th>
                        <th class="text-center">Hệ 10</th>
                        <th class="text-center">Chữ</th>
                        <th class="text-center">Hệ 4</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ds_sv as $s): $ma = e($s['ma_sv']); ?>
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold"><?= e($s['ho_ten']) ?></div>
                            <small class="text-body-secondary"><?= $ma ?></small>
                        </td>
                        <td><input type="number" name="cc[<?= $ma ?>]" class="form-control form-control-sm" min="0" max="10" step="0.01" value="<?= $s['diem_chuyen_can'] ?>"></td>
                        <td><input type="number" name="gk[<?= $ma ?>]" class="form-control form-control-sm" min="0" max="10" step="0.01" value="<?= $s['diem_giua_ky'] ?>"></td>
                        <td><input type="number" name="ck[<?= $ma ?>]" class="form-control form-control-sm" min="0" max="10" step="0.01" value="<?= $s['diem_cuoi_ky'] ?>"></td>
                        <td class="text-center fw-semibold"><?= fmt_diem($s['diem_tong']) ?></td>
                        <td class="text-center"><?= badge_diem_chu($s['diem_tong']) ?></td>
                        <td class="text-center fw-semibold"><?= fmt_he_4($s['diem_tong']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-body d-flex justify-content-between align-items-center">
            <small class="text-body-secondary">Để trống cả 3 ô là xóa điểm của sinh viên đó. Điểm hệ 10 do CSDL tự tính.</small>
            <button class="btn btn-success"><i class="bi bi-save"></i> Lưu tất cả</button>
        </div>
    </form>
<?php elseif ($ma_lop && $ma_mh && $hoc_ky): ?>
    <div class="alert alert-info">Lớp này chưa có sinh viên.</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
