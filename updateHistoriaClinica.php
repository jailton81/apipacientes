<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/HistoriasClinicas.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
$payload = AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$historiasModel = new HistoriasClinicas($db);

$data = json_decode(file_get_contents("php://input"));

$id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : (isset($_GET['id_pcnte']) ? $_GET['id_pcnte'] : null);
$cnsctvo_pcnte = !empty($data->cnsctvo_pcnte) ? $data->cnsctvo_pcnte : (isset($_GET['cnsctvo_pcnte']) ? $_GET['cnsctvo_pcnte'] : null);

if (!empty($id_pcnte) && !empty($cnsctvo_pcnte)) {
    // Si no viene usuario_modificacion, usar datos del token JWT
    if (empty($data->usuario_modificacion) && !empty($payload['id'])) {
        $data->usuario_modificacion = substr($payload['id'], 0, 20);
    }

    $result = $historiasModel->updateHistoriaClinica($id_pcnte, $cnsctvo_pcnte, $data);
    
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
        "message" => "Los campos 'id_pcnte' y 'cnsctvo_pcnte' son obligatorios para la actualización."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
