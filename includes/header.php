<?php
$u = $_SESSION['user'] ?? null;

// Tìm trang đang mở để tô đậm menu. Ví dụ: sinhvien_xem thuộc nhóm sinhvien
$trang_mo = basename($_SERVER['PHP_SELF'], '.php');

// Menu theo vai trò: [tên file => [icon, chữ]]
$menu = ['sinhvien_xem' => ['bi-person-badge', 'Hồ sơ & điểm']];
if ($u && $u['role'] == 'admin') {
    $menu = [
        'index'    => ['bi-speedometer2', 'Tổng quan'],
        'lop'      => ['bi-people', 'Lớp'],
        'sinhvien' => ['bi-person-vcard', 'Sinh viên'],
        'monhoc'   => ['bi-journal-bookmark', 'Môn học'],
        'diem'     => ['bi-clipboard-data', 'Điểm'],
        'taikhoan' => ['bi-key', 'Tài khoản'],
        'email'    => ['bi-envelope', 'Email'],
    ];
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Font sans-serif có đầy đủ tiếng Việt, áp dụng cho toàn bộ Bootstrap */
        :root {
            --bs-font-sans-serif: 'Be Vietnam Pro', Arial, sans-serif;
            --bs-body-font-family: var(--bs-font-sans-serif);
        }
    </style>
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand fw-semibold" href="index.php"><i class="bi bi-mortarboard-fill me-1"></i><?= APP_NAME ?></a>
        <?php if ($u): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <?php foreach ($menu as $file => [$icon, $chu]): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $file == $trang_mo ? 'active' : '' ?>" href="<?= $file ?>.php"><i class="bi <?= $icon ?>"></i> <?= e($chu) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= e($u['ho_ten'] ?? $u['username']) ?>
                            <span class="badge text-bg-light ms-1"><?= $u['role'] == 'admin' ? 'Quản trị' : 'Sinh viên' ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="doi_mat_khau.php"><i class="bi bi-shield-lock me-1"></i> Đổi mật khẩu</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>

<main class="container-fluid px-lg-4 py-4 flex-grow-1">

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show">
        <?= e($_SESSION['flash']['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
