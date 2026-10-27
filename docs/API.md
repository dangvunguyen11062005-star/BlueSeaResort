# BlueSea Resort — Tài liệu REST API

API này chạy **song song** với website chính (website vẫn render HTML truyền thống
qua PHP như trước). API nằm trong thư mục `/api`, dùng chung 1 database, phục vụ
cho việc tích hợp với app di động / hệ thống khác, và đáp ứng tiêu chuẩn "chuẩn hóa
API + có tài liệu đi kèm" của buổi nghiệm thu.

- **Base URL (local):** `http://localhost/BlueSeaResort/api`
- **Định dạng:** JSON (`Content-Type: application/json`) cho cả request và response.
- **Xác thực:** Bearer token (không dùng session/cookie). Lấy token từ `POST /login.php`,
  gửi kèm mọi request cần đăng nhập bằng header:
  ```
  Authorization: Bearer <token>
  ```
  Token có hạn **30 ngày**, hoặc hết hiệu lực ngay khi gọi `POST /logout.php`.
- **Định dạng lỗi** (thống nhất cho mọi endpoint):
  ```json
  { "error": { "code": "validation_error", "message": "Email không đúng định dạng." } }
  ```
- **Định dạng thành công:** `{ "data": { ... } }`
- Toàn bộ lỗi hệ thống không mong muốn (SQL, PHP...) được bắt và ghi vào `logs/app.log`,
  người gọi API chỉ nhận `500 { "error": { "code": "server_error", ... } }`, không lộ chi tiết.

## Tài liệu máy đọc được (import vào công cụ)
| File | Dùng với |
|---|---|
| `docs/openapi.yaml` | Swagger UI / Swagger Editor (https://editor.swagger.io, dán nội dung file vào) |
| `docs/postman_collection.json` | Postman: File → Import → chọn file này |

## Danh sách endpoint

| Method | Endpoint | Cần token? | Mô tả |
|---|---|---|---|
| POST | `/register.php` | Không | Tạo tài khoản khách hàng mới |
| POST | `/login.php` | Không | Đăng nhập, nhận token |
| POST | `/logout.php` | Có | Thu hồi token hiện tại |
| GET | `/me.php` | Có | Thông tin khách đang đăng nhập |
| GET | `/rooms.php` | Không | Danh sách loại phòng (lọc `guests`, `min_price`, `max_price`) |
| GET | `/room_detail.php?id=` | Không | Chi tiết 1 loại phòng + đánh giá |
| GET | `/availability.php?room_type_id=&check_in=&check_out=` | Không | Kiểm tra loại phòng còn trống trong khoảng ngày; hết phòng thì kèm `suggestions` (ngày thay thế gần nhất) |
| POST | `/promo_check.php` | Không | Kiểm tra mã khuyến mãi |
| GET | `/bookings.php` | Có | Danh sách đơn của chính mình |
| POST | `/bookings.php` | Có | Tạo đơn đặt phòng mới |
| GET | `/booking_detail.php?id=` | Có | Chi tiết 1 đơn (phải là đơn của mình) |
| GET | `/bank_qr.php?booking_id=` | Có | Chuỗi VietQR (EMV QR) để tự vẽ mã QR chuyển khoản cho đơn đó |
| GET | `/reviews.php?room_type_id=` | Không | Đánh giá đã duyệt của 1 loại phòng |
| POST | `/reviews.php` | Có | Gửi đánh giá cho đơn đã trả phòng |

## Ví dụ dùng curl

```bash
# 1. Đăng nhập, lấy token
curl -X POST http://localhost/BlueSeaResort/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"nguyenvana@gmail.com","password":"123456"}'

# 2. Dùng token để tạo đơn đặt phòng
curl -X POST http://localhost/BlueSeaResort/api/bookings.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token_vừa_nhận>" \
  -d '{"room_id":2,"check_in":"2026-12-10","check_out":"2026-12-13","guests":2,"promo_code":"WELCOME10"}'
```

## Mã lỗi HTTP dùng trong API

| HTTP | Khi nào |
|---|---|
| 200 / 201 | Thành công |
| 401 | Thiếu/sai token, hoặc sai email-mật khẩu |
| 404 | Không tìm thấy (phòng, đơn...) hoặc đơn không thuộc về bạn |
| 405 | Gọi sai method (ví dụ GET vào endpoint chỉ nhận POST) |
| 409 | Xung đột nghiệp vụ (email đã dùng, hết phòng, đã đánh giá rồi...) |
| 422 | Dữ liệu đầu vào không hợp lệ |
| 503 | Chức năng cần nâng cấp database (`database/upgrade_v2.sql`) chưa được bật |
| 500 | Lỗi hệ thống (đã được ghi log, không lộ chi tiết) |

## Lưu ý phạm vi (được nói rõ, không tô vẽ)
- API này cho phần **đặt phòng của khách hàng** (auth, rooms, bookings, reviews, khuyến mãi) —
  là luồng nghiệp vụ cốt lõi của đề tài. Các trang quản trị (admin) hiện chưa có API riêng,
  vẫn thao tác trực tiếp qua giao diện web (có CSRF + phân quyền riêng, xem `README.md`).
- Website hiện tại **chưa gọi** các endpoint này (vẫn render HTML server-side như cũ);
  API là một tầng độc lập dùng chung database, sẵn cho việc tích hợp thêm (app mobile, v.v.)
