<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/FamilyMemberHistory.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$familyModel = new FamilyMemberHistory($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    $result = $familyModel->updateFamilyMemberHistory($data->id, $data);
    
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
        "message" => "El campo 'id' es obligatorio para identificar el antecedente familiar a actualizar."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
