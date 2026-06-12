<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Remisiones.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$remisionModel = new Remisiones($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id_pcnte) && !empty($data->cnsctvo_pcnte) && !empty($data->cnsctvo_rmsion)) {
    $result = $remisionModel->updateRemision($data->id_pcnte, $data->cnsctvo_pcnte, $data->cnsctvo_rmsion, $data);
    
    if ($result['status'] == 'success') {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => "Los campos 'id_pcnte', 'cnsctvo_pcnte' y 'cnsctvo_rmsion' son obligatorios para identificar la remisión a actualizar."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
