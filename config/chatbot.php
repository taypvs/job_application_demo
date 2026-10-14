<?php

return [

    /*
    | Số đoạn Pinecone trả về, xếp từ giống câu hỏi nhất.
    | API không nhận ngưỡng điểm, chỉ nhận topK.
    */
    'top_k' => 10,

    /*
    | Chỉ giữ đoạn có score lớn hơn hoặc bằng ngưỡng này.
    | Index cosine: gần 1 là giống hơn. Log hiện tại khoảng 0.18–0.30.
    */
    'min_score' => 0.25,

    /*
    | Độ dài mỗi đoạn khi cắt tài liệu upload, tính bằng ký tự.
    | Đoạn sau chồng 100 ký tự với đoạn trước.
    */
    'chunk_size' => 300,

];
