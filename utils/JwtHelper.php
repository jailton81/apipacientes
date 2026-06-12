<?php
class JwtHelper {
    private static function loadEnv() {
        if (getenv('JWT_SECRET')) return;
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                putenv(trim($name) . '=' . trim($value));
            }
        }
    }

    public static function createToken($payload) {
        self::loadEnv();
        $key = getenv('JWT_SECRET') ?: 'default_secret';
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $headerEnc = self::base64UrlEncode($header);
        $payloadEnc = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "$headerEnc.$payloadEnc", $key, true);
        $signatureEnc = self::base64UrlEncode($signature);
        return "$headerEnc.$payloadEnc.$signatureEnc";
    }

    public static function verifyToken($token) {
        self::loadEnv();
        $key = getenv('JWT_SECRET') ?: 'default_secret';
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($headerEnc, $payloadEnc, $signatureEnc) = $parts;
        $signature = self::base64UrlDecode($signatureEnc);
        $expectedSignature = hash_hmac('sha256', "$headerEnc.$payloadEnc", $key, true);

        if (hash_equals($expectedSignature, $signature)) {
            $payload = json_decode(self::base64UrlDecode($payloadEnc), true);
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false; // Token expirado
            }
            return $payload;
        }

        return false;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
?>
