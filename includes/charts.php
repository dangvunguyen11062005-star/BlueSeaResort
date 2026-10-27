<?php
/* Biểu đồ vẽ bằng SVG/CSS thuần – không cần thư viện ngoài, chạy offline */

/* Làm tròn trục Y để chia đúng 4 khoảng */
function nice_max($max) {
    if ($max <= 0) return 4000000;
    $unit = pow(10, floor(log10($max / 4)));
    foreach ([1, 2, 2.5, 5, 10] as $m) {
        if ($m * $unit * 4 >= $max) return $m * $unit * 4;
    }
    return 10 * $unit * 4;
}

function axis_label($v) {
    if ($v <= 0) return '0';
    if ($v >= 1000000) return rtrim(rtrim(number_format($v / 1000000, 1, '.', ''), '0'), '.') . 'M';
    return number_format($v / 1000, 0, '.', '') . 'K';
}

/* Biểu đồ đường + vùng tô (doanh thu theo ngày) */
function line_chart_svg(array $labels, array $values) {
    $W = 560; $H = 250; $L = 46; $R = 16; $T = 16; $B = 34;
    $max = nice_max(max($values ?: [0]));
    $n = max(1, count($values) - 1);
    $pw = $W - $L - $R; $ph = $H - $T - $B;
    $pts = [];
    foreach ($values as $i => $v) {
        $pts[] = [$L + $pw * ($i / $n), $T + $ph * (1 - $v / $max), $v];
    }
    $line = implode(' ', array_map(function ($p) { return round($p[0], 1) . ',' . round($p[1], 1); }, $pts));
    $area = $line . ' ' . round($L + $pw, 1) . ',' . ($T + $ph) . ' ' . $L . ',' . ($T + $ph);
    $s = '<svg class="line-chart" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="Biểu đồ doanh thu 7 ngày gần nhất">';
    $s .= '<defs><linearGradient id="lg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1e9bfe" stop-opacity=".28"/><stop offset="1" stop-color="#1e9bfe" stop-opacity="0"/></linearGradient></defs>';
    for ($g = 0; $g <= 4; $g++) {
        $y = $T + $ph * (1 - $g / 4);
        $s .= '<line x1="' . $L . '" x2="' . ($W - $R) . '" y1="' . round($y, 1) . '" y2="' . round($y, 1) . '" stroke="#e3eef5" stroke-dasharray="' . ($g ? '3 4' : '0') . '"/>';
        $s .= '<text x="' . ($L - 8) . '" y="' . round($y + 4, 1) . '" text-anchor="end">' . axis_label($max * $g / 4) . '</text>';
    }
    $s .= '<polygon points="' . $area . '" fill="url(#lg)"/>';
    $s .= '<polyline points="' . $line . '" fill="none" stroke="#1e9bfe" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';
    foreach ($pts as $i => $p) {
        $s .= '<circle cx="' . round($p[0], 1) . '" cy="' . round($p[1], 1) . '" r="4" fill="#fff" stroke="#1e9bfe" stroke-width="2.5"><title>' . e($labels[$i]) . ': ' . money($p[2]) . '</title></circle>';
        $s .= '<text x="' . round($p[0], 1) . '" y="' . ($H - 10) . '" text-anchor="middle">' . e($labels[$i]) . '</text>';
    }
    return $s . '</svg>';
}

/* Trả về chuỗi conic-gradient cho biểu đồ tròn */
function pie_gradient(array $percents, array $colors) {
    $acc = 0; $parts = [];
    foreach ($percents as $i => $p) {
        $start = $acc; $acc += $p;
        $parts[] = $colors[$i % count($colors)] . ' ' . round($start, 2) . '% ' . round($acc, 2) . '%';
    }
    return $parts ? 'conic-gradient(' . implode(',', $parts) . ')' : '#e3eef5';
}
?>
