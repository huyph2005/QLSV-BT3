<?php
// Gửi mail qua SMTP bằng PHP thuần (hỗ trợ TLS cổng 587 và SSL cổng 465)

// Gửi 1 lệnh tới máy chủ SMTP rồi kiểm tra mã trả về
function smtp_lenh($fp, $lenh, $ma_hop_le)
{
    fwrite($fp, $lenh . "\r\n");
    return smtp_doc($fp, $ma_hop_le);
}

// Đọc phản hồi của máy chủ. Sai mã thì ném Exception
function smtp_doc($fp, $ma_hop_le)
{
    $phan_hoi = '';
    while (($dong = fgets($fp, 515)) !== false) {
        $phan_hoi .= $dong;
        // Dòng cuối có dấu cách ở vị trí thứ 4, ví dụ: "250 OK"
        if (isset($dong[3]) && $dong[3] == ' ') break;
    }
    if ($phan_hoi == '') {
        throw new Exception('Máy chủ SMTP không phản hồi (timeout)');
    }
    if (!in_array((int) substr($phan_hoi, 0, 3), $ma_hop_le)) {
        throw new Exception('SMTP: ' . trim($phan_hoi));
    }
    return $phan_hoi;
}

// Mã hóa tiếng Việt cho tiêu đề mail
function ma_hoa_tieu_de($text)
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

// Gửi 1 mail. Thành công trả về chuỗi rỗng, thất bại trả về nội dung lỗi
function smtp_send($to, $subject, $html)
{
    if (SMTP_HOST == '' || SMTP_USER == '' || SMTP_PASS == '') {
        return 'Chưa cấu hình SMTP trong config.php';
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return 'Địa chỉ email không hợp lệ';
    }

    $dia_chi = (SMTP_SECURE == 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . SMTP_PORT;
    $fp = @stream_socket_client($dia_chi, $errno, $errstr, 15);
    if (!$fp) {
        return "Không kết nối được máy chủ SMTP: $errstr ($errno)";
    }
    stream_set_timeout($fp, 20);

    $loi = '';
    try {
        smtp_doc($fp, [220]);
        smtp_lenh($fp, 'EHLO localhost', [250]);

        if (SMTP_SECURE == 'tls') {
            smtp_lenh($fp, 'STARTTLS', [220]);
            $tls = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            if (!stream_socket_enable_crypto($fp, true, $tls)) {
                throw new Exception('Không bật được TLS');
            }
            smtp_lenh($fp, 'EHLO localhost', [250]);
        }

        smtp_lenh($fp, 'AUTH LOGIN', [334]);
        smtp_lenh($fp, base64_encode(SMTP_USER), [334]);
        smtp_lenh($fp, base64_encode(SMTP_PASS), [235]);
        smtp_lenh($fp, 'MAIL FROM:<' . SMTP_USER . '>', [250]);
        smtp_lenh($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_lenh($fp, 'DATA', [354]);

        $data  = 'Date: ' . date('r') . "\r\n";
        $data .= 'From: ' . ma_hoa_tieu_de(SMTP_FROM_NAME) . ' <' . SMTP_USER . ">\r\n";
        $data .= 'To: <' . $to . ">\r\n";
        $data .= 'Subject: ' . ma_hoa_tieu_de($subject) . "\r\n";
        $data .= 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . SMTP_HOST . ">\r\n";
        $data .= "MIME-Version: 1.0\r\n";
        $data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $data .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $data .= chunk_split(base64_encode($html), 76, "\r\n") . '.';
        smtp_lenh($fp, $data, [250]);

        fwrite($fp, "QUIT\r\n");
    } catch (Exception $ex) {
        $loi = mb_substr($ex->getMessage(), 0, 500);
    }
    fclose($fp);
    return $loi;
}

// Ghi nhật ký gửi mail. $ma_sv = null nghĩa là mail gửi thử
function ghi_email_log($ma_sv, $email, $tieu_de, $hoc_ky, $loi)
{
    q("INSERT INTO email_log (ma_sv, email, tieu_de, hoc_ky, trang_thai, loi, gui_boi) VALUES (?, ?, ?, ?, ?, ?, ?)",
      [$ma_sv, mb_substr($email, 0, 100), mb_substr($tieu_de, 0, 200), $hoc_ky, $loi == '' ? 'thanh_cong' : 'that_bai', $loi ?: null, $_SESSION['user']['username']]);
}

// Gửi bảng điểm 1 học kỳ cho 1 sinh viên. Thành công trả về true
function gui_bang_diem($sv, $hoc_ky)
{
    $ds_diem = q_all("SELECT d.*, m.ten_mh, m.so_tin_chi FROM diem d JOIN mon_hoc m ON m.ma_mh = d.ma_mh
                      WHERE d.ma_sv = ? AND d.hoc_ky = ? ORDER BY m.ten_mh", [$sv['ma_sv'], $hoc_ky]);
    $tieu_de = "Kết quả học tập học kỳ $hoc_ky – {$sv['ma_sv']}";
    $loi = smtp_send($sv['email'], $tieu_de, noi_dung_bang_diem($sv, $hoc_ky, $ds_diem));
    ghi_email_log($sv['ma_sv'], $sv['email'], $tieu_de, $hoc_ky, $loi);
    return $loi == '';
}

// Tạo nội dung HTML bảng điểm của 1 sinh viên trong 1 học kỳ
function noi_dung_bang_diem($sv, $hoc_ky, $ds_diem)
{
    $td = 'style="border:1px solid #ccc;padding:6px"';
    $giua = 'style="border:1px solid #ccc;padding:6px;text-align:center"';
    $th = 'style="border:1px solid #ccc;padding:6px;background:#1f4e79;color:#fff"';

    $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222">';
    $html .= '<p>Chào <b>' . e($sv['ho_ten']) . '</b> (' . e($sv['ma_sv']) . ' – lớp ' . e($sv['ma_lop']) . '),</p>';
    $html .= '<p>Phòng Đào tạo gửi bạn kết quả học tập <b>học kỳ ' . e($hoc_ky) . '</b>:</p>';
    $html .= '<table style="border-collapse:collapse"><tr>';
    foreach (['#', 'Môn học', 'TC', 'CC', 'GK', 'CK', 'Hệ 10', 'Chữ', 'Hệ 4'] as $cot) {
        $html .= "<th $th>$cot</th>";
    }
    $html .= '</tr>';

    foreach ($ds_diem as $i => $d) {
        $html .= "<tr><td $td>" . ($i + 1) . "</td><td $td>" . e($d['ten_mh']) . "</td><td $giua>" . (int) $d['so_tin_chi'] . '</td>'
               . "<td $giua>" . fmt_diem($d['diem_chuyen_can']) . "</td><td $giua>" . fmt_diem($d['diem_giua_ky']) . '</td>'
               . "<td $giua>" . fmt_diem($d['diem_cuoi_ky']) . "</td><td $giua><b>" . fmt_diem($d['diem_tong']) . '</b></td>'
               . "<td $giua>" . diem_chu($d['diem_tong']) . "</td><td $giua>" . fmt_he_4($d['diem_tong']) . '</td></tr>';
    }

    $gpa = tinh_gpa($ds_diem);
    $html .= '</table><p>Điểm trung bình học kỳ (hệ 4): <b>' . ($gpa === null ? '-' : number_format($gpa, 2) . '</b> – ' . xep_loai_gpa($gpa)) . '</p>';
    $html .= '<p style="color:#666;font-size:12px">Đây là email tự động, vui lòng không trả lời.</p></div>';
    return $html;
}
