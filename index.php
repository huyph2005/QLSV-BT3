<?php
require_once 'includes/init.php';
require_login();

// Sinh viên thì chuyển sang trang hồ sơ
if ($_SESSION['user']['role'] != 'admin') {
    redirect('sinhvien_xem.php');
}

// ===== LẤY DỮ LIỆU =====
$dem = q_one("SELECT
        (SELECT COUNT(*) FROM lop) AS lop,
        (SELECT COUNT(*) FROM sinhvien) AS sinhvien,
        (SELECT COUNT(*) FROM mon_hoc) AS mon_hoc,
        (SELECT COUNT(*) FROM diem) AS diem,
        (SELECT COUNT(*) FROM diem WHERE diem_tong < 4) AS truot,
        (SELECT COUNT(*) FROM email_log WHERE trang_thai = 'thanh_cong') AS mail_ok");

// Kết quả theo môn. Tỷ lệ đạt tính trên các lượt đã đủ điểm, môn có tỷ lệ thấp xếp trước
$theo_mon = q_all("SELECT m.ma_mh, m.ten_mh, m.so_tin_chi,
        COUNT(d.id) AS so_luot,
        ROUND(AVG(d.diem_tong), 2) AS tb,
        ROUND(SUM(d.diem_tong >= 4) * 100 / COUNT(d.diem_tong)) AS ty_le
    FROM mon_hoc m
    LEFT JOIN diem d ON d.ma_mh = m.ma_mh
    GROUP BY m.ma_mh, m.ten_mh, m.so_tin_chi
    ORDER BY COUNT(d.diem_tong) = 0, ty_le, m.ten_mh");

// Sĩ số và điểm trung bình theo lớp
$theo_lop = q_all("SELECT l.ma_lop, l.ten_lop, COUNT(DISTINCT s.ma_sv) AS si_so, ROUND(AVG(d.diem_tong), 2) AS tb
    FROM lop l
    LEFT JOIN sinhvien s ON s.ma_lop = l.ma_lop
    LEFT JOIN diem d ON d.ma_sv = s.ma_sv
    GROUP BY l.ma_lop, l.ten_lop
    ORDER BY l.ma_lop");

// Top 5 sinh viên theo điểm trung bình hệ 10 có trọng số tín chỉ
$top_sv = q_all("SELECT s.ma_sv, s.ho_ten, s.ma_lop, ROUND(SUM(d.diem_tong * m.so_tin_chi) / SUM(m.so_tin_chi), 2) AS tb
    FROM sinhvien s
    JOIN diem d ON d.ma_sv = s.ma_sv AND d.diem_tong IS NOT NULL
    JOIN mon_hoc m ON m.ma_mh = d.ma_mh
    GROUP BY s.ma_sv, s.ho_ten, s.ma_lop
    ORDER BY tb DESC, s.ma_sv
    LIMIT 5");

// 6 ô thống kê ở đầu trang: [chữ, số, icon, màu, link]
$cards = [
    ['Sinh viên',    $dem['sinhvien'], 'bi-person-vcard',         'success',   'sinhvien.php'],
    ['Lớp',          $dem['lop'],      'bi-people',               'primary',   'lop.php'],
    ['Môn học',      $dem['mon_hoc'],  'bi-journal-bookmark',     'info',      'monhoc.php'],
    ['Bản ghi điểm', $dem['diem'],     'bi-clipboard-data',       'warning',   'diem.php'],
    ['Lượt điểm F',  $dem['truot'],    'bi-exclamation-triangle', 'danger',    'diem.php'],
    ['Email đã gửi', $dem['mail_ok'],  'bi-envelope',             'secondary', 'email.php'],
];

$title = 'Tổng quan';
include 'includes/header.php';
?>

<div class="mb-4">
    <h1 class="h4 mb-1">Xin chào, <?= e($u['username']) ?></h1>
    <div class="text-body-secondary">Tình hình đào tạo hôm nay, <?= date('d/m/Y') ?></div>
</div>

<!-- 6 ô thống kê -->
<div class="row g-3 mb-3">
    <?php foreach ($cards as [$ten, $so, $icon, $mau, $link]): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= $link ?>" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-3 bg-<?= $mau ?>-subtle text-<?= $mau ?>-emphasis p-2 me-3 fs-4 lh-1"><i class="bi <?= $icon ?>"></i></div>
                    <div>
                        <div class="fs-4 fw-semibold text-body lh-sm"><?= $so ?></div>
                        <div class="small text-body-secondary"><?= $ten ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <!-- Cột trái: kết quả theo môn -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body fw-semibold"><i class="bi bi-bar-chart-line me-1 text-primary"></i> Kết quả theo môn</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Môn học</th>
                            <th class="text-center">Lượt học</th>
                            <th class="text-center">Điểm TB</th>
                            <th class="pe-3" style="min-width:220px">Tỷ lệ đạt</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($theo_mon as $m): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold"><?= e($m['ten_mh']) ?></div>
                                <div class="small text-body-secondary"><?= e($m['ma_mh']) ?> · <?= $m['so_tin_chi'] ?> TC</div>
                            </td>
                            <td class="text-center"><?= $m['so_luot'] ?></td>
                            <td class="text-center"><?= badge_diem_so($m['tb']) ?></td>
                            <td class="pe-3">
                                <?php if ($m['ty_le'] === null): ?>
                                    <span class="small text-body-tertiary">Chưa có điểm</span>
                                <?php else: ?>
                                    <?php $mau = $m['ty_le'] >= 90 ? 'success' : ($m['ty_le'] >= 70 ? 'warning' : 'danger'); ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px">
                                            <div class="progress-bar bg-<?= $mau ?>" style="width:<?= $m['ty_le'] ?>%"></div>
                                        </div>
                                        <span class="small fw-semibold text-end" style="width:40px"><?= $m['ty_le'] ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cột phải: theo lớp và top 5 -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body fw-semibold"><i class="bi bi-people me-1 text-success"></i> Theo lớp</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($theo_lop as $l): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <a href="sinhvien.php?lop=<?= urlencode($l['ma_lop']) ?>" class="fw-semibold text-body text-decoration-none"><?= e($l['ma_lop']) ?></a>
                            <div class="small text-body-secondary"><?= e($l['ten_lop']) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="badge text-bg-secondary"><?= $l['si_so'] ?> SV</span>
                            <div class="small text-body-secondary">ĐTB: <?= $l['tb'] ?? '–' ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body fw-semibold"><i class="bi bi-trophy me-1 text-warning"></i> Top 5 sinh viên (ĐTB hệ 10)</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($top_sv as $i => $s): ?>
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <span class="badge rounded-pill <?= $i == 0 ? 'text-bg-warning border border-warning' : 'text-bg-light border' ?>">#<?= $i + 1 ?></span>
                        <div class="flex-grow-1">
                            <a href="sinhvien_xem.php?ma_sv=<?= urlencode($s['ma_sv']) ?>" class="fw-semibold text-body text-decoration-none"><?= e($s['ho_ten']) ?></a>
                            <div class="small text-body-secondary"><?= e($s['ma_sv']) ?> · <?= e($s['ma_lop']) ?></div>
                        </div>
                        <span class="fw-semibold"><?= fmt_diem($s['tb']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
