<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/IncapacidadesHC.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
$payload = AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$incapModel = new IncapacidadesHC($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id_pcnte) && !empty($data->cnsctvo_pcnte)) {
    // Si no viene id_mdco, usar datos del token JWT
    if (empty($data->id_mdco) && !empty($payload['id'])) {
        $data->id_mdco = substr($payload['id'], 0, 20);
    }

    $result = $incapModel->createIncapacidad($data);
    
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
        "message" => "Los campos 'id_pcnte' y 'cnsctvo_pcnte' son obligatorios."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
