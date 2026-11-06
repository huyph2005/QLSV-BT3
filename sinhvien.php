<?php
require_once 'includes/init.php';
require_admin();

// ===== 1. XỬ LÝ FORM: thêm / sửa / xóa =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $ma_sv = trim($_POST['ma_sv']);

    // Các ô của form thêm/sửa (khi xóa thì không dùng tới)
    $gia_tri = [
        trim($_POST['ho_ten'] ?? ''),
        null_neu_rong($_POST['ngay_sinh'] ?? ''),
        $_POST['gioi_tinh'] ?? 'Nam',
        null_neu_rong($_POST['email'] ?? ''),
        null_neu_rong($_POST['so_dt'] ?? ''),
        null_neu_rong($_POST['dia_chi'] ?? ''),
        $_POST['ma_lop'] ?? '',
        $ma_sv,
    ];

    try {
        if ($action == 'them') {
            // Thêm sinh viên + tạo tài khoản: dùng transaction để cùng thành công hoặc cùng hủy
            $conn->begin_transaction();
            q("INSERT INTO sinhvien (ho_ten, ngay_sinh, gioi_tinh, email, so_dt, dia_chi, ma_lop, ma_sv) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", $gia_tri);
            q("INSERT INTO taikhoan (username, password, role, ma_sv, doi_mat_khau) VALUES (?, ?, 'sinhvien', ?, 1)",
              [$ma_sv, password_hash(DEFAULT_PASSWORD, PASSWORD_DEFAULT), $ma_sv]);
            $conn->commit();
            flash("Đã thêm sinh viên $ma_sv. Tài khoản: $ma_sv / " . DEFAULT_PASSWORD);
        } elseif ($action == 'sua') {
            q("UPDATE sinhvien SET ho_ten = ?, ngay_sinh = ?, gioi_tinh = ?, email = ?, so_dt = ?, dia_chi = ?, ma_lop = ? WHERE ma_sv = ?", $gia_tri);
            flash("Đã cập nhật sinh viên $ma_sv.");
        } elseif ($action == 'xoa') {
            // ON DELETE CASCADE: điểm, tài khoản, nhật ký mail của sinh viên cũng bị xóa theo
            q("DELETE FROM sinhvien WHERE ma_sv = ?", [$ma_sv]);
            flash("Đã xóa sinh viên $ma_sv.");
        }
    } catch (mysqli_sql_exception $ex) {
        $conn->rollback();
        flash(thong_bao_loi($ex), 'danger');
    }
    redirect('sinhvien.php');
}

// ===== 2. AJAX: JS gọi sinhvien.php?ajax=1, PHP chỉ trả về phần bảng rồi dừng =====
if (isset($_GET['ajax'])) {
    // Ghép điều kiện lọc: ô nào có nhập mới thêm vào câu SQL
    $sql = "SELECT * FROM sinhvien WHERE 1 = 1";
    $params = [];
    if ($_GET['q'] != '') {
        $sql .= " AND (ma_sv LIKE ? OR ho_ten LIKE ? OR email LIKE ?)";
        $tim = '%' . $_GET['q'] . '%';
        array_push($params, $tim, $tim, $tim);
    }
    if ($_GET['lop'] != '') {
        $sql .= " AND ma_lop = ?";
        $params[] = $_GET['lop'];
    }
    $ds_sv = q_all($sql . " ORDER BY ma_sv", $params);
    ?>
    <div class="px-3 py-2 small text-body-secondary border-bottom">Tìm thấy <b class="text-body"><?= count($ds_sv) ?></b> sinh viên</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Mã SV</th><th>Họ tên</th><th>Ngày sinh</th><th>Giới tính</th><th>Lớp</th><th>Email</th><th>SĐT</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($ds_sv as $s): ?>
                <tr>
                    <td class="fw-semibold"><?= e($s['ma_sv']) ?></td>
                    <td><?= e($s['ho_ten']) ?></td>
                    <td><?= fmt_date($s['ngay_sinh']) ?></td>
                    <td><?= e($s['gioi_tinh']) ?></td>
                    <td><?= e($s['ma_lop']) ?></td>
                    <td class="small"><?= e($s['email']) ?></td>
                    <td class="small"><?= e($s['so_dt']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="sinhvien_xem.php?ma_sv=<?= urlencode($s['ma_sv']) ?>" class="btn btn-sm btn-outline-secondary" title="Xem"><i class="bi bi-eye"></i></a>
                        <a href="sinhvien.php?sua=<?= urlencode($s['ma_sv']) ?>" class="btn btn-sm btn-outline-primary" title="Sửa"><i class="bi bi-pencil"></i></a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Xóa sinh viên này? Điểm và tài khoản của sinh viên cũng bị xóa.')">
                            <input type="hidden" name="action" value="xoa">
                            <input type="hidden" name="ma_sv" value="<?= e($s['ma_sv']) ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$ds_sv): ?>
                <tr><td colspan="8" class="text-center text-body-secondary py-4">Không tìm thấy sinh viên</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    exit;
}

// ===== 3. LẤY DỮ LIỆU ĐỂ HIỂN THỊ =====
$ds_lop = q_all("SELECT ma_lop, ten_lop FROM lop ORDER BY ma_lop");

// Có ?them hoặc ?sua=... thì hiện FORM, không thì hiện DANH SÁCH
$hien_form = isset($_GET['them']) || isset($_GET['sua']);
$sua = null;
if (isset($_GET['sua'])) {
    $sua = q_one("SELECT * FROM sinhvien WHERE ma_sv = ?", [$_GET['sua']]);
}

$title = 'Sinh viên';
include 'includes/header.php';
?>

<?php if ($hien_form): ?>
<!-- ========== FORM THÊM / SỬA ========== -->
<div class="card border-0 shadow-sm" style="max-width:860px">
    <div class="card-body p-4">
        <h1 class="h5 mb-1"><?= $sua ? 'Sửa sinh viên' : 'Thêm sinh viên' ?></h1>
        <?php if (!$sua): ?>
            <p class="small text-body-secondary">Hệ thống tự tạo tài khoản: tên đăng nhập là mã SV, mật khẩu mặc định <?= DEFAULT_PASSWORD ?>.</p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="<?= $sua ? 'sua' : 'them' ?>">
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Mã sinh viên</label>
                    <input name="ma_sv" class="form-control" maxlength="10" required value="<?= e($sua['ma_sv'] ?? '') ?>" <?= $sua ? 'readonly' : '' ?>>
                </div>
                <div class="col-sm-8">
                    <label class="form-label">Họ và tên</label>
                    <input name="ho_ten" class="form-control" maxlength="100" required value="<?= e($sua['ho_ten'] ?? '') ?>">
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Ngày sinh</label>
                    <input type="date" name="ngay_sinh" class="form-control" value="<?= e($sua['ngay_sinh'] ?? '') ?>">
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Giới tính</label>
                    <select name="gioi_tinh" class="form-select">
                        <?php foreach (['Nam', 'Nữ', 'Khác'] as $gt): ?>
                            <option <?= ($sua['gioi_tinh'] ?? '') == $gt ? 'selected' : '' ?>><?= $gt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Lớp</label>
                    <select name="ma_lop" class="form-select" required>
                        <option value="">-- Chọn lớp --</option>
                        <?php foreach ($ds_lop as $l): ?>
                            <option value="<?= e($l['ma_lop']) ?>" <?= ($sua['ma_lop'] ?? '') == $l['ma_lop'] ? 'selected' : '' ?>><?= e($l['ma_lop'] . ' – ' . $l['ten_lop']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" maxlength="100" value="<?= e($sua['email'] ?? '') ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Số điện thoại</label>
                    <input name="so_dt" class="form-control" maxlength="15" value="<?= e($sua['so_dt'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Địa chỉ</label>
                    <input name="dia_chi" class="form-control" maxlength="200" value="<?= e($sua['dia_chi'] ?? '') ?>">
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu</button>
                <a href="sinhvien.php" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ========== DANH SÁCH ========== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Danh sách sinh viên</h1>
    <a href="sinhvien.php?them=1" class="btn btn-primary"><i class="bi bi-person-plus"></i> Thêm sinh viên</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body border-bottom">
        <div class="row g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input class="form-control" id="q" oninput="taiBang()" placeholder="Gõ mã SV, họ tên, email..." autocomplete="off">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="lop" onchange="taiBang()">
                    <option value="">-- Tất cả lớp --</option>
                    <?php foreach ($ds_lop as $l): ?>
                        <option value="<?= e($l['ma_lop']) ?>" <?= ($_GET['lop'] ?? '') == $l['ma_lop'] ? 'selected' : '' ?>><?= e($l['ma_lop']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Bảng sinh viên do AJAX tải vào đây -->
    <div id="bangSv"></div>
</div>

<script>
// AJAX: gọi sinhvien.php?ajax=1 rồi đưa kết quả vào div#bangSv, không tải lại cả trang
function taiBang() {
    var q = document.getElementById("q").value;
    var lop = document.getElementById("lop").value;

    var xhttp = new XMLHttpRequest();
    xhttp.onload = function () {
        document.getElementById("bangSv").innerHTML = this.responseText;
    };
    xhttp.open("GET", "sinhvien.php?ajax=1&q=" + encodeURIComponent(q) + "&lop=" + encodeURIComponent(lop));
    xhttp.send();
}

// Tải bảng lần đầu khi mở trang
taiBang();
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
