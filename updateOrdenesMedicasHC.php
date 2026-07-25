<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/OrdenesMedicasHC.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
$payload = AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$model = new OrdenesMedicasHC($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) || !empty($data->ID)) {
    $result = $model->updateOrdenesMedicasHC($data);
    
    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "El campo 'id' es obligatorio para realizar modificaciones."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
