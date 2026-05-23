<?php
class Database {
    private $conexion;

    public function __construct() {
        $this->loadEnv();
    }

    private function loadEnv() {
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

    public function getConnection() {
        $this->conexion = null;

        $username = getenv('DB_USERNAME');
        $password = getenv('DB_PASSWORD');
        $database = getenv('DB_DATABASE');

        // Omitimos warnings de PHP para que no se arruine el JSON si faltan credenciales
        $this->conexion = @oci_connect($username, $password, $database, 'AL32UTF8');

        if (!$this->conexion) {
            if (!headers_sent()) {
                header("Content-Type: application/json; charset=UTF-8");
            }
            echo json_encode([
                "status" => "error",
                "message" => "Ocurrió un problema conectando a la base de datos. Por favor revisa la configuración."
            ]);
            exit;
        }

        return $this->conexion;
    }

    public function closeConnection() {
        if ($this->conexion) {
            oci_close($this->conexion);
        }
    }
}
?>
