<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/HistoriasClinicas.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
$payload = AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$historiasModel = new HistoriasClinicas($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id_pcnte)) {
    // Si no viene usuario_ingreso, usar datos del token JWT
    if (empty($data->usuario_ingreso) && !empty($payload['id'])) {
        $data->usuario_ingreso = substr($payload['id'], 0, 20);
    }

    $result = $historiasModel->createHistoriaClinica($data);
    
    if ($result['status'] == 'success') {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => "El campo 'id_pcnte' es obligatorio."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
