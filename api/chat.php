<?php
/**
 * POST /api/chat.php   (Chatbot AI tư vấn khách hàng - công khai, không cần token API)
 * Header: X-CSRF-Token (lấy từ trang web), Content-Type: application/json
 * Body : { "messages": [ {"role":"user","content":"..."}, {"role":"assistant","content":"..."}, ... ] }
 * 200  -> { "data": { "reply": "...", "ai": true|false } }
 *   ai=false nghĩa là chưa cấu hình AI_API_KEY (hoặc AI lỗi) nên trả lời bằng bộ luật dự phòng dựa trên dữ liệu thật.
 * 429  -> gửi quá nhiều (30 lượt/giờ/IP)
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/booking_service.php';
require_once __DIR__ . '/../includes/ai.php';
api_method_guard('POST');

api_run(function () {
    global $pdo;
    ai_require_csrf_header();
    $body = api_body();
    $history = ai_clean_history($body['messages'] ?? []);
    if (!$history) api_error('Vui lòng nhập câu hỏi.', 422, 'validation_error');
    if (!rate_limit_allow($pdo, 'ai_chat', 30, 3600)) {
        api_error('Bạn đã hỏi quá nhiều trong thời gian ngắn. Vui lòng thử lại sau hoặc gọi lễ tân ' . setting('phone', '0987 654 321') . '.', 429, 'rate_limited');
    }
    $question = $history[count($history) - 1]['content'];
    $usedAi = false;

    if (ai_enabled()) {
        try {
            $reply = ai_converse(ai_chat_system_prompt(), $history, ai_chat_tools(), ai_chat_tool_handler($pdo), ai_model());
            $usedAi = $reply !== '';
        } catch (Throwable $ex) {
            error_log('[ai][chat] ' . $ex->getMessage());
            $reply = '';
        }
    } else {
        $reply = '';
    }
    if ($reply === '') $reply = ai_chat_fallback($pdo, $question);

    ai_log($pdo, 'chat', $question, $reply, $usedAi);
    api_json(['data' => ['reply' => $reply, 'ai' => $usedAi]]);
});
