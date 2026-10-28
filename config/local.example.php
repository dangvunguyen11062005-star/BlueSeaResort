<?php
/**
 * Sao chép file này thành config/local.php rồi sửa giá trị cho máy chủ thật.
 * config/local.php nằm trong .gitignore nên mật khẩu DB không bị đưa lên Git.
 * Cũng có thể đặt các giá trị này bằng biến môi trường cùng tên.
 */
return [
    'APP_ENV'      => 'production',                 // 'production' sẽ từ chối chạy với DB root không mật khẩu
    'BASE_URL'     => '',                           // '' nếu site nằm ở gốc domain; '/BlueSeaResort' nếu nằm trong thư mục con
    'SITE_URL'     => 'https://ten-mien-cua-ban.vn', // URL tuyệt đối (kèm thư mục con nếu có), dùng cho canonical / OpenGraph
    'APP_TIMEZONE' => 'Asia/Ho_Chi_Minh',

    'DB_HOST' => 'localhost',
    'DB_NAME' => 'bluesea_resort',
    'DB_USER' => 'bluesea_app',                     // tạo user riêng, chỉ cấp quyền trên DB này
    'DB_PASS' => 'doi-mat-khau-manh-o-day',

    // --- Tích hợp AI (chatbot tư vấn khách hàng). Để trống AI_API_KEY = tắt AI, website vẫn chạy ---
    // Cách 1: Google Gemini (có gói MIỄN PHÍ, không cần thẻ). Lấy khóa tại https://aistudio.google.com/apikey
    //   Lưu ý: gói miễn phí có thể bị Google dùng dữ liệu để cải thiện sản phẩm -> đừng dùng cho dữ liệu khách thật; có giới hạn số lượt/ngày.
    // 'AI_PROVIDER'    => 'gemini',
    // 'AI_MODEL'       => 'gemini-3.5-flash-lite',
    // Cách 2: Claude (trả phí theo lượt). Khóa tại https://console.anthropic.com
    'AI_PROVIDER'    => 'anthropic',
    'AI_API_KEY'     => '',                         // KHÔNG commit khóa thật
    'AI_MODEL'       => 'claude-haiku-5-5',         // chatbot (nhanh, rẻ)
];
