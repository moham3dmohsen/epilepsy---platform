<?php
if (ob_get_level() === 0) {
    ob_start();
}

function jsonResponse(mixed $data, int $httpCode = 200): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
