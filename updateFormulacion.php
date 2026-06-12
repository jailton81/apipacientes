<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Formulacion.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$formulacionModel = new Formulacion($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id_pcnte) && !empty($data->nmro_evlcion) && !empty($data->cnsctvo)) {
    $result = $formulacionModel->updateFormulacion($data->id_pcnte, $data->nmro_evlcion, $data->cnsctvo, $data);
    
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
        "message" => "Los campos 'id_pcnte', 'nmro_evlcion' y 'cnsctvo' son obligatorios para identificar la formulación a actualizar."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
