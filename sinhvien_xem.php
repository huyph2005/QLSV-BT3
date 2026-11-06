<?php
// Hồ sơ + bảng điểm của 1 sinh viên.
//  - Sinh viên: xem của chính mình, được sửa số điện thoại và địa chỉ.
//  - Admin: xem bất kỳ sinh viên nào qua sinhvien_xem.php?ma_sv=...
require_once 'includes/init.php';
require_login();

$la_admin = $_SESSION['user']['role'] == 'admin';
$ma_sv = $la_admin ? ($_GET['ma_sv'] ?? '') : $_SESSION['user']['ma_sv'];

// ===== 1. SINH VIÊN CẬP NHẬT LIÊN HỆ =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$la_admin) {
    q("UPDATE sinhvien SET so_dt = ?, dia_chi = ? WHERE ma_sv = ?",
      [null_neu_rong($_POST['so_dt']), null_neu_rong($_POST['dia_chi']), $ma_sv]);
    flash('Đã cập nhật thông tin liên hệ.');
    redirect('sinhvien_xem.php');
}

// ===== 2. LẤY DỮ LIỆU =====
$sv = q_one("SELECT s.*, l.ten_lop, l.nganh, l.nam_nhap_hoc FROM sinhvien s JOIN lop l ON l.ma_lop = s.ma_lop WHERE s.ma_sv = ?", [$ma_sv]);
if (!$sv) {
    flash('Không tìm thấy sinh viên.', 'danger');
    redirect($la_admin ? 'sinhvien.php' : 'logout.php');
}

// Lấy điểm, học kỳ cũ xếp trước
$ds_diem = q_all("SELECT d.*, m.ten_mh, m.so_tin_chi
                  FROM diem d JOIN mon_hoc m ON m.ma_mh = d.ma_mh
                  WHERE d.ma_sv = ?
                  ORDER BY SUBSTRING(d.hoc_ky, 5), d.hoc_ky, m.ten_mh", [$ma_sv]);

$theo_ky = [];    // Điểm chia theo từng học kỳ
$tich_luy = [];   // Các môn được tính tích lũy
foreach ($ds_diem as $d) {
    $theo_ky[$d['hoc_ky']][] = $d;

    // Chỉ tích lũy môn đạt (>= 4). Học lại nhiều lần thì lấy điểm cao nhất
    if ($d['diem_tong'] !== null && $d['diem_tong'] >= 4) {
        $mh = $d['ma_mh'];
        if (!isset($tich_luy[$mh]) || $d['diem_tong'] > $tich_luy[$mh]['diem_tong']) {
            $tich_luy[$mh] = $d;
        }
    }
}
$gpa = tinh_gpa($tich_luy);
$tc_tich_luy = array_sum(array_column($tich_luy, 'so_tin_chi'));

// Chữ cái đầu của tên để làm ảnh đại diện. Ví dụ: Phạm Gia Huy -> H
$cac_tu = explode(' ', $sv['ho_ten']);
$chu_cai = mb_strtoupper(mb_substr(end($cac_tu), 0, 1));

$title = $sv['ho_ten'];
include 'includes/header.php';
?>

<div class="row g-3">
    <!-- Cột trái: hồ sơ -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center pt-4">
                <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fs-2 fw-semibold mb-2" style="width:80px;height:80px"><?= e($chu_cai) ?></div>
                <h1 class="h5 mb-0"><?= e($sv['ho_ten']) ?></h1>
                <div class="text-body-secondary"><?= e($sv['ma_sv']) ?></div>
                <?php if ($la_admin): ?>
                    <div class="mt-3">
                        <a href="sinhvien.php?sua=<?= urlencode($sv['ma_sv']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Sửa</a>
                        <a href="diem.php?ma_lop=<?= urlencode($sv['ma_lop']) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-plus-lg"></i> Nhập điểm</a>
                    </div>
                <?php endif; ?>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">Lớp</span><span><?= e($sv['ma_lop'] . ' – ' . $sv['ten_lop']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">Ngành</span><span><?= e($sv['nganh']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">Khóa</span><span><?= $sv['nam_nhap_hoc'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">Ngày sinh</span><span><?= fmt_date($sv['ngay_sinh']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">Giới tính</span><span><?= e($sv['gioi_tinh']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">Email</span><span class="text-break"><?= e($sv['email']) ?></span></li>
                <?php if ($la_admin): ?>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">Điện thoại</span><span><?= e($sv['so_dt']) ?></span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-body-secondary">Địa chỉ</span><span class="text-end"><?= e($sv['dia_chi']) ?></span></li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if (!$la_admin): ?>
            <!-- Form cập nhật liên hệ: chỉ sinh viên mới thấy -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body fw-semibold"><i class="bi bi-telephone me-1"></i> Cập nhật liên hệ</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input class="form-control" name="so_dt" maxlength="15" value="<?= e($sv['so_dt']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input class="form-control" name="dia_chi" maxlength="200" value="<?= e($sv['dia_chi']) ?>">
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Lưu</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-6">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <div class="small text-body-secondary">GPA tích lũy (hệ 4)</div>
                        <div class="fs-3 fw-semibold text-primary"><?= $gpa === null ? '–' : number_format($gpa, 2) ?></div>
                        <div class="small"><?= xep_loai_gpa($gpa) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <div class="small text-body-secondary">Tín chỉ tích lũy</div>
                        <div class="fs-3 fw-semibold"><?= $tc_tich_luy ?></div>
                        <div class="small text-body-secondary"><?= count($tich_luy) ?> môn</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cột phải: bảng điểm theo học kỳ -->
    <div class="col-lg-8">
        <?php if (!$theo_ky): ?>
            <div class="card border-0 shadow-sm"><div class="card-body text-center text-body-secondary py-5">Chưa có điểm.</div></div>
        <?php endif; ?>

        <?php foreach ($theo_ky as $hoc_ky => $ds): ?>
            <?php $gpa_ky = tinh_gpa($ds); ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-calendar3 me-1"></i> Học kỳ <?= e($hoc_ky) ?></span>
                    <span class="small text-body-secondary">
                        GPA kỳ: <b class="text-body"><?= $gpa_ky === null ? '–' : number_format($gpa_ky, 2) ?></b>
                        · <?= array_sum(array_column($ds, 'so_tin_chi')) ?> TC
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Môn học</th>
                                <th class="text-center">TC</th>
                                <th class="text-center">CC</th>
                                <th class="text-center">GK</th>
                                <th class="text-center">CK</th>
                                <th class="text-center">Hệ 10</th>
                                <th class="text-center">Chữ</th>
                                <th class="text-center">Hệ 4</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ds as $d): ?>
                            <tr class="<?= $d['diem_tong'] !== null && $d['diem_tong'] < 4 ? 'table-danger' : '' ?>">
                                <td class="ps-3"><?= e($d['ten_mh']) ?> <span class="small text-body-secondary"><?= e($d['ma_mh']) ?></span></td>
                                <td class="text-center"><?= $d['so_tin_chi'] ?></td>
                                <td class="text-center"><?= fmt_diem($d['diem_chuyen_can']) ?></td>
                                <td class="text-center"><?= fmt_diem($d['diem_giua_ky']) ?></td>
                                <td class="text-center"><?= fmt_diem($d['diem_cuoi_ky']) ?></td>
                                <td class="text-center fw-semibold"><?= fmt_diem($d['diem_tong']) ?></td>
                                <td class="text-center"><?= badge_diem_chu($d['diem_tong']) ?></td>
                                <td class="text-center fw-semibold"><?= fmt_he_4($d['diem_tong']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
