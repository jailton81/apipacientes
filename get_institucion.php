<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Verificar el token de logueo antes de continuar
AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$email = isset($_GET['email']) ? trim($_GET['email']) : null;

if (!$email) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requiere el parámetro 'email' en la URL."
    ]);
    $database->closeConnection();
    exit;
}

$resultado = $auth->getInstitucionByEmail($email);

if ($resultado['status'] == 'success') {
    http_response_code(200);
} else {
    http_response_code(404);
}

echo json_encode($resultado, JSON_PRETTY_PRINT);

$database->closeConnection();
?>
