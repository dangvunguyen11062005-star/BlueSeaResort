# BlueSea Resort – PHP + MySQL + XAMPP

Website đặt phòng và quản lý resort Phan Thiết – Mũi Né, dựng theo mockup.

## 1. Yêu cầu
- XAMPP (Apache + MySQL)
- PHP 8.x trở lên
- Visual Studio Code
- Trình duyệt Chrome/Edge

## 2. Cài đặt
1. Copy thư mục `BlueSeaResort` vào:
   `C:\xampp\htdocs\`
2. Mở XAMPP, Start **Apache** và **MySQL**.
3. Vào `http://localhost/phpmyadmin`.
4. Chọn Import và import lần lượt (đúng thứ tự):
   1. `database/bluesea_resort.sql` (tạo database, bảng và dữ liệu phòng/dịch vụ cơ bản)
   2. `database/demo_bookings.sql` — **tùy chọn**, thêm 3 khách hàng mẫu và ~190 đơn đặt phòng trong 12 tháng gần nhất để Dashboard/Thống kê/Lịch sử có số liệu giống mockup. Import sau file 1.
   - **Nếu bạn đã import `bluesea_resort.sql` từ bản trước** (không có chức năng Loại phòng / Hóa đơn / Khuyến mãi / Đánh giá / Cài đặt) và không muốn mất dữ liệu đang có: chọn database `bluesea_resort` → Import file **`database/upgrade_v2.sql`** (chỉ cần 1 lần, chạy lại nhiều lần cũng không lỗi). Ai cài mới hoàn toàn thì **không cần** file này.
   - ⚠️ Nếu import bằng dòng lệnh `mysql` thay vì phpMyAdmin, luôn thêm `--default-character-set=utf8mb4` để tránh lỗi hiển thị tiếng Việt (mojibake), ví dụ:
     `mysql --default-character-set=utf8mb4 -u root < database/bluesea_resort.sql`
   - **Nếu bạn đã có database từ các bản trước (v1/v2)**: import thêm **`database/upgrade_v3.sql`** (1 lần, chạy lại không lỗi). File này thêm bảng tin nhắn liên hệ, bảng chống đoán mật khẩu, trạng thái hoàn tiền `refunded` và sửa các đơn đã hủy nhưng còn ghi "paid". Admin sẽ hiện cảnh báo đỏ cho tới khi bạn import.
   - Muốn xóa dữ liệu demo: import lại `bluesea_resort.sql` (sẽ xóa toàn bộ và tạo lại từ đầu).
5. Mở VS Code:
   `File > Open Folder > C:\xampp\htdocs\BlueSeaResort`
6. Truy cập:
   `http://localhost/BlueSeaResort/`

## 3. Tài khoản demo
> ⚠️ **Các tài khoản dưới đây chỉ để chạy thử trên máy cá nhân và đã công khai trong README này.** Khi đưa website lên mạng, **đổi ngay** mật khẩu `admin123` / `staff123` (Admin → Cài đặt → Đổi mật khẩu), đổi hoặc xóa khách demo, và không import `demo_bookings.sql`. Hệ thống sẽ nhắc trên màn hình mỗi lần đăng nhập admin bằng mật khẩu mặc định.

### Khách hàng
Email: `nguyenvana@gmail.com` — Mật khẩu: `123456`
(3 khách khác nếu đã import `demo_bookings.sql`: `tranthib@gmail.com`, `leminhc@gmail.com`, `phamquocd@gmail.com`, cùng mật khẩu `123456`)

### Admin — 2 vai trò (RBAC)
| Vai trò | Email | Mật khẩu | Quyền |
|---|---|---|---|
| **Administrator** | `admin@bluesearesort.vn` | `admin123` | Toàn quyền, bao gồm Loại phòng, Dịch vụ, Khuyến mãi, Cài đặt |
| **Staff** (nhân viên lễ tân) | `staff@bluesearesort.vn` | `staff123` | Dashboard, Quản lý phòng (đổi trạng thái), Đặt phòng, Khách hàng, Hóa đơn, Đánh giá, Thống kê — **không** được vào Loại phòng/Dịch vụ/Khuyến mãi/Cài đặt (menu hiện khóa 🔒, vào thẳng URL cũng bị chặn ở backend) |

> Vì có thêm yêu cầu bảo mật, trang đăng nhập admin **không còn tự điền sẵn** email/mật khẩu như bản trước — phải tự nhập.

## 4. Chức năng
**Khách hàng:** Trang chủ / tìm phòng · Danh sách & chi tiết phòng (có đánh giá thật) · Đăng ký / đăng nhập · Đặt phòng (có ô nhập mã khuyến mãi) · Chọn dịch vụ thêm · Thanh toán bằng **tiền mặt tại resort** hoặc **chuyển khoản ngân hàng** kèm **mã QR (VietQR) quét bằng app ngân hàng thật** (cả hai đều cần nhân viên/admin xác nhận đã nhận tiền) · Lịch sử đặt phòng · Viết đánh giá sau khi trả phòng · Đổi thông tin cá nhân / mật khẩu

**Admin:** Dashboard · Quản lý phòng · Quản lý loại phòng · Quản lý đặt phòng · Quản lý khách hàng · Quản lý dịch vụ · Quản lý hóa đơn (xem & in) · Khuyến mãi · Duyệt đánh giá · Thống kê · Cài đặt thông tin resort & đổi mật khẩu

**Phòng trống:** chọn ngày ở trang Phòng nghỉ để thấy "Còn N phòng / Hết phòng"; trang Đặt phòng tự kiểm tra khi đổi ngày và **gợi ý khoảng ngày gần nhất còn phòng** (cùng số đêm) khi hết phòng.

**REST API** (song song với website, xem mục 7): đăng ký/đăng nhập, danh sách & chi tiết phòng, kiểm tra phòng trống theo ngày (`availability.php`), kiểm tra mã khuyến mãi, tạo & xem đơn đặt phòng, đánh giá.

## 5. Bảo mật (đáp ứng tiêu chuẩn nghiệm thu)
- **Mật khẩu:** băm bằng `password_hash()` (bcrypt), không còn lưu dạng SHA2 thuần. Tài khoản tạo từ các bản cũ (hash SHA2) vẫn đăng nhập được và **tự động nâng cấp** lên bcrypt ngay sau lần đăng nhập thành công đầu tiên — không cần làm gì thêm.
- **Session:** `session_regenerate_id()` sau mỗi lần đăng nhập (khách & admin), chống session fixation.
- **CSRF:** mọi form POST (đặt phòng, thanh toán, đăng nhập/đăng ký, toàn bộ thao tác thêm/sửa/xóa trong admin...) đều có token ẩn, kiểm tra bằng `require_csrf()`. Submit thiếu token hoặc token sai → bị từ chối, quay lại trang với thông báo.
- **Phân quyền (RBAC):** 2 vai trò admin (`admin`/`staff`), kiểm tra ở backend bằng `require_admin('admin')`, không chỉ ẩn ở giao diện.
- **Validate dữ liệu:** email dùng `filter_var(FILTER_VALIDATE_EMAIL)` ở server (không chỉ dựa vào `type="email"` của trình duyệt), số điện thoại, ngày tháng, số khách... đều được kiểm tra trước khi ghi database.
- **Chống đoán mật khẩu:** đăng nhập khách, đăng nhập admin và `api/login.php` đếm số lần SAI theo email (5 lần) và theo IP (20 lần) trong 15 phút, vượt ngưỡng thì khóa tạm (API trả 429 kèm `Retry-After`). Đăng nhập đúng xóa bộ đếm của email đó. Ngưỡng chỉnh ở `includes/rate_limit.php`. Form liên hệ giới hạn 5 tin/giờ/IP và có ô bẫy bot.
- **Cookie phiên:** `HttpOnly`, `SameSite=Lax`, `Secure` (tự bật khi chạy HTTPS), chế độ strict mode. Session không còn chứa hash mật khẩu.
- **Header bảo mật:** `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy` (không cho script inline/script ngoài) và HSTS khi chạy HTTPS. Vì vậy **đừng viết `onclick=`/`<script>` inline**: đặt JS trong `assets/js/main.js` (đã có sẵn `data-autosubmit`, `data-print`).
- **Đặt phòng không trùng:** chọn phòng trống và ghi đơn nằm trong một transaction, khóa dòng phòng bằng `SELECT ... FOR UPDATE` (xem `create_booking_atomic()` trong `includes/booking_service.php`). Mã đơn sinh bằng `random_bytes`.
- **Giữ phòng có thời hạn:** đơn `pending` tự hủy khi chưa chọn thanh toán sau 24 giờ, chuyển khoản chưa được xác nhận sau 48 giờ, hoặc đã qua ngày nhận phòng (đơn "Tiền mặt" được giữ tới ngày nhận phòng vì khách trả tại quầy). Chỉnh ở đầu `includes/booking_service.php`. Hệ thống tự dọn khi có người xem phòng/đặt phòng; nên thêm cron cho đều: `*/15 * * * * php /đường/dẫn/cron/expire_bookings.php`.
- **Trạng thái đơn & hoàn tiền:** chỉ cho các bước hợp lệ (pending → hủy; confirmed → check-in/hủy; check-in → trả phòng). Hủy đơn đã thu tiền chỉ dành cho Administrator và sẽ ghi nhận `refunded` (loại khỏi doanh thu, hóa đơn có nhãn "Đã hoàn tiền"); việc trả tiền thật cho khách làm ngoài hệ thống.
- **Múi giờ:** `config/app.php` đặt `Asia/Ho_Chi_Minh`, `config/database.php` đồng bộ múi giờ phiên MySQL (`+07:00`) nên `CURDATE()`/`NOW()` luôn khớp PHP dù hosting để UTC.
- **Xử lý ngoại lệ toàn cục:** `config/app.php` đăng ký `set_error_handler` / `set_exception_handler` / `register_shutdown_function`. Mọi lỗi PHP/SQL không mong muốn được ghi vào `logs/app.log`, người dùng chỉ thấy trang "Rất tiếc, có lỗi xảy ra" — không lộ đường dẫn file, câu SQL hay stack trace. Thư mục `logs/` có `.htaccess` chặn truy cập trực tiếp từ trình duyệt.

## 6. Cơ sở dữ liệu
- Các khóa ngoại đều được đặt tên rõ ràng (`fk_bookings_customer`, `fk_rooms_room_type`...) kèm `ON DELETE RESTRICT` (không cho xóa loại phòng/khách hàng còn dữ liệu liên quan) hoặc `ON DELETE CASCADE` (xóa đơn thì dịch vụ/thanh toán đi kèm cũng được xóa) — xem chi tiết trong `database/bluesea_resort.sql`.
- Seed data thực tế: 5 loại phòng, 8 phòng, 5 dịch vụ, 3 khuyến mãi, và (nếu import `demo_bookings.sql`) ~190 đơn đặt phòng trong 12 tháng + 42 đánh giá mẫu.

## 7. REST API (`/api`)
Tầng API JSON độc lập, xác thực bằng Bearer token, phục vụ tích hợp (app di động, hệ thống khác...).
Tài liệu đầy đủ: **`docs/API.md`** (đọc trực tiếp), **`docs/openapi.yaml`** (dán vào https://editor.swagger.io để xem dạng Swagger UI), **`docs/postman_collection.json`** (import vào Postman).

```bash
curl -X POST http://localhost/BlueSeaResort/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"nguyenvana@gmail.com","password":"123456"}'
```

## 8. Triển khai lên máy chủ thật (checklist)
1. **Cấu hình bằng file riêng, không sửa mã nguồn:** sao chép `config/local.example.php` thành `config/local.php` (đã nằm trong `.gitignore`) rồi điền `DB_*`, `BASE_URL` (`''` nếu site ở gốc domain), `SITE_URL` (URL đầy đủ, dùng cho canonical/OpenGraph) và `APP_ENV => 'production'`. Cũng có thể đặt cùng tên bằng biến môi trường. Khi `APP_ENV=production`, site từ chối chạy nếu DB dùng `root` hoặc mật khẩu rỗng.
2. Tạo user MySQL riêng chỉ có quyền trên database `bluesea_resort`; **không** dùng `root`.
3. Đổi mật khẩu admin/staff mặc định, không import `demo_bookings.sql`, nhập đúng tài khoản ngân hàng ở Admin → Cài đặt.
4. Bật HTTPS (cookie `Secure` và HSTS tự bật theo). Nếu đứng sau proxy/CDN của bạn, đặt `TRUST_PROXY=1` để lấy đúng IP khách cho bộ đếm đăng nhập sai.
5. Dùng Apache có `AllowOverride All` để các file `.htaccess` có hiệu lực: chặn tải `.sql/.log/.md/local.php`, chặn chạy PHP trong `assets/images/uploads/`, chặn xem `logs/`. Với Nginx cần viết quy tắc tương đương.
6. Thêm cron dọn đơn quá hạn (xem mục 5) và sao lưu database định kỳ.
7. `logs/app.log` và ảnh trong `assets/images/uploads/` đã được `.gitignore`; không commit lại.

## 9. Lưu ý
- Hệ thống chỉ hỗ trợ 2 phương thức thanh toán: **Tiền mặt (tại resort)** và **Chuyển khoản ngân hàng**. VNPay/MoMo đã được gỡ khỏi giao diện và bị từ chối ở backend (chưa kết nối cổng thanh toán thật).
- **Luồng thanh toán (cả Tiền mặt và Chuyển khoản):** khách chọn phương thức và bấm "Thanh toán ngay" → đơn chuyển sang **"Chờ thanh toán tại resort"** (tiền mặt) hoặc **"Chờ xác nhận chuyển khoản"** (chuyển khoản); chưa tính doanh thu, chưa có hóa đơn. Nhân viên lễ tân (Staff) hoặc Administrator nhận được tiền (tại quầy hoặc về tài khoản) rồi vào **Admin → Quản lý đặt phòng**, bấm nút ✔ "Xác nhận đã nhận tiền" → đơn mới chuyển sang **Đã xác nhận / Đã thanh toán** và phát sinh hóa đơn. Thao tác xác nhận an toàn khi bấm lặp lại (không tạo trùng hóa đơn).
- **Chuyển khoản ngân hàng** tạo mã **QR thật theo chuẩn VietQR** (tự dựng chuỗi EMVCo + CRC16, vẽ SVG bằng thư viện PHP thuần `includes/qrcode/qrcode.php` — không gọi ảnh/API qua internet). Vào **Admin → Cài đặt**, mục "Tài khoản nhận chuyển khoản", nhập đúng số tài khoản thật + chọn ngân hàng thì mã QR sẽ quét được bằng app ngân hàng thật (chuyển đúng số tiền + nội dung "TT <mã đơn>" tự động). Việc xác nhận "đã thanh toán" là **thủ công do nhân viên/admin thực hiện** (xem mục trên) vì dự án không có quyền truy cập API đối soát của ngân hàng thật.
- Hình ảnh trong bản source là SVG minh họa để dự án chạy ngay không cần tải ảnh ngoài.
- Đổi `BASE_URL` trong `config/app.php` nếu bạn đổi tên thư mục project.
- API hiện phục vụ luồng khách hàng (đăng nhập, phòng, đặt phòng, đánh giá); các trang quản trị (admin) chưa có endpoint API riêng, vẫn dùng giao diện web.
