<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Estados.php';

$database = new Database();
$db = $database->getConnection();

$estadosModel = new Estados($db);

$status = isset($_GET['status']) ? $_GET['status'] : null;

if (!$status) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requiere el parámetro 'status' en la URL."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $database->closeConnection();
    exit;
}

// Obtener los datos desde el modelo (el método getStatusData verifica el token JWT internamente)
$response = $estadosModel->getStatusData($status);

// Enviar la respuesta JSON
echo $response;

$database->closeConnection();
?>
