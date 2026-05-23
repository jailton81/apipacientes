<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Verificar token antes de procesar
AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->old_password) && !empty($data->new_password)) {
    $result = $auth->changePasswordInstitucion($data->email, $data->old_password, $data->new_password);
    
    if ($result['status'] == 'success') {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400); // 400 Bad Request
        echo json_encode($result);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "El email, la contraseña actual y la nueva contraseña son obligatorios."]);
}

$database->closeConnection();
?>
