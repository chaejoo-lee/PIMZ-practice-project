<?php

namespace src\common;

class JsonResponse
{
    public static function send(int $status, string $message, mixed $body = null): never
    {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($status);

        $response = [
            'status' => $status,
            'message' => $message,
        ];

        // null이면 생략
        if ($body !== null) {
            $response['body'] = $body;
        }

        // 가독성이 향상
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}