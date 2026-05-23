<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Auth.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
    $result = $auth->register($data->name, $data->email, $data->password);
    
    if ($result['status'] == 'success') {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos incompletos para el registro."]);
}

$database->closeConnection();
?>
