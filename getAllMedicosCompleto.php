<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Medico.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Verificar el token de logueo antes de continuar
AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$medicoModel = new Medico($db);

$resultado = $medicoModel->getAllMedicosCompleto();

if ($resultado['status'] == 'success') {
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode($resultado, JSON_PRETTY_PRINT);

$database->closeConnection();
?>
