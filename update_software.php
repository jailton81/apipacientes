<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, POST"); // Some clients might use POST instead of PUT

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Verificar token antes de procesar
AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->nit_insttcion) && !empty($data->client_id) && !empty($data->client_secret) && !empty($data->scope) && !empty($data->subscription_key)) {
    
    $result = $auth->updateSoftware($data->nit_insttcion, $data->client_id, $data->client_secret, $data->scope, $data->subscription_key);
    
    if ($result['status'] == 'success') {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400); 
        echo json_encode($result);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Todos los campos (nit_insttcion, client_id, client_secret, scope, subscription_key) son obligatorios para actualizar."]);
}

$database->closeConnection();
?>
