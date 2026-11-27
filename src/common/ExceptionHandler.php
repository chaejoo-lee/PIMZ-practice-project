<?php

// 글로벌 예외 핸들러
set_exception_handler(function (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }

    // 상태 코드(400~599)면 그대로 쓰고, 아니면 500
    $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    error_log($e->getMessage());

    echo json_encode([
        'code' => $code,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

?>