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
];
