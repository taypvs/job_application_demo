# 求人管理 Kanri

Ứng viên và quản trị viên dùng chung một hệ thống Laravel. Giao diện trên web là tiếng Nhật. MySQL giữ dữ liệu nghiệp vụ. Object storage dùng API S3; trong Docker đó là [Silo](https://github.com/pgsty/silo) (tương thích MinIO) vì image chính thức của MinIO không còn pull công khai.

## Chạy

```bash
make init
```

Ứng viên và quản trị chạy hai cổng để đăng nhập song song không mất session. Trình duyệt không tách cookie theo cổng trên cùng `localhost`, nên mỗi cổng dùng một cookie riêng (`candidate_session` và `admin_session`).

| Vai trò | URL | Email | Mật khẩu |
| --- | --- | --- | --- |
| Ứng viên | http://localhost:8080 | user@example.com | password |
| Ứng viên đã có lịch phỏng vấn | http://localhost:8080 | yamada@example.com | password |
| Quản trị | http://localhost:8081 | admin@example.com | password |

Lệnh khác: `make seed`, `make fresh`, `make test`, `make shell`, `make logs`, `make down`. `make reset` xóa volume Docker rồi dựng lại. `make test` dùng SQLite trong bộ nhớ, không đụng database demo.

Console S3: http://localhost:9001 (minio / minio12345). API: http://localhost:9000. MySQL từ máy host: `127.0.0.1:3307`, database `jobapply`, user `jobapply`, password `secret`.

## Biến môi trường

S3 trỏ vào MinIO qua `AWS_*` trong `.env`. OpenAI và Pinecone để trống thì hệ thống vẫn chạy:

- Phân tích matching dùng đối chiếu kỹ năng trong CV với skill của tin tuyển dụng.
- Chatbot tìm đoạn văn đã lưu trong MySQL.
- Khi có `OPENAI_API_KEY`, CV được cấu trúc hóa và matching do model viết.
- Khi có cả `OPENAI_API_KEY` và `PINECONE_HOST` (index 1536 chiều, khớp `text-embedding-3-small`), tài liệu được embed và upsert vào namespace `jobapply`.

Prompt nằm ở `config/prompts.php`, đọc qua `App\Services\Ai\PromptLibrary`.

## Vì sao xếp lịch phỏng vấn không dùng AI agent

Việc này là quy tắc cố định: lấy manager của bộ phận gắn với tin, trừ các khung đã có lịch, chọn khung sớm nhất trong giờ làm việc. Code thường trong `InterviewSchedulerService` cho kết quả ổn định và giải thích được. Agent chỉ thêm độ trễ và rủi ro chọn nhầm giờ. Sau khi gán lịch, hệ thống ghi một tin vào hộp thư trong tài khoản ứng viên.

## Database

Bảng queue sẵn có của Laravel tên `jobs` được giữ nguyên. Tin tuyển dụng nằm ở `job_postings`.

- `users.role`: `candidate` hoặc `admin`.
- `departments` và `managers`: bộ phận và người phỏng vấn. `manager_availabilities` là khung giờ lặp theo thứ (1 = thứ Hai … 5 = thứ Sáu).
- `companies`: hồ sơ công ty dùng chung cho nhiều tin.
- `job_postings`: nội dung tin (mô tả, lương theo yên, địa điểm, hình thức làm việc, trạng thái). `job_skills.category` là `required` hoặc `nice_to_have`.
- `cvs`: file trên S3 (`s3_key`), `extracted_text`, và `parsed_profile` (JSON: summary, skills, experiences, education). `is_active` đánh dấu CV đang dùng.
- `applications`: một ứng viên một tin. Status `applied`, `interview`, `rejected`, `passed`.
- `match_analyses`: điểm, điểm mạnh, điểm yếu, việc cần cải thiện, và `engine` (`openai` hoặc `local`) để lần sau đọc lại không cần gọi model.
- `interviews`: một buổi cho một hồ sơ, gắn manager và khoảng thời gian. Lịch đã `scheduled` bị loại khi tìm khung trống.
- `mailbox_messages`: hộp thư trong tài khoản ứng viên (không phải email SMTP).
- `knowledge_documents` và `document_chunks`: tài liệu admin upload. Chunk lưu nội dung để chat vẫn trả lời khi Pinecone chưa cấu hình. `pinecone_id` nối với vector.
- `chat_messages`: lịch sử chatbot của ứng viên.
