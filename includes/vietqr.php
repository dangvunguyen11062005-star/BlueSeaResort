<?php
/**
 * VietQR / EMVCo QR Code cho thanh toán chuyển khoản ngân hàng.
 * Tự dựng chuỗi theo chuẩn "VietQR" (Napas 247 - EMV QR Code), tính CRC16,
 * rồi vẽ thành mã QR dạng SVG bằng thư viện includes/qrcode/qrcode.php.
 * Không gọi bất kỳ API/ảnh nào qua internet - chạy offline hoàn toàn.
 *
 * Tham khảo: EMVCo QR Code specification + tài liệu kỹ thuật VietQR của Napas
 * (cấu trúc TLV: tag 2 số + độ dài 2 số + giá trị).
 */

/** Danh sách ngân hàng hay dùng kèm mã BIN (theo chuẩn Napas). Admin chọn trong Cài đặt. */
function vietqr_bank_list() {
    return [
        '970436' => 'Vietcombank',
        '970415' => 'VietinBank',
        '970418' => 'BIDV',
        '970405' => 'Agribank',
        '970407' => 'Techcombank',
        '970422' => 'MB Bank',
        '970432' => 'VPBank',
        '970423' => 'TPBank',
        '970403' => 'Sacombank',
        '970416' => 'ACB',
        '970441' => 'VIB',
        '970437' => 'HDBank',
        '970448' => 'OCB',
        '970454' => 'SCB',
    ];
}

/** Bỏ dấu tiếng Việt + chỉ giữ ký tự an toàn cho field QR (chuẩn yêu cầu không dấu, in hoa) */
function vietqr_ascii_upper($str) {
    $str = (string)$str;
    if (function_exists('transliterator_transliterate')) {
        $t = @transliterator_transliterate('Any-Latin; Latin-ASCII;', $str);
        if ($t !== false) $str = $t;
    } else {
        $map = ['à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a','è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e','ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i','ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o','ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u','ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d'];
        $str = strtr(mb_strtolower($str, 'UTF-8'), $map);
    }
    $str = preg_replace('/[^A-Za-z0-9 .\/\-]/', '', $str);
    return strtoupper(trim(preg_replace('/\s+/', ' ', $str)));
}

function vietqr_tlv($tag, $value) {
    return $tag . str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

/** CRC16-CCITT (False): poly 0x1021, init 0xFFFF - đúng chuẩn EMVCo */
function vietqr_crc16($data) {
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($data); $i < $n; $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

/**
 * Dựng chuỗi VietQR (EMV QR Code). $amount = 0 => QR tĩnh (khách tự nhập số tiền).
 * $message: nội dung chuyển khoản (sẽ tự bỏ dấu, viết hoa, cắt gọn).
 */
function vietqr_payload($bankBin, $accountNo, $amount, $message, $merchantName = 'BLUESEA RESORT') {
    $bankBin = preg_replace('/\D/', '', (string)$bankBin);
    $accountNo = preg_replace('/[^A-Za-z0-9]/', '', (string)$accountNo);
    $amount = max(0, (int)$amount);
    $message = mb_substr(vietqr_ascii_upper($message), 0, 50);
    $merchantName = mb_substr(vietqr_ascii_upper($merchantName), 0, 25);

    $beneficiary = vietqr_tlv('00', $bankBin) . vietqr_tlv('01', $accountNo);
    $merchantAccountInfo = vietqr_tlv('00', 'A000000727') . vietqr_tlv('01', $beneficiary) . vietqr_tlv('02', 'QRIBFTTA');
    $additionalData = $message !== '' ? vietqr_tlv('08', $message) : '';

    $payload = vietqr_tlv('00', '01')
             . vietqr_tlv('01', $amount > 0 ? '12' : '11')
             . vietqr_tlv('38', $merchantAccountInfo)
             . vietqr_tlv('53', '704')
             . ($amount > 0 ? vietqr_tlv('54', (string)$amount) : '')
             . vietqr_tlv('58', 'VN')
             . vietqr_tlv('59', $merchantName !== '' ? $merchantName : 'BLUESEA RESORT')
             . vietqr_tlv('60', 'VIETNAM')
             . ($additionalData !== '' ? vietqr_tlv('62', $additionalData) : '');

    $payload .= '6304';
    $payload .= vietqr_crc16($payload);
    return $payload;
}

/** Vẽ chuỗi bất kỳ thành mã QR dạng SVG (inline, không phụ thuộc ảnh ngoài) */
function vietqr_svg($text, $pixelSize = 6, $ecc = 'qrm') {
    require_once __DIR__ . '/qrcode/qrcode.php';
    $qr = new QRCode($text, ['s' => $ecc]);
    $m = $qr->get_matrix();
    $size = $m['size'];
    [$qt, $qr_, $qb, $ql] = $m['quiet'];
    $total = $size + $ql + $qr_;
    $px = $pixelSize;
    $w = $total * $px;

    $out = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $w . '" width="' . $w . '" height="' . $w . '" shape-rendering="crispEdges" role="img" aria-label="Mã QR chuyển khoản">';
    $out .= '<rect width="' . $w . '" height="' . $w . '" fill="#fff"/>';
    $path = '';
    foreach ($m['modules'] as $y => $row) {
        foreach ($row as $x => $dark) {
            if ($dark) {
                $path .= 'M' . (($x + $ql) * $px) . ' ' . (($y + $qt) * $px) . 'h' . $px . 'v' . $px . 'h-' . $px . 'z';
            }
        }
    }
    $out .= '<path d="' . $path . '" fill="#0a344b"/></svg>';
    return $out;
}
