<?php
require_once __DIR__ . '/JwtHelper.php';

class AuthMiddleware {
    public static function checkToken() {
        $headers = apache_request_headers();
        $authHeader = null;

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            self::unauthorized("Token no proporcionado o formato inválido.");
        }

        $token = $matches[1];
        $payload = JwtHelper::verifyToken($token);

        if (!$payload) {
            self::unauthorized("Token inválido o expirado.");
        }

        return $payload; // Devuelve el payload del token si es válido
    }

    private static function unauthorized($message) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => $message]);
        exit;
    }
}
?>
