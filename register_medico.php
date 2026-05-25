<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Medico.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

$database = new Database();
$db = $database->getConnection();

// Verificar token
$payload = AuthMiddleware::checkToken();

if (empty($payload['nit_cntbldad'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "El token de la institución no contiene un nit_cntbldad válido."]);
    exit();
}

$id_institucion = $payload['nit_cntbldad'];

$database = new Database();
$db = $database->getConnection();
$medicoModel = new Medico($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->idntfccion_mdcos)) {
    $result = $medicoModel->registerMedico($id_institucion, $data);
    
    if ($result['status'] == 'success') {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "El campo idntfccion_mdcos es obligatorio para registrar al médico."]);
}

$database->closeConnection();
?>
