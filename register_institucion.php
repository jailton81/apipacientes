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

if (!empty($data->email) && !empty($data->password) && !empty($data->nit_insttcion)) {
    // Calculamos el nit_cntbldad quitando el dígito de verificación
    // Asumimos el formato: XXXXXXXXX-Y
    $nit_parts = explode('-', $data->nit_insttcion);
    $data->nit_cntbldad = $nit_parts[0];
    
    $result = $auth->registerInstitucion($data);
    
    if ($result['status'] == 'success') {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "El email, el password y el NIT de la institución son obligatorios."]);
}

$database->closeConnection();
?>
