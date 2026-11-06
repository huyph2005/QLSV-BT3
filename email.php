<?php
require_once 'includes/init.php';
require_once 'includes/mailer.php';
require_admin();

// ===== XỬ LÝ CÁC NÚT GỬI =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'gui_thu') {
        $to = trim($_POST['to']);
        $tieu_de = '[' . APP_NAME . '] Mail gửi thử';
        $loi = smtp_send($to, $tieu_de, '<p>Đây là mail gửi thử từ hệ thống ' . APP_NAME . '.</p>');
        ghi_email_log(null, $to, $tieu_de, null, $loi);
        $loi == '' ? flash("Đã gửi mail thử tới $to.") : flash("Gửi thử thất bại: $loi", 'danger');
    }

    if ($_POST['action'] == 'gui_bang_diem') {
        // Lấy các sinh viên có email và có điểm trong học kỳ. Có chọn lớp thì lọc theo lớp
        $hoc_ky = $_POST['hoc_ky'];
        $sql = "SELECT DISTINCT s.* FROM sinhvien s JOIN diem d ON d.ma_sv = s.ma_sv
                WHERE d.hoc_ky = ? AND d.diem_tong IS NOT NULL AND s.email IS NOT NULL";
        $params = [$hoc_ky];
        if ($_POST['ma_lop'] != '') {
            $sql .= " AND s.ma_lop = ?";
            $params[] = $_POST['ma_lop'];
        }

        $ok = $loi = 0;
        foreach (q_all($sql, $params) as $sv) {
            gui_bang_diem($sv, $hoc_ky) ? $ok++ : $loi++;
        }
        flash("Gửi bảng điểm kỳ $hoc_ky: $ok thành công, $loi lỗi.", $loi ? 'warning' : 'success');
    }
    redirect('email.php');
}

// ===== LẤY DỮ LIỆU =====
$ds_lop = q_all("SELECT ma_lop FROM lop ORDER BY ma_lop");
$ds_hoc_ky = q_all("SELECT DISTINCT hoc_ky FROM diem ORDER BY SUBSTRING(hoc_ky, 5) DESC, hoc_ky DESC");
$ds_log = q_all("SELECT * FROM email_log ORDER BY id DESC LIMIT 100");

$title = 'Email';
include 'includes/header.php';
?>

<h1 class="h4 mb-3">Gửi bảng điểm qua email</h1>

<?php if (SMTP_USER == ''): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i> Chưa cấu hình SMTP trong <code>config.php</code>, mọi lần gửi sẽ thất bại nhưng vẫn ghi nhật ký.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body fw-semibold"><i class="bi bi-send me-1"></i> Gửi bảng điểm học kỳ</div>
            <div class="card-body">
                <form method="post" class="row g-2 align-items-end" onsubmit="return confirm('Gửi bảng điểm?')">
                    <input type="hidden" name="action" value="gui_bang_diem">
                    <div class="col-sm-5">
                        <label class="form-label">Học kỳ</label>
                        <select class="form-select" name="hoc_ky">
                            <?php foreach ($ds_hoc_ky as $h): ?>
                                <option><?= e($h['hoc_ky']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Lớp</label>
                        <select class="form-select" name="ma_lop">
                            <option value="">-- Tất cả lớp --</option>
                            <?php foreach ($ds_lop as $l): ?>
                                <option><?= e($l['ma_lop']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <button class="btn btn-primary w-100"><i class="bi bi-send"></i> Gửi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-body fw-semibold"><i class="bi bi-envelope-check me-1"></i> Gửi thử</div>
            <div class="card-body">
                <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="action" value="gui_thu">
                    <input type="email" class="form-control" name="to" placeholder="email@example.com" required>
                    <button class="btn btn-outline-primary text-nowrap">Gửi thử</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Nhật ký gửi mail -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-body fw-semibold"><i class="bi bi-list-check me-1"></i> Nhật ký gửi mail</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr><th>Thời gian</th><th>Mã SV</th><th>Email</th><th>Tiêu đề</th><th>Trạng thái</th></tr>
            </thead>
            <tbody>
            <?php foreach ($ds_log as $m): ?>
                <tr>
                    <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                    <td><?= e($m['ma_sv'] ?? 'Gửi thử') ?></td>
                    <td><?= e($m['email']) ?></td>
                    <td><?= e($m['tieu_de']) ?></td>
                    <td>
                        <?php if ($m['trang_thai'] == 'thanh_cong'): ?>
                            <span class="badge text-bg-success">Thành công</span>
                        <?php else: ?>
                            <span class="badge text-bg-danger" title="<?= e($m['loi']) ?>">Thất bại</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$ds_log): ?>
                <tr><td colspan="5" class="text-center text-body-secondary py-4">Chưa có nhật ký</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
