# API Documentation for Magazine AI System

Tài liệu này cung cấp thông tin về các API endpoints có sẵn trong Magazine AI System.

## Cách sử dụng

### Xem API Documentation trực tiếp

Bạn có thể xem tài liệu API trực tiếp bằng cách:

1. Mở file `index.html` trong trình duyệt.
2. Hoặc truy cập đường dẫn `/docs` nếu đã được cấu hình trong Laravel.

### Tệp Swagger

Tài liệu API được định nghĩa trong các tệp sau:

- `swagger.yaml`: Định nghĩa API ở định dạng YAML.
- `swagger.json`: Định nghĩa API ở định dạng JSON.

Bạn có thể sử dụng các tệp này với các công cụ Swagger khác như:
- [Swagger Editor](https://editor.swagger.io/)
- [Swagger UI](https://swagger.io/tools/swagger-ui/)
- [Postman](https://www.postman.com/)

## Các API Endpoint Chính

Tài liệu API đề cập đến các nhóm endpoint sau:

### Xác thực
- Đăng ký người dùng mới
- Đăng nhập
- Đăng xuất
- Xem thông tin profile

### Bài viết
- Xem danh sách bài viết
- Xem chi tiết bài viết
- Tìm kiếm bài viết
- Xem các bài viết mới nhất
- Import bài viết từ scraper

### Danh mục
- Xem danh sách danh mục
- Xem chi tiết danh mục
- Xem các bài viết trong danh mục

### Facebook Posts
- Xem danh sách bài đăng Facebook
- Scrape bài đăng Facebook
- Theo dõi trạng thái công việc scrape
- Quản lý bài đăng Facebook

### Quản lý Media
- Xem danh sách media
- Tải lên media mới
- Xem chi tiết media
- Xóa media

### Quản lý người dùng (Admin)
- Xem danh sách người dùng
- Tạo người dùng mới
- Cập nhật thông tin người dùng
- Xóa người dùng
- Cập nhật trạng thái người dùng

### Cài đặt AI
- Xem cài đặt AI
- Cập nhật cài đặt AI
- Kiểm tra kết nối AI
- Đặt lại cài đặt AI mặc định

## Xác thực

Đa số các API endpoint yêu cầu xác thực. Để xác thực, hãy:

1. Gửi yêu cầu đến `/api/auth/login` để nhận token.
2. Sử dụng token trong header `Authorization: Bearer {token}` cho các yêu cầu tiếp theo.

## Lưu ý

- Các API endpoints có tiền tố `/admin` chỉ có thể được truy cập bởi người dùng có vai trò `admin`.
- Các API endpoints không có tiền tố `/admin` có thể được truy cập bởi tất cả người dùng đã xác thực (trừ khi có quy định khác).
- Các API endpoints không yêu cầu xác thực đã được đánh dấu rõ ràng.

## Hỗ trợ và báo lỗi

Nếu bạn gặp vấn đề với API hoặc cần hỗ trợ thêm, vui lòng liên hệ với đội phát triển qua email hoặc tạo issue trên hệ thống quản lý dự án. 