-- =====================================================================
--  CSDL qlsv_db - Hệ thống quản lý sinh viên (MySQL 8 / MariaDB 10.4+) - 6 bảng
--  Tài khoản mẫu:  admin / admin123
--                  B23DCCN001 ... / 123456  (bắt buộc đổi mật khẩu lần đầu)
-- =====================================================================
DROP DATABASE IF EXISTS qlsv_db;
CREATE DATABASE qlsv_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qlsv_db;

-- ---------------------------------------------------------------- lop
CREATE TABLE lop (
    ma_lop       VARCHAR(10)  NOT NULL,
    ten_lop      VARCHAR(100) NOT NULL,
    nganh        VARCHAR(100) NOT NULL,
    nam_nhap_hoc INT          NOT NULL,
    PRIMARY KEY (ma_lop)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------- sinhvien
CREATE TABLE sinhvien (
    ma_sv     VARCHAR(10)  NOT NULL,
    ho_ten    VARCHAR(100) NOT NULL,
    ngay_sinh DATE         NULL,
    gioi_tinh ENUM('Nam','Nữ','Khác') NOT NULL DEFAULT 'Nam',
    email     VARCHAR(100) NULL,
    so_dt     VARCHAR(15)  NULL,
    dia_chi   VARCHAR(200) NULL,
    ma_lop    VARCHAR(10)  NOT NULL,
    PRIMARY KEY (ma_sv),
    UNIQUE KEY uq_sinhvien_email (email),
    KEY idx_sinhvien_lop (ma_lop),
    CONSTRAINT fk_sinhvien_lop FOREIGN KEY (ma_lop) REFERENCES lop (ma_lop)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------ mon_hoc
CREATE TABLE mon_hoc (
    ma_mh      VARCHAR(10)  NOT NULL,
    ten_mh     VARCHAR(100) NOT NULL,
    so_tin_chi INT          NOT NULL DEFAULT 3,
    PRIMARY KEY (ma_mh),
    CONSTRAINT chk_mon_hoc_tc CHECK (so_tin_chi BETWEEN 1 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------- diem
-- diem_tong = 10% chuyên cần + 30% giữa kỳ + 60% cuối kỳ (cột tự tính)
CREATE TABLE diem (
    id              INT          NOT NULL AUTO_INCREMENT,
    ma_sv           VARCHAR(10)  NOT NULL,
    ma_mh           VARCHAR(10)  NOT NULL,
    hoc_ky          VARCHAR(10)  NOT NULL,
    diem_chuyen_can DECIMAL(4,2) NULL,
    diem_giua_ky    DECIMAL(4,2) NULL,
    diem_cuoi_ky    DECIMAL(4,2) NULL,
    diem_tong       DECIMAL(4,2) AS (ROUND(diem_chuyen_can * 0.1 + diem_giua_ky * 0.3 + diem_cuoi_ky * 0.6, 2)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_diem (ma_sv, ma_mh, hoc_ky),
    KEY idx_diem_mh (ma_mh),
    CONSTRAINT fk_diem_sv FOREIGN KEY (ma_sv) REFERENCES sinhvien (ma_sv)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_diem_mh FOREIGN KEY (ma_mh) REFERENCES mon_hoc (ma_mh)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_diem_cc CHECK (diem_chuyen_can BETWEEN 0 AND 10),
    CONSTRAINT chk_diem_gk CHECK (diem_giua_ky BETWEEN 0 AND 10),
    CONSTRAINT chk_diem_ck CHECK (diem_cuoi_ky BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------- taikhoan
CREATE TABLE taikhoan (
    id          INT          NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)  NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','sinhvien') NOT NULL DEFAULT 'sinhvien',
    ma_sv       VARCHAR(10)  NULL,
    doi_mat_khau TINYINT(1)  NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_taikhoan_username (username),
    UNIQUE KEY uq_taikhoan_ma_sv (ma_sv),
    CONSTRAINT fk_taikhoan_sv FOREIGN KEY (ma_sv) REFERENCES sinhvien (ma_sv)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------- email_log
-- ma_sv NULL = mail gửi thử
CREATE TABLE email_log (
    id         INT          NOT NULL AUTO_INCREMENT,
    ma_sv      VARCHAR(10)  NULL,
    email      VARCHAR(100) NOT NULL,
    tieu_de    VARCHAR(200) NOT NULL,
    hoc_ky     VARCHAR(10)  NULL,
    trang_thai ENUM('thanh_cong','that_bai') NOT NULL,
    loi        VARCHAR(500) NULL,
    gui_boi    VARCHAR(50)  NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email_log_sv (ma_sv),
    CONSTRAINT fk_email_log_sv FOREIGN KEY (ma_sv) REFERENCES sinhvien (ma_sv)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  Dữ liệu mẫu
-- =====================================================================
INSERT INTO lop (ma_lop, ten_lop, nganh, nam_nhap_hoc) VALUES
('D23CQCN01', 'Công nghệ thông tin 01', 'Công nghệ thông tin', 2023),
('D23CQCN02', 'Công nghệ thông tin 02', 'Công nghệ thông tin', 2023),
('D23CQAT01', 'An toàn thông tin 01',   'An toàn thông tin',   2023);

INSERT INTO mon_hoc (ma_mh, ten_mh, so_tin_chi) VALUES
('BAS1157', 'Tiếng Anh 1', 2),
('BAS1203', 'Giải tích 1', 3),
('INT1306', 'Cấu trúc dữ liệu và giải thuật', 3),
('INT1313', 'Cơ sở dữ liệu', 3),
('INT1332', 'Lập trình hướng đối tượng', 4),
('INT1339', 'Ngôn ngữ lập trình C++', 3),
('INT1434', 'Lập trình Web', 3),
('SKD1101', 'Kỹ năng thuyết trình', 1);

INSERT INTO sinhvien (ma_sv, ho_ten, ngay_sinh, gioi_tinh, email, so_dt, dia_chi, ma_lop) VALUES
('B23DCCN001', 'Nguyễn Văn An',     '2005-03-12', 'Nam', 'an.nv@example.com',    '0912345601', 'Hà Đông, Hà Nội',     'D23CQCN01'),
('B23DCCN002', 'Trần Thị Bình',     '2005-07-25', 'Nữ',  'binh.tt@example.com',  '0912345602', 'Cầu Giấy, Hà Nội',    'D23CQCN01'),
('B23DCCN003', 'Lê Minh Châu',      '2005-11-02', 'Nữ',  'chau.lm@example.com',  '0912345603', 'Thanh Xuân, Hà Nội',  'D23CQCN01'),
('B23DCCN004', 'Phạm Quốc Dũng',    '2005-01-30', 'Nam', 'dung.pq@example.com',  '0912345604', 'Nam Định',            'D23CQCN01'),
('B23DCCN051', 'Hoàng Thu Hà',      '2005-05-18', 'Nữ',  'ha.ht@example.com',    '0912345605', 'Hải Phòng',           'D23CQCN02'),
('B23DCCN052', 'Vũ Đức Huy',        '2005-09-09', 'Nam', 'huy.vd@example.com',   '0912345606', 'Bắc Ninh',            'D23CQCN02'),
('B23DCCN053', 'Đặng Ngọc Lan',     '2005-12-21', 'Nữ',  'lan.dn@example.com',   '0912345607', 'Thái Bình',           'D23CQCN02'),
('B23DCAT001', 'Bùi Tuấn Kiệt',     '2005-04-14', 'Nam', 'kiet.bt@example.com',  '0912345608', 'Hà Nam',              'D23CQAT01'),
('B23DCAT002', 'Đỗ Phương Linh',    '2005-08-03', 'Nữ',  'linh.dp@example.com',  '0912345609', 'Hưng Yên',            'D23CQAT01'),
('B23DCAT003', 'Ngô Thành Long',    '2005-10-27', 'Nam', NULL,                   '0912345610', 'Ninh Bình',           'D23CQAT01'),
('B23DCCN005', 'Trịnh Hoài Nam',    '2005-02-08', 'Nam', 'nam.th@example.com',   '0912345611', 'Thanh Hóa',           'D23CQCN01'),
('B23DCCN054', 'Lý Thu Trang',      '2005-06-16', 'Nữ',  'trang.lt@example.com', '0912345612', 'Phú Thọ',             'D23CQCN02'),
('B23DCCN055', 'Phan Anh Tú',       '2005-03-29', 'Nam', 'tu.pa@example.com',    '0912345613', 'Vĩnh Phúc',           'D23CQCN02'),
('B23DCAT004', 'Dương Hải Đăng',    '2005-07-07', 'Nam', 'dang.dh@example.com',  '0912345614', 'Hải Dương',           'D23CQAT01');

INSERT INTO diem (ma_sv, ma_mh, hoc_ky, diem_chuyen_can, diem_giua_ky, diem_cuoi_ky) VALUES
('B23DCCN001', 'BAS1203', 'HK1-23-24', 10, 8.0, 7.5),
('B23DCCN001', 'INT1339', 'HK1-23-24', 9,  7.0, 8.0),
('B23DCCN001', 'INT1306', 'HK2-23-24', 10, 8.5, 9.0),
('B23DCCN001', 'INT1313', 'HK2-23-24', 10, 9.0, 8.5),
('B23DCCN002', 'BAS1203', 'HK1-23-24', 8,  6.0, 5.5),
('B23DCCN002', 'INT1339', 'HK1-23-24', 10, 7.5, 7.0),
('B23DCCN002', 'INT1306', 'HK2-23-24', 9,  5.0, 4.0),
('B23DCCN003', 'BAS1203', 'HK1-23-24', 10, 9.5, 9.0),
('B23DCCN003', 'INT1339', 'HK1-23-24', 10, 9.0, 9.5),
('B23DCCN004', 'BAS1203', 'HK1-23-24', 6,  3.0, 2.5),
('B23DCCN004', 'INT1339', 'HK1-23-24', 7,  5.0, 6.0),
('B23DCCN051', 'BAS1203', 'HK1-23-24', 10, 7.0, 6.5),
('B23DCCN051', 'INT1434', 'HK2-23-24', 10, 8.0, 8.5),
('B23DCCN052', 'BAS1203', 'HK1-23-24', 9,  6.5, 7.0),
('B23DCCN053', 'BAS1203', 'HK1-23-24', 10, 8.0, 8.0),
('B23DCAT001', 'BAS1203', 'HK1-23-24', 8,  7.0, 6.0),
('B23DCAT001', 'INT1313', 'HK2-23-24', 10, 6.5, 7.5),
('B23DCAT002', 'BAS1203', 'HK1-23-24', 10, 9.0, 8.5),
('B23DCAT003', 'BAS1203', 'HK1-23-24', 5,  4.0, 3.5),
('B23DCCN001', 'BAS1157', 'HK1-23-24', 9,  8.0, 8.5),
('B23DCCN002', 'BAS1157', 'HK1-23-24', 9,  7.0, 6.5),
('B23DCCN002', 'INT1313', 'HK2-23-24', 8,  6.5, 6.0),
('B23DCCN003', 'BAS1157', 'HK1-23-24', 10, 9.0, 9.0),
('B23DCCN003', 'INT1306', 'HK2-23-24', 10, 8.5, 9.0),
('B23DCCN003', 'INT1313', 'HK2-23-24', 9,  9.0, 9.5),
('B23DCCN004', 'BAS1157', 'HK1-23-24', 7,  5.0, 5.0),
('B23DCCN004', 'BAS1203', 'HK2-23-24', 8,  6.0, 5.5),
('B23DCCN004', 'INT1306', 'HK2-23-24', 6,  4.5, 4.0),
('B23DCCN051', 'INT1339', 'HK1-23-24', 9,  7.5, 7.0),
('B23DCCN051', 'BAS1157', 'HK1-23-24', 10, 8.5, 8.0),
('B23DCCN051', 'INT1306', 'HK2-23-24', 9,  7.0, 6.5),
('B23DCCN052', 'INT1339', 'HK1-23-24', 8,  6.0, 6.5),
('B23DCCN052', 'BAS1157', 'HK1-23-24', 9,  7.0, 7.5),
('B23DCCN052', 'INT1306', 'HK2-23-24', 8,  5.5, 5.0),
('B23DCCN052', 'INT1313', 'HK2-23-24', 9,  7.0, 7.0),
('B23DCCN053', 'INT1339', 'HK1-23-24', 10, 8.5, 9.0),
('B23DCCN053', 'BAS1157', 'HK1-23-24', 10, 9.0, 8.5),
('B23DCCN053', 'INT1306', 'HK2-23-24', 9,  8.0, 8.5),
('B23DCCN053', 'INT1434', 'HK2-23-24', 10, 9.0, 9.5),
('B23DCAT001', 'INT1339', 'HK1-23-24', 8,  6.5, 7.0),
('B23DCAT001', 'BAS1157', 'HK1-23-24', 9,  7.5, 7.0),
('B23DCAT001', 'INT1306', 'HK2-23-24', 9,  7.0, 7.5),
('B23DCAT002', 'INT1339', 'HK1-23-24', 10, 8.0, 9.0),
('B23DCAT002', 'BAS1157', 'HK1-23-24', 10, 9.0, 9.0),
('B23DCAT002', 'INT1306', 'HK2-23-24', 9,  8.0, 8.0),
('B23DCAT002', 'INT1313', 'HK2-23-24', 10, 8.5, 8.5),
('B23DCAT003', 'INT1339', 'HK1-23-24', 7,  5.0, 4.5),
('B23DCAT003', 'BAS1157', 'HK1-23-24', 8,  6.0, 5.0),
('B23DCAT003', 'BAS1203', 'HK2-23-24', 8,  5.5, 5.5),
('B23DCAT003', 'INT1306', 'HK2-23-24', 6,  3.0, 3.0),
('B23DCCN005', 'BAS1203', 'HK1-23-24', 9,  7.0, 7.5),
('B23DCCN005', 'INT1339', 'HK1-23-24', 10, 8.0, 8.0),
('B23DCCN005', 'BAS1157', 'HK1-23-24', 8,  7.0, 7.0),
('B23DCCN005', 'INT1332', 'HK2-23-24', 9,  8.0, 8.5),
('B23DCCN054', 'BAS1203', 'HK1-23-24', 10, 9.0, 8.5),
('B23DCCN054', 'INT1339', 'HK1-23-24', 10, 9.0, 9.0),
('B23DCCN054', 'BAS1157', 'HK1-23-24', 9,  8.0, 8.0),
('B23DCCN054', 'INT1332', 'HK2-23-24', 10, 8.5, 9.0),
('B23DCCN055', 'BAS1203', 'HK1-23-24', 7,  5.5, 5.0),
('B23DCCN055', 'INT1339', 'HK1-23-24', 8,  6.0, 6.0),
('B23DCCN055', 'BAS1157', 'HK1-23-24', 8,  6.5, 6.0),
('B23DCCN055', 'INT1332', 'HK2-23-24', 8,  6.0, 7.0),
('B23DCAT004', 'BAS1203', 'HK1-23-24', 9,  8.0, 7.0),
('B23DCAT004', 'INT1339', 'HK1-23-24', 9,  7.5, 8.0),
('B23DCAT004', 'BAS1157', 'HK1-23-24', 10, 8.0, 7.5),
('B23DCAT004', 'INT1332', 'HK2-23-24', 9,  7.0, 8.0);

-- admin/admin123 ; sinh viên/123456
INSERT INTO taikhoan (username, password, role, ma_sv, doi_mat_khau) VALUES
('admin',      '$2y$10$dxb6b/CXU3h1cL6Yyq.aIeJhn94tCDdYwl1Sr/sMJldD8GdukhgO.', 'admin',    NULL,         0),
('B23DCCN001', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN001', 1),
('B23DCCN002', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN002', 1),
('B23DCCN003', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN003', 1),
('B23DCCN051', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN051', 1),
('B23DCAT001', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCAT001', 1),
('B23DCCN004', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN004', 1),
('B23DCCN005', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN005', 1),
('B23DCCN052', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN052', 1),
('B23DCCN053', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN053', 1),
('B23DCCN054', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN054', 1),
('B23DCCN055', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCCN055', 1),
('B23DCAT002', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCAT002', 1),
('B23DCAT003', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCAT003', 1),
('B23DCAT004', '$2y$10$330.akL412PvqSb2ANS12.5bhBle1W48mqD1SBHu15qLTU/07loOy', 'sinhvien', 'B23DCAT004', 1);
