<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/OrdenesMedicasHC.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
$payload = AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$model = new OrdenesMedicasHC($db);

// Obtener parámetros de la petición GET
$id_pcnte = isset($_GET['id_pcnte']) ? trim($_GET['id_pcnte']) : null;
$consecutivo_paciente = isset($_GET['consecutivo_paciente']) ? trim($_GET['consecutivo_paciente']) : null;

// Si no viene consecutivo_paciente, intentar con cnsctvo_pcnte
if ($consecutivo_paciente === null && isset($_GET['cnsctvo_pcnte'])) {
    $consecutivo_paciente = trim($_GET['cnsctvo_pcnte']);
}

if (!empty($id_pcnte) && $consecutivo_paciente !== null && $consecutivo_paciente !== '') {
    $result = $model->GetInfoOrdenesMedicasHC($id_pcnte, $consecutivo_paciente);
    
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
        "message" => "Los parámetros 'id_pcnte' y 'consecutivo_paciente' son requeridos."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
